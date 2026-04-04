<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * NotificationCredit Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property int $balance
 * @property int $monthly_grant
 * @property bool $auto_recharge
 * @property int $auto_recharge_threshold
 * @property int $auto_recharge_amount
 * @property \Cake\I18n\DateTime|null $last_grant_at
 * @property int|null $auto_replenish_max_monthly
 * @property \Cake\I18n\DateTime|null $auto_replenish_last_charged_at
 * @property \Cake\I18n\DateTime|null $low_balance_notified_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 */
class NotificationCredit extends Entity
{
    /**
     * Fields that can be mass assigned.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => false,
        'balance' => true,
        'monthly_grant' => true,
        'auto_recharge' => true,
        'auto_recharge_threshold' => true,
        'auto_recharge_amount' => true,
        'auto_replenish_max_monthly' => true,
        'auto_replenish_last_charged_at' => true,
        'last_grant_at' => true,
        'low_balance_notified_at' => true,
        'created' => false,
        'modified' => false,
    ];
}
