<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * MonitorCheckDetail Entity (TASK-DB-011)
 *
 * Stores error_message and details for monitor checks in a separate
 * companion table to reduce the main monitor_checks heap size.
 *
 * @property int $check_id
 * @property string|null $error_message
 * @property string|null $details
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\MonitorCheck $monitor_check
 */
class MonitorCheckDetail extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'check_id' => true,
        'error_message' => true,
        'details' => true,
        'created' => true,
        'monitor_check' => true,
    ];
}
