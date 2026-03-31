<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * ApiKey Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property string $name
 * @property string $key_hash
 * @property string $key_prefix
 * @property string|null $permissions
 * @property int $rate_limit
 * @property \Cake\I18n\DateTime|null $last_used_at
 * @property \Cake\I18n\DateTime|null $expires_at
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\User $user
 */
class ApiKey extends Entity
{
    /**
     * Permission constants
     */
    public const PERMISSION_READ = 'read';
    public const PERMISSION_WRITE = 'write';
    public const PERMISSION_ADMIN = 'admin';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'user_id' => true,
        'name' => true,
        'key_hash' => true,
        'key_prefix' => true,
        'permissions' => true,
        'rate_limit' => false,   // Must be set explicitly — prevents user self-escalation
        'last_used_at' => true,
        'expires_at' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'organization' => true,
        'user' => true,
    ];

    /**
     * Fields that are hidden from serialization
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'key_hash',
    ];

    /**
     * Check if the API key has a specific permission
     *
     * @param string $permission The permission to check (read, write, admin)
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        $perms = $this->getPermissions();

        // Admin permission grants all access
        if (in_array(self::PERMISSION_ADMIN, $perms, true)) {
            return true;
        }

        // Write permission includes read
        if ($permission === self::PERMISSION_READ && in_array(self::PERMISSION_WRITE, $perms, true)) {
            return true;
        }

        return in_array($permission, $perms, true);
    }

    /**
     * Check if the API key is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Get permissions as an array
     *
     * @return array<string>
     */
    public function getPermissions(): array
    {
        if (empty($this->permissions)) {
            return [self::PERMISSION_READ];
        }

        if (is_array($this->permissions)) {
            return $this->permissions;
        }

        $decoded = json_decode($this->permissions, true);

        return is_array($decoded) ? $decoded : [self::PERMISSION_READ];
    }

    /**
     * Set permissions from array
     *
     * @param array|string $value Permissions array or JSON string
     * @return string
     */
    protected function _setPermissions(array|string $value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
