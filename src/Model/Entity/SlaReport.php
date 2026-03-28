<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SlaReport Entity
 *
 * Represents a calculated SLA compliance report for a specific period.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $sla_definition_id
 * @property int $monitor_id
 * @property \Cake\I18n\Date $period_start
 * @property \Cake\I18n\Date $period_end
 * @property string $period_type
 * @property string $target_uptime
 * @property string $actual_uptime
 * @property int $total_minutes
 * @property string $downtime_minutes
 * @property string $allowed_downtime_minutes
 * @property string $remaining_downtime_minutes
 * @property string $status
 * @property int $incidents_count
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\SlaDefinition $sla_definition
 * @property \App\Model\Entity\Monitor $monitor
 */
class SlaReport extends Entity
{
    /**
     * SLA status constants
     */
    public const STATUS_COMPLIANT = 'compliant';
    public const STATUS_AT_RISK = 'at_risk';
    public const STATUS_BREACHED = 'breached';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'sla_definition_id' => true,
        'monitor_id' => true,
        'period_start' => true,
        'period_end' => true,
        'period_type' => true,
        'target_uptime' => true,
        'actual_uptime' => true,
        'total_minutes' => true,
        'downtime_minutes' => true,
        'allowed_downtime_minutes' => true,
        'remaining_downtime_minutes' => true,
        'status' => true,
        'incidents_count' => true,
        'created' => true,
        'modified' => true,
        'sla_definition' => true,
        'monitor' => true,
    ];

    /**
     * Virtual fields to expose
     *
     * @var array<string>
     */
    protected array $_virtual = ['is_breached', 'is_at_risk', 'uptime_formatted'];

    /**
     * Check if this report indicates a breach.
     *
     * @return bool
     */
    protected function _getIsBreached(): bool
    {
        return $this->status === self::STATUS_BREACHED;
    }

    /**
     * Check if this report indicates at-risk status.
     *
     * @return bool
     */
    protected function _getIsAtRisk(): bool
    {
        return $this->status === self::STATUS_AT_RISK;
    }

    /**
     * Get the actual uptime formatted as a string with three decimal places.
     *
     * @return string
     */
    protected function _getUptimeFormatted(): string
    {
        return number_format((float)$this->actual_uptime, 3) . '%';
    }

    /**
     * Get the CSS badge class for the current status.
     *
     * @return string
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLIANT => 'badge-success',
            self::STATUS_AT_RISK => 'badge-warning',
            self::STATUS_BREACHED => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    /**
     * Get human-readable status label.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLIANT => __('Compliant'),
            self::STATUS_AT_RISK => __('At Risk'),
            self::STATUS_BREACHED => __('Breached'),
            default => __('Unknown'),
        };
    }

    /**
     * Get the downtime budget usage as a percentage (0-100).
     *
     * @return float
     */
    public function getBudgetUsagePercent(): float
    {
        $allowed = (float)$this->allowed_downtime_minutes;

        if ($allowed <= 0) {
            return 100.0;
        }

        $used = (float)$this->downtime_minutes;

        return min(100.0, round(($used / $allowed) * 100, 1));
    }
}
