<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Table\SettingsTable;
use Cake\Cache\Cache;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Setting Service
 *
 * Provides cached access to application settings.
 * Settings are stored in the database and cached for performance.
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
     * Get a setting value by key
     *
     * @param string $key The setting key
     * @param mixed $default Default value if setting doesn't exist
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
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
