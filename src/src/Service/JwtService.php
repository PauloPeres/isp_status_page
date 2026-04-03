<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * JwtService
 *
 * Handles JWT access token generation/verification and refresh token
 * lifecycle (generate, validate, revoke). Refresh tokens are stored
 * as SHA-256 hashes in the database; plain tokens are returned to
 * the client and never persisted.
 */
class JwtService
{
    use LocatorAwareTrait;

    private string $secretKey;
    private string $algorithm = 'HS256';
    private int $accessTokenTtl = 900; // 15 minutes
    private int $refreshTokenTtl = 604800; // 7 days

    public function __construct()
    {
        $secret = env('JWT_SECRET') ?: env('SECURITY_SALT');
        if (empty($secret)) {
            throw new \RuntimeException('JWT_SECRET or SECURITY_SALT environment variable must be configured.');
        }
        // Block default/weak secrets in production
        $isDebug = (bool)(env('APP_DEBUG') ?: false);
        if (!$isDebug && in_array($secret, ['change-me', 'secret', 'password', 'jwt-secret'], true)) {
            throw new \RuntimeException('JWT_SECRET or SECURITY_SALT must not use a default value in production. Set APP_DEBUG=true to bypass in development.');
        }
        // Ensure key meets HS256 minimum length (32 bytes / 256 bits)
        // by hashing short keys with SHA-256
        if (strlen((string)$secret) < 32) {
            $this->secretKey = hash('sha256', (string)$secret);
        } else {
            $this->secretKey = (string)$secret;
        }
    }

    /**
     * Generate an access token containing user identity and org context.
     *
     * @param int $userId The authenticated user ID.
     * @param int $orgId The current organization ID.
     * @param string $role The user's role within the organization.
     * @param bool $isSuperAdmin Whether the user is a super admin.
     * @return string The encoded JWT string.
     */
    public function generateAccessToken(int $userId, int $orgId, string $role, bool $isSuperAdmin = false): string
    {
        $payload = [
            'iss' => 'isp-status-page',
            'sub' => $userId,
            'org_id' => $orgId,
            'role' => $role,
            'is_super_admin' => $isSuperAdmin,
            'iat' => time(),
            'exp' => time() + $this->accessTokenTtl,
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Verify and decode an access token.
     *
     * @param string $token The JWT string.
     * @return object|null The decoded payload or null on failure.
     */
    public function verifyAccessToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secretKey, $this->algorithm));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate a refresh token — store hash in DB, return plain token.
     *
     * @param int $userId The user ID.
     * @param string $ipAddress Client IP address.
     * @param string $userAgent Client User-Agent header.
     * @return string The plain refresh token (64 hex characters).
     */
    public function generateRefreshToken(int $userId, string $ipAddress = '', string $userAgent = '', ?int $ttl = null): string
    {
        $plainToken = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plainToken);

        $effectiveTtl = $ttl ?? $this->refreshTokenTtl;

