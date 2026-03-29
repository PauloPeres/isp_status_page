<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\JwtService;
use Cake\TestSuite\TestCase;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * App\Service\JwtService Test Case
 *
 * Tests JWT access token generation/verification and refresh token lifecycle.
 */
class JwtServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.RefreshTokens',
    ];

    protected JwtService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Set a known JWT secret for deterministic testing
        putenv('JWT_SECRET=test-jwt-secret-key-for-testing-only-1234567890');

        $this->service = new JwtService();
    }

    protected function tearDown(): void
    {
        putenv('JWT_SECRET');
        unset($this->service);
        parent::tearDown();
    }

    /**
     * Test that generateAccessToken returns a non-empty string.
     */
    public function testGenerateAccessTokenReturnsString(): void
    {
        $token = $this->service->generateAccessToken(1, 10, 'admin', false);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        // JWT has 3 parts separated by dots
        $this->assertCount(3, explode('.', $token));
    }

    /**
     * Test that a valid token can be decoded with correct claims.
     */
    public function testVerifyValidToken(): void
    {
        $token = $this->service->generateAccessToken(42, 7, 'owner', true);

        $payload = $this->service->verifyAccessToken($token);

        $this->assertNotNull($payload);
        $this->assertIsObject($payload);
        $this->assertEquals('isp-status-page', $payload->iss);
        $this->assertEquals(42, $payload->sub);
        $this->assertEquals(7, $payload->org_id);
        $this->assertEquals('owner', $payload->role);
        $this->assertTrue($payload->is_super_admin);
        $this->assertObjectHasProperty('iat', $payload);
        $this->assertObjectHasProperty('exp', $payload);
        $this->assertGreaterThan(time(), $payload->exp);
    }

    /**
     * Test that an expired token returns null.
     */
    public function testVerifyExpiredToken(): void
    {
        // Manually create an already-expired JWT
        $secretKey = 'test-jwt-secret-key-for-testing-only-1234567890';
        $expiredPayload = [
            'iss' => 'isp-status-page',
            'sub' => 1,
            'org_id' => 1,
            'role' => 'admin',
            'is_super_admin' => false,
            'iat' => time() - 3600,
            'exp' => time() - 1800, // expired 30 minutes ago
        ];
        $expiredToken = JWT::encode($expiredPayload, $secretKey, 'HS256');

        $result = $this->service->verifyAccessToken($expiredToken);

        $this->assertNull($result);
    }

    /**
     * Test that a token signed with a wrong key returns null.
     */
    public function testVerifyTokenWithWrongKey(): void
    {
        $wrongKey = 'completely-different-secret-key';
        $payload = [
            'iss' => 'isp-status-page',
            'sub' => 1,
            'org_id' => 1,
            'role' => 'admin',
            'is_super_admin' => false,
            'iat' => time(),
            'exp' => time() + 900,
        ];
        $token = JWT::encode($payload, $wrongKey, 'HS256');

        $result = $this->service->verifyAccessToken($token);

        $this->assertNull($result);
    }

    /**
     * Test that verifying garbage returns null.
     */
    public function testVerifyGarbageToken(): void
    {
        $result = $this->service->verifyAccessToken('not-a-jwt-at-all');

        $this->assertNull($result);
    }

    /**
     * Test the full refresh token flow: generate, validate, revoke.
     */
    public function testRefreshTokenFlow(): void
    {
        // Generate a refresh token for user 1
        $plainToken = $this->service->generateRefreshToken(1, '127.0.0.1', 'PHPUnit');

        $this->assertIsString($plainToken);
        $this->assertEquals(64, strlen($plainToken)); // 32 bytes = 64 hex chars

        // Validate — should return user_id
        $userId = $this->service->validateRefreshToken($plainToken);
        $this->assertEquals(1, $userId);

        // Revoke the token
        $this->service->revokeRefreshToken($plainToken);

        // Validate again — should return null after revocation
        $userId = $this->service->validateRefreshToken($plainToken);
        $this->assertNull($userId);
    }

    /**
     * Test that validating a non-existent refresh token returns null.
     */
    public function testValidateNonExistentRefreshToken(): void
    {
        $result = $this->service->validateRefreshToken('0000000000000000000000000000000000000000000000000000000000000000');

        $this->assertNull($result);
    }

    /**
     * Test revoking all user tokens.
     */
    public function testRevokeAllUserTokens(): void
    {
        // Generate two refresh tokens for the same user
        $token1 = $this->service->generateRefreshToken(1, '127.0.0.1', 'PHPUnit');
        $token2 = $this->service->generateRefreshToken(1, '127.0.0.1', 'PHPUnit');

        // Both should be valid
        $this->assertEquals(1, $this->service->validateRefreshToken($token1));
        $this->assertEquals(1, $this->service->validateRefreshToken($token2));

        // Revoke all tokens for user 1
        $this->service->revokeAllUserTokens(1);

        // Both should now be invalid
        $this->assertNull($this->service->validateRefreshToken($token1));
        $this->assertNull($this->service->validateRefreshToken($token2));
    }

    /**
     * Test that getAccessTokenTtl returns the expected default.
     */
    public function testGetAccessTokenTtl(): void
    {
        $this->assertEquals(900, $this->service->getAccessTokenTtl());
    }
}
