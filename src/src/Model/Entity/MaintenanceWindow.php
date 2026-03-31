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
 * @property bool|null $is_recurring
 * @property string|null $recurrence_pattern
 * @property string|null $recurrence_days
 * @property string|null $recurrence_time_start
 * @property string|null $recurrence_time_end
 * @property \Cake\I18n\DateTime|null $effective_from
 * @property \Cake\I18n\DateTime|null $recurrence_end_date
 * @property int|null $parent_window_id
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
        'is_recurring' => true,
        'recurrence_pattern' => true,
        'recurrence_days' => true,
        'recurrence_time_start' => true,
        'recurrence_time_end' => true,
        'effective_from' => true,
        'recurrence_end_date' => true,
        'parent_window_id' => true,
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
        $now = new \DateTime();

        // Recurring window: check day-of-week + time
        if (!empty($this->is_recurring) && !empty($this->recurrence_time_start) && !empty($this->recurrence_time_end)) {
            // Check effective date range
            if ($this->effective_from && $now < new \DateTime($this->effective_from->format('Y-m-d'))) {
                return false;
            }
            if ($this->recurrence_end_date && $now > new \DateTime($this->recurrence_end_date->format('Y-m-d') . ' 23:59:59')) {
                return false;
            }

            // Check day of week
            $days = json_decode($this->recurrence_days ?? '[]', true) ?: [];
            if (!empty($days)) {
                $dayMap = ['sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6];
                $currentDay = (int)$now->format('w');
                $matchesDay = false;
                foreach ($days as $day) {
                    if (isset($dayMap[$day]) && $dayMap[$day] === $currentDay) {
                        $matchesDay = true;
                        break;
                    }
                }
                if (!$matchesDay) return false;
            }

            // Check time window
            $currentTime = $now->format('H:i');
            $start = $this->recurrence_time_start;
            $end = $this->recurrence_time_end;

            if ($start <= $end) {
                return $currentTime >= $start && $currentTime < $end;
            }
            // Overnight window (e.g., 22:00 - 06:00)
            return $currentTime >= $start || $currentTime < $end;
        }

        // One-time window: original logic
        if (empty($this->starts_at) || empty($this->ends_at)) {
            return false;
        }
        return $now >= $this->starts_at && $now <= $this->ends_at;
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
