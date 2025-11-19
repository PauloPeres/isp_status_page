<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Monitor Entity
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string|null $configuration
 * @property int $check_interval
 * @property int $timeout
 * @property int $retry_count
 * @property string $status
 * @property \Cake\I18n\DateTime|null $last_check_at
 * @property \Cake\I18n\DateTime|null $next_check_at
 * @property string|null $uptime_percentage
 * @property bool $active
 * @property bool $visible_on_status_page
 * @property int $display_order
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\AlertLog[] $alert_logs
 * @property \App\Model\Entity\AlertRule[] $alert_rules
 * @property \App\Model\Entity\Incident[] $incidents
 * @property \App\Model\Entity\MonitorCheck[] $monitor_checks
 * @property \App\Model\Entity\Subscription[] $subscriptions
 */
class Monitor extends Entity
{
    /**
     * Monitor types
     */
    public const TYPE_HTTP = 'http';
    public const TYPE_PING = 'ping';
    public const TYPE_PORT = 'port';
    public const TYPE_API = 'api';
    public const TYPE_IXC = 'ixc';
    public const TYPE_ZABBIX = 'zabbix';

    /**
     * Monitor statuses
     */
    public const STATUS_UP = 'up';
    public const STATUS_DOWN = 'down';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_UNKNOWN = 'unknown';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'description' => true,
        'type' => true,
        'configuration' => true,
        'check_interval' => true,
        'timeout' => true,
        'retry_count' => true,
        'status' => true,
        'last_check_at' => true,
        'next_check_at' => true,
        'uptime_percentage' => true,
        'active' => true,
        'visible_on_status_page' => true,
        'display_order' => true,
        'created' => true,
        'modified' => true,
        'alert_logs' => true,
        'alert_rules' => true,
        'incidents' => true,
        'monitor_checks' => true,
        'subscriptions' => true,
    ];

    /**
     * Virtual fields to expose
     *
     * @var array<string>
     */
    protected array $_virtual = ['target'];

    /**
     * Get configuration as array
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        if (empty($this->configuration)) {
            return [];
        }

        if (is_array($this->configuration)) {
            return $this->configuration;
        }

        $decoded = json_decode($this->configuration, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get target from configuration (virtual field)
     *
     * Extracts the target/host from configuration based on monitor type
     *
     * @return string|null
     */
    protected function _getTarget(): ?string
    {
        $config = $this->getConfiguration();

        return match ($this->type) {
            self::TYPE_HTTP => $config['url'] ?? null,
            self::TYPE_PING => $config['host'] ?? null,
            self::TYPE_PORT => isset($config['host'], $config['port'])
                ? "{$config['host']}:{$config['port']}"
                : null,
            default => null,
        };
    }

    /**
     * Set configuration from array
     *
     * @param array|string|null $value Configuration array or JSON string
     * @return string|null
     */
    protected function _setConfiguration(array|string|null $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * Check if monitor is up
     *
     * @return bool
     */
    public function isUp(): bool
    {
        return $this->status === self::STATUS_UP;
    }

    /**
     * Check if monitor is down
     *
     * @return bool
     */
    public function isDown(): bool
    {
        return $this->status === self::STATUS_DOWN;
    }

    /**
     * Check if monitor is degraded
     *
     * @return bool
     */
    public function isDegraded(): bool
    {
        return $this->status === self::STATUS_DEGRADED;
    }

    /**
     * Check if monitor is unknown
     *
     * @return bool
     */
    public function isUnknown(): bool
    {
        return $this->status === self::STATUS_UNKNOWN;
    }

    /**
     * Check if monitor is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Check if monitor is visible on status page
     *
     * @return bool
     */
    public function isVisibleOnStatusPage(): bool
    {
        return $this->visible_on_status_page === true;
    }

    /**
     * Get status badge class for UI
     *
     * @return string
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_UP => 'success',
            self::STATUS_DOWN => 'danger',
            self::STATUS_DEGRADED => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get human-readable type name
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return match ($this->type) {
            self::TYPE_HTTP => 'HTTP/HTTPS',
            self::TYPE_PING => 'Ping',
            self::TYPE_PORT => 'Port Check',
            self::TYPE_API => 'API Endpoint',
            self::TYPE_IXC => 'IXC Soft',
            self::TYPE_ZABBIX => 'Zabbix',
            default => 'Unknown',
        };
    }
}
