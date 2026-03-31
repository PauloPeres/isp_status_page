<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * NotificationPolicyStep Entity
 *
 * An individual step within a notification policy chain.
 * Each step references a notification channel and defines a delay.
 *
 * @property int $id
 * @property int $notification_policy_id
 * @property int $step_order
 * @property int $delay_minutes
 * @property int $notification_channel_id
 * @property bool $notify_on_resolve
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\NotificationPolicy $notification_policy
 * @property \App\Model\Entity\NotificationChannel $notification_channel
 */
class NotificationPolicyStep extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'notification_policy_id' => true,
        'step_order' => true,
        'delay_minutes' => true,
        'notification_channel_id' => true,
        'notify_on_resolve' => true,
        'created' => true,
        'notification_policy' => true,
        'notification_channel' => true,
    ];
}
