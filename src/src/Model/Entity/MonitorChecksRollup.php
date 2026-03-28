<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MonitorChecksRollup Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property int $monitor_id
 * @property \Cake\I18n\DateTime $period_start
 * @property \Cake\I18n\DateTime $period_end
 * @property string $period_type
 * @property int $check_count
 * @property int $success_count
 * @property int $failure_count
 * @property int $timeout_count
 * @property int $error_count
 * @property float|null $avg_response_time
 * @property int|null $min_response_time
 * @property int|null $max_response_time
 * @property float|null $uptime_percentage
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\Monitor $monitor
 */
class MonitorChecksRollup extends Entity
{
    /**
     * Period type constants
     */
    public const PERIOD_5MIN = '5min';
    public const PERIOD_1HOUR = '1hour';
    public const PERIOD_1DAY = '1day';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'monitor_id' => true,
        'period_start' => true,
        'period_end' => true,
        'period_type' => true,
        'check_count' => true,
        'success_count' => true,
        'failure_count' => true,
        'timeout_count' => true,
        'error_count' => true,
        'avg_response_time' => true,
        'min_response_time' => true,
        'max_response_time' => true,
        'uptime_percentage' => true,
        'created' => true,
        'organization' => true,
        'monitor' => true,
    ];
}
