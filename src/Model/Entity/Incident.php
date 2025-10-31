<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Incident Entity
 *
 * @property int $id
 * @property int $monitor_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $severity
 * @property \Cake\I18n\DateTime $started_at
 * @property \Cake\I18n\DateTime|null $identified_at
 * @property \Cake\I18n\DateTime|null $resolved_at
 * @property int|null $duration
 * @property bool $auto_created
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Monitor $monitor
 * @property \App\Model\Entity\AlertLog[] $alert_logs
 */
class Incident extends Entity
{
    /**
     * Incident statuses
     */
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_IDENTIFIED = 'identified';
    public const STATUS_MONITORING = 'monitoring';
    public const STATUS_RESOLVED = 'resolved';

    /**
     * Incident severities
     */
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_MAJOR = 'major';
    public const SEVERITY_MINOR = 'minor';
    public const SEVERITY_MAINTENANCE = 'maintenance';

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
        'monitor_id' => true,
        'title' => true,
        'description' => true,
        'status' => true,
        'severity' => true,
        'started_at' => true,
        'identified_at' => true,
        'resolved_at' => true,
        'duration' => true,
        'auto_created' => true,
        'created' => true,
        'modified' => true,
        'monitor' => true,
        'alert_logs' => true,
    ];

    /**
     * Check if incident is resolved
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if incident is ongoing
     *
     * @return bool
     */
    public function isOngoing(): bool
    {
        return !$this->isResolved();
    }

    /**
     * Get severity badge class for UI
     *
     * @return string
     */
    public function getSeverityBadgeClass(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_MAJOR => 'warning',
            self::SEVERITY_MINOR => 'info',
            self::SEVERITY_MAINTENANCE => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get human-readable status name
     *
     * @return string
     */
    public function getStatusName(): string
    {
        return match ($this->status) {
            self::STATUS_INVESTIGATING => 'Investigating',
            self::STATUS_IDENTIFIED => 'Identified',
            self::STATUS_MONITORING => 'Monitoring',
            self::STATUS_RESOLVED => 'Resolved',
            default => 'Unknown',
        };
    }
}
