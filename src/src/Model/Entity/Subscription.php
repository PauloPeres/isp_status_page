<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Subscription Entity
 *
 * @property int $id
 * @property int $subscriber_id
 * @property int|null $monitor_id
 * @property bool $notify_on_down
 * @property bool $notify_on_up
 * @property bool $notify_on_degraded
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Subscriber $subscriber
 * @property \App\Model\Entity\Monitor $monitor
 */
class Subscription extends Entity
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
        'subscriber_id' => true,
        'monitor_id' => true,
        'notify_on_down' => true,
        'notify_on_up' => true,
        'notify_on_degraded' => true,
        'created' => true,
        'modified' => true,
        'subscriber' => true,
        'monitor' => true,
    ];
}
