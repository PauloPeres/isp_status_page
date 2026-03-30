<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MonitorCheck Entity
 *
 * @property int $id
 * @property int $monitor_id
 * @property string $status
 * @property int|null $response_time
 * @property int|null $status_code
 * @property string|null $error_message
 * @property string|null $details
 * @property \Cake\I18n\DateTime $checked_at
 * @property \Cake\I18n\DateTime $created
 *
 * @property int|null $region_id
 * @property \App\Model\Entity\Monitor $monitor
 * @property \App\Model\Entity\CheckRegion|null $check_region
 */
class MonitorCheck extends Entity
{
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
        'organization_id' => true,
        'monitor_id' => true,
        'status' => true,
        'response_time' => true,
        'status_code' => true,
        'error_message' => true,
        'details' => true,
        'region_id' => true,
        'checked_at' => true,
        'created' => true,
        'monitor' => true,
        'check_region' => true,
    ];
}
