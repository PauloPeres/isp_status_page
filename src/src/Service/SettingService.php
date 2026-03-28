<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\SettingsTable;
use App\Tenant\TenantContext;
use Cake\Cache\Cache;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\TableRegistry;

/**
 * Setting Service
 *
 * Provides cached access to application settings.
 * Settings are stored in the database and cached for performance.
 *
 * Supports two levels of settings:
 * - System-level: stored in the `settings` table, ignores tenant scope.
 *   Used for SMTP, FTP backup, system defaults.
 * - Org-level: stored in Organization.settings JSON column.
 *   Used for org preferences (name, logo, timezone, notifications).
 *
 * The `get()` method cascades: org settings -> system settings -> default.
 */
class SettingService
{
    use LocatorAwareTrait;

    /**
     * Cache configuration name
     */
    private const CACHE_CONFIG = 'default';

    /**
     * Cache key prefix
     */
    private const CACHE_KEY = 'settings_all';

    /**
     * Cache duration in seconds (1 hour)
     */
    private const CACHE_DURATION = 3600;

    /**
     * Setting key prefixes that ALWAYS read from system level (bypass org).
     * These are platform-managed settings that customers cannot override.
     *
     * @var array<string>
     */
    private const SYSTEM_ONLY_PREFIXES = [
        'smtp_',
        'backup_ftp_',
    ];

    /**
     * Individual setting keys that ALWAYS read from system level (bypass org).
     *
     * @var array<string>
     */
    private const SYSTEM_ONLY_KEYS = [
        'default_language',
    ];

    /**
     * Setting keys that are org-level but fall back to system defaults.
     * Org can override these, but system provides the default.
     *
     * @var array<string>
     */
    private const ORG_OVERRIDABLE_KEYS = [
        'site_name',
    ];

    /**
     * Settings table instance
     *
     * @var \App\Model\Table\SettingsTable
     */
    private SettingsTable $Settings;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Settings = $this->fetchTable('Settings');
    }

    /**
     * Get all settings as an associative array
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        $settings = Cache::remember(
            self::CACHE_KEY,
            function () {
                $result = [];
                $settings = $this->Settings->find()->all();

                foreach ($settings as $setting) {
                    $result[$setting->key] = $setting->getTypedValue();
                }

                return $result;
            },
            self::CACHE_CONFIG
        );

        return $settings;
    }

    /**
     * Get a system-level setting (ignores tenant scope).
     * Used for: SMTP, FTP, system defaults.
     *
     * @param string $key The setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed
     */
    public function getSystem(string $key, mixed $default = null): mixed
    {
        $settingsTable = TableRegistry::getTableLocator()->get('Settings');
        // Query WITHOUT tenant scope
        $setting = $settingsTable->find()
            ->applyOptions(['skipTenantScope' => true])
            ->where(['key' => $key])
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Get org-level setting from Organization.settings JSON.
     *
     * @param string $key The setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed
     */
    public function getOrg(string $key, mixed $default = null): mixed
    {
        if (TenantContext::isSet()) {
            $org = TenantContext::getCurrentOrganization();
            $orgSettings = json_decode($org['settings'] ?? '{}', true);
            if (isset($orgSettings[$key])) {
                return $orgSettings[$key];
            }
        }

        return $default;
    }

    /**
     * Check if a setting key must always be read from system level.
     *
     * @param string $key The setting key
     * @return bool
     */
    private function isSystemOnlyKey(string $key): bool
    {
        // Check exact key matches
        if (in_array($key, self::SYSTEM_ONLY_KEYS, true)) {
            return true;
        }

        // Check prefix matches
        foreach (self::SYSTEM_ONLY_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a setting value by key.
     *
     * Cascades: org settings -> system settings -> default.
     * System-only keys (SMTP, FTP, etc.) bypass org and always read from system.
     *
     * @param string $key The setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // System-only keys always bypass org settings
        if ($this->isSystemOnlyKey($key)) {
            $settings = $this->getAll();

            return $settings[$key] ?? $default;
        }

        // For all other keys, cascade: org -> system -> default
        $orgValue = $this->getOrg($key);
        if ($orgValue !== null) {
            return $orgValue;
        }

        $settings = $this->getAll();

        return $settings[$key] ?? $default;
    }

    /**
     * Get a setting as string
     *
     * @param string $key The setting key
     * @param string $default Default value
     * @return string
     */
    public function getString(string $key, string $default = ''): string
    {
        return (string)$this->get($key, $default);
    }

    /**
     * Get a setting as integer
     *
     * @param string $key The setting key
     * @param int $default Default value
     * @return int
     */
    public function getInt(string $key, int $default = 0): int
    {
        return (int)$this->get($key, $default);
    }

    /**
     * Get a setting as boolean
     *
     * @param string $key The setting key
     * @param bool $default Default value
     * @return bool
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get a setting as array (from JSON)
     *
     * @param string $key The setting key
     * @param array $default Default value
     * @return array
     */
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        if (is_array($value)) {
            return $value;
        }

        return $default;
    }

    /**
     * Set a setting value
     *
     * @param string $key The setting key
     * @param mixed $value The value to set
     * @param string|null $type The type (auto-detected if null)
     * @return bool
     */
    public function set(string $key, mixed $value, ?string $type = null): bool
    {
        $setting = $this->Settings->findOrCreate(['key' => $key]);

        if ($type !== null) {
            $setting->type = $type;
        }

        $setting->value = $value;

        $result = $this->Settings->save($setting);

        if ($result) {
            $this->clearCache();

            return true;
        }

        return false;
    }

    /**
     * Check if a setting exists
     *
     * @param string $key The setting key
     * @return bool
     */
    public function has(string $key): bool
    {
        $settings = $this->getAll();

        return array_key_exists($key, $settings);
    }

    /**
     * Delete a setting
     *
     * @param string $key The setting key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $setting = $this->Settings->find()
            ->where(['key' => $key])
            ->first();

        if ($setting === null) {
            return false;
        }

        $result = $this->Settings->delete($setting);

        if ($result) {
            $this->clearCache();

            return true;
        }

        return false;
    }

    /**
     * Clear the settings cache
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        return Cache::delete(self::CACHE_KEY, self::CACHE_CONFIG);
    }

    /**
     * Get an organization-level setting, falling back to global settings.
     *
     * Organization settings are stored in the `settings` JSON column of the
     * organizations table. If TenantContext is set and the org has a value
     * for the requested key, that value is returned. Otherwise the global
     * system setting (from the settings table) is returned.
     *
     * @param string $key The setting key
     * @param mixed $default Default value if neither org nor global setting exists
     * @return mixed
     * @deprecated Use get() instead, which now cascades org -> system -> default.
     */
    public function getOrgSetting(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    /**
     * Reload settings from database (bypass cache)
     *
     * @return array<string, mixed>
     */
    public function reload(): array
    {
        $this->clearCache();

        return $this->getAll();
    }
}
