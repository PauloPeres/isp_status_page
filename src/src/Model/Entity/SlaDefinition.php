<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SlaDefinition Entity
 *
 * Represents a Service Level Agreement definition for a monitor.
 * Each monitor can have at most one active SLA.
 *
 * @property int $id
 * @property int $organization_id
 * @property int $monitor_id
 * @property string $name
 * @property string $target_uptime
 * @property string $measurement_period
 * @property bool $breach_notification
 * @property string|null $warning_threshold
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Monitor $monitor
 * @property \App\Model\Entity\SlaReport[] $sla_reports
 */
class SlaDefinition extends Entity
{
    /**
     * Measurement period constants
     */
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_QUARTERLY = 'quarterly';
    public const PERIOD_YEARLY = 'yearly';

    /**
     * Common SLA target presets
     */
    public const TARGET_THREE_NINES = '99.900';
    public const TARGET_THREE_NINES_FIVE = '99.950';
    public const TARGET_FOUR_NINES = '99.990';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'public_id' => true,
        'organization_id' => true,
        'monitor_id' => true,
        'name' => true,
        'target_uptime' => true,
        'measurement_period' => true,
        'breach_notification' => true,
        'warning_threshold' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'monitor' => true,
        'sla_reports' => true,
    ];

    /**
     * Get the allowed downtime in minutes per month for the target uptime.
     *
     * @return float
     */
    public function getAllowedDowntimePerMonth(): float
    {
        $target = (float)$this->target_uptime;
        $totalMinutesMonth = 30 * 24 * 60; // ~43200

        return round($totalMinutesMonth * (100 - $target) / 100, 2);
    }

    /**
     * Get a human-readable description of allowed downtime.
     *
     * @return string
     */
    public function getAllowedDowntimeDescription(): string
    {
        $minutes = $this->getAllowedDowntimePerMonth();

        if ($minutes < 1) {
            return round($minutes * 60, 1) . ' sec/month';
        }

        if ($minutes < 60) {
            return round($minutes, 1) . ' min/month';
        }

        $hours = floor($minutes / 60);
        $mins = round($minutes - ($hours * 60), 0);

        return "{$hours}h {$mins}m/month";
    }

    /**
     * Get available measurement periods.
     *
     * @return array<string, string>
     */
    public static function getMeasurementPeriods(): array
    {
        return [
            self::PERIOD_MONTHLY => __('Monthly'),
            self::PERIOD_QUARTERLY => __('Quarterly'),
            self::PERIOD_YEARLY => __('Yearly'),
        ];
    }

    /**
     * Get common target uptime presets with descriptions.
     *
     * @return array<string, string>
     */
    public static function getTargetPresets(): array
    {
        return [
            '99.900' => '99.9% (~43 min/month downtime)',
            '99.950' => '99.95% (~22 min/month downtime)',
            '99.990' => '99.99% (~4.3 min/month downtime)',
            'custom' => __('Custom'),
        ];
    }
}
