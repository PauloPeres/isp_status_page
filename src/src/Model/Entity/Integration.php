<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Integration Entity
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $configuration
 * @property bool $active
 * @property \Cake\I18n\DateTime|null $last_sync_at
 * @property string|null $last_sync_status
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\IntegrationLog[] $integration_logs
 */
class Integration extends Entity
{
    /**
     * Integration types
     */
    public const TYPE_IXC = 'ixc';
    public const TYPE_ZABBIX = 'zabbix';

    /**
     * Sync statuses
     */
    public const SYNC_SUCCESS = 'success';
    public const SYNC_ERROR = 'error';
    public const SYNC_PENDING = 'pending';

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
        'type' => true,
        'configuration' => true,
        'active' => true,
        'last_sync_at' => true,
        'last_sync_status' => true,
        'created' => true,
        'modified' => true,
        'integration_logs' => true,
    ];

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
     * Set configuration from array
     *
     * @param array|string $value Configuration array or JSON string
     * @return string
     */
    protected function _setConfiguration(array|string $value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * Check if integration is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Check if last sync was successful
     *
     * @return bool
     */
    public function lastSyncSuccessful(): bool
    {
        return $this->last_sync_status === self::SYNC_SUCCESS;
    }

    /**
     * Get human-readable type name
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return match ($this->type) {
            self::TYPE_IXC => 'IXC Soft',
            self::TYPE_ZABBIX => 'Zabbix',
            default => 'Unknown',
        };
    }
}