        $table = $this->fetchTable('RefreshTokens');
        $entity = $table->newEntity([
            'user_id' => $userId,
            'token_hash' => $hash,
            'expires_at' => DateTime::now()->modify('+' . $effectiveTtl . ' seconds'),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
        $table->save($entity);

        return $plainToken;
    }

    /**
     * Validate a refresh token — returns user_id or null.
     *
     * @param string $plainToken The plain refresh token.
     * @return int|null The user ID if valid, null otherwise.
     */
    public function validateRefreshToken(string $plainToken): ?int
    {
        $hash = hash('sha256', $plainToken);
        $table = $this->fetchTable('RefreshTokens');
        $record = $table->find()
            ->where([
                'token_hash' => $hash,
                'revoked_at IS' => null,
                'expires_at >' => DateTime::now(),
            ])
            ->first();

        if (!$record) {
            return null;
        }

        return $record->user_id;
    }

    /**
     * Revoke a specific refresh token.
     *
     * @param string $plainToken The plain refresh token to revoke.
     * @return void
     */
    public function revokeRefreshToken(string $plainToken): void
    {
        $hash = hash('sha256', $plainToken);
        $table = $this->fetchTable('RefreshTokens');
        $table->updateAll(
            ['revoked_at' => DateTime::now()],
            ['token_hash' => $hash]
        );
    }

    /**
     * Revoke all active refresh tokens for a user.
     *
     * @param int $userId The user ID.
     * @return void
     */
    public function revokeAllUserTokens(int $userId): void
    {
        $table = $this->fetchTable('RefreshTokens');
        $table->updateAll(
            ['revoked_at' => DateTime::now()],
            ['user_id' => $userId, 'revoked_at IS' => null]
        );
    }

    /**
     * Get the access token TTL in seconds.
     *
     * @return int
     */
    public function getAccessTokenTtl(): int
    {
        return $this->accessTokenTtl;
    }

    /**
     * Block a token by adding its identifier to the Redis blocklist.
     *
     * The key expires automatically when the token would have expired,
     * so the blocklist is self-cleaning and never grows unbounded.
     *
     * @param string $tokenId The token identifier (jti claim or hash of the token).
     * @param int $expiresAt The token's exp timestamp.
     * @return void
     */
    public function blockToken(string $tokenId, int $expiresAt): void
    {
        $ttl = $expiresAt - time();
        if ($ttl <= 0) {
            // Token already expired — no need to block it
            return;
        }

        try {
            $redis = $this->getBlocklistRedis();
            $redis->setex('keepup:blocked_token:' . $tokenId, $ttl, '1');
        } catch (\Exception $e) {
            Log::error('JwtService: Failed to block token: ' . $e->getMessage());
        }
    }

    /**
     * Check if a token is in the blocklist.
     *
     * @param string $tokenId The token identifier (jti claim or hash of the token).
     * @return bool True if blocked, false otherwise.
     */
    public function isTokenBlocked(string $tokenId): bool
    {
        try {
            $redis = $this->getBlocklistRedis();

            return (bool)$redis->exists('keepup:blocked_token:' . $tokenId);
        } catch (\Exception $e) {
            Log::error('JwtService: Failed to check token blocklist: ' . $e->getMessage());

            // Fail open only if Redis is completely down — this is a tradeoff.
            // In production you may want to fail closed (return true).
            return false;
        }
    }

    /**
     * Derive a stable identifier for a token.
     *
     * Uses the jti claim if present, otherwise hashes the raw token string.
     *
     * @param string $rawToken The raw JWT string.
     * @param object|null $payload The decoded payload (optional, to avoid double-decode).
     * @return string A stable identifier suitable for the blocklist key.
     */
    public function getTokenIdentifier(string $rawToken, ?object $payload = null): string
    {
        if ($payload !== null && isset($payload->jti)) {
            return (string)$payload->jti;
        }

        return hash('sha256', $rawToken);
    }

    /**
     * Get a Redis connection for the token blocklist.
     *
     * Uses DB 6 (same as locks) with the standard REDIS_URL env var.
     *
     * @return \Redis
     */
    private function getBlocklistRedis(): \Redis
    {
        static $redis = null;
        if ($redis !== null) {
            try {
                $redis->ping();

                return $redis;
            } catch (\Exception $e) {
                $redis = null;
            }
        }

        $redis = new \Redis();

        $redisUrl = getenv('REDIS_URL') ?: '';
        $host = '127.0.0.1';
        $port = 6379;
        $password = '';

        if ($redisUrl) {
            $parsed = parse_url($redisUrl);
            $host = $parsed['host'] ?? '127.0.0.1';
            $port = $parsed['port'] ?? 6379;
            $password = $parsed['pass'] ?? '';
        }

        $redis->connect($host, $port, 2.0);

        if ($password !== '') {
            $redis->auth($password);
        }

        $redis->select(6);

        return $redis;
    }
}
