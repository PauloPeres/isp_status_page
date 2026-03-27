<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\ApiKey;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * ApiKeyService
 *
 * Handles generation, validation, and revocation of API keys.
 *
 * Key format: "sk_live_" + 32 random hex chars (72 chars total)
 * Prefix: first 12 characters for DB lookup
 * Hash: bcrypt hash of the full key for verification
 */
class ApiKeyService
{
    use LocatorAwareTrait;

    /**
     * Key prefix identifier
     */
    public const KEY_PREFIX_IDENTIFIER = 'sk_live_';

    /**
     * Generate a new API key.
     *
     * Returns the plain key (only shown once) along with the saved entity.
     *
     * @param int $orgId Organization ID
     * @param int $userId User ID who created the key
     * @param string $name Human-readable name for the key
     * @param array $permissions Array of permissions (read, write, admin)
     * @param int $rateLimit Rate limit per hour
     * @param \Cake\I18n\DateTime|null $expiresAt Optional expiration date
     * @return array{key: string, entity: \App\Model\Entity\ApiKey} Plain key and saved entity
     * @throws \RuntimeException If the key could not be saved
     */
    public function generate(
        int $orgId,
        int $userId,
        string $name,
        array $permissions = ['read'],
        int $rateLimit = 1000,
        ?DateTime $expiresAt = null
    ): array {
        // Generate key: "sk_live_" + 32 random hex chars
        $plainKey = self::KEY_PREFIX_IDENTIFIER . bin2hex(random_bytes(32));
        $prefix = substr($plainKey, 0, 12); // "sk_live_XXXX"
        $hash = password_hash($plainKey, PASSWORD_DEFAULT);

        $apiKeysTable = $this->fetchTable('ApiKeys');

        $entity = $apiKeysTable->newEntity([
            'organization_id' => $orgId,
            'user_id' => $userId,
            'name' => $name,
            'key_hash' => $hash,
            'key_prefix' => $prefix,
            'permissions' => json_encode($permissions),
            'rate_limit' => $rateLimit,
            'expires_at' => $expiresAt,
            'active' => true,
        ]);

        $saved = $apiKeysTable->save($entity);
        if (!$saved) {
            Log::error('Failed to save API key for org=' . $orgId . ', user=' . $userId);
            throw new \RuntimeException('Failed to save API key.');
        }

        Log::info('API key generated: name=' . $name . ', org=' . $orgId . ', user=' . $userId);

        return [
            'key' => $plainKey,
            'entity' => $saved,
        ];
    }

    /**
     * Validate an API key.
     *
     * Finds the key by prefix, then verifies the hash.
     * Updates last_used_at on successful validation.
     *
     * @param string $key The plain API key to validate
     * @return \App\Model\Entity\ApiKey|null The API key entity if valid, null otherwise
     */
    public function validate(string $key): ?ApiKey
    {
        // Basic format check
        if (strlen($key) < 12 || !str_starts_with($key, self::KEY_PREFIX_IDENTIFIER)) {
            return null;
        }

        $prefix = substr($key, 0, 12);

        $apiKeysTable = $this->fetchTable('ApiKeys');

        // Find active keys with this prefix (skip tenant scope for API auth)
        $candidates = $apiKeysTable->find()
            ->where([
                'ApiKeys.key_prefix' => $prefix,
                'ApiKeys.active' => true,
            ])
            ->contain(['Organizations'])
            ->all();

        foreach ($candidates as $candidate) {
            // Skip expired keys
            if ($candidate->isExpired()) {
                continue;
            }

            // Verify the hash
            if (password_verify($key, $candidate->key_hash)) {
                // Update last_used_at
                $candidate->last_used_at = DateTime::now();
                $apiKeysTable->save($candidate);

                return $candidate;
            }
        }

        return null;
    }

    /**
     * Revoke (deactivate) an API key.
     *
     * @param int $keyId The API key ID to revoke
     * @return bool True if revoked successfully
     */
    public function revoke(int $keyId): bool
    {
        $apiKeysTable = $this->fetchTable('ApiKeys');

        try {
            $entity = $apiKeysTable->get($keyId);
            $entity->active = false;
            $result = $apiKeysTable->save($entity);

            if ($result) {
                Log::info('API key revoked: id=' . $keyId);

                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to revoke API key id=' . $keyId . ': ' . $e->getMessage());
        }

        return false;
    }
}
