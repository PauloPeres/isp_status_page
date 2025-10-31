<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AlertLog Entity
 *
 * @property int $id
 * @property int $alert_rule_id
 * @property int|null $incident_id
 * @property int $monitor_id
 * @property string $channel
 * @property string $recipient
 * @property string $status
 * @property \Cake\I18n\DateTime|null $sent_at
 * @property string|null $error_message
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\AlertRule $alert_rule
 * @property \App\Model\Entity\Incident $incident
 * @property \App\Model\Entity\Monitor $monitor
 */
class AlertLog extends Entity
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
        'alert_rule_id' => true,
        'incident_id' => true,
        'monitor_id' => true,
        'channel' => true,
        'recipient' => true,
        'status' => true,
        'sent_at' => true,
        'error_message' => true,
        'created' => true,
        'alert_rule' => true,
        'incident' => true,
        'monitor' => true,
    ];
}
