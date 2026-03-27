<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * MaintenanceWindow Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property string $title
 * @property string|null $description
 * @property string|null $monitor_ids
 * @property \Cake\I18n\DateTime $starts_at
 * @property \Cake\I18n\DateTime $ends_at
 * @property bool $auto_suppress_alerts
 * @property bool $notify_subscribers
 * @property string $status
 * @property int|null $created_by
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 */
class MaintenanceWindow extends Entity
{
    /**
     * Status constants
     */
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'title' => true,
        'description' => true,
        'monitor_ids' => true,
        'starts_at' => true,
        'ends_at' => true,
        'auto_suppress_alerts' => true,
        'notify_subscribers' => true,
        'status' => true,
        'created_by' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Get monitor IDs as array
     *
     * @return array
     */
    public function getMonitorIdsList(): array
    {
        if (empty($this->monitor_ids)) {
            return [];
        }

        if (is_array($this->monitor_ids)) {
            return $this->monitor_ids;
        }

        $decoded = json_decode($this->monitor_ids, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Check if maintenance is currently active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $now = DateTime::now();

        return $this->status !== self::STATUS_CANCELLED
            && $this->status !== self::STATUS_COMPLETED
            && $now->greaterThanOrEquals($this->starts_at)
            && $now->lessThanOrEquals($this->ends_at);
    }

    /**
     * Check if maintenance is scheduled (not yet started)
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if a specific monitor is affected by this maintenance window
     *
     * @param int $monitorId The monitor ID to check
     * @return bool
     */
    public function affectsMonitor(int $monitorId): bool
    {
        $ids = $this->getMonitorIdsList();

        // Empty list means all monitors are affected
        if (empty($ids)) {
            return true;
        }

        return in_array($monitorId, $ids, false);
    }

    /**
     * Check if alerts should be suppressed for this maintenance window
     *
     * @return bool
     */
    public function shouldSuppressAlerts(): bool
    {
        return $this->auto_suppress_alerts && $this->isActive();
    }
}
