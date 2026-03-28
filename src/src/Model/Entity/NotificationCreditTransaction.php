<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * NotificationCreditTransaction Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property string $type
 * @property int $amount
 * @property int $balance_after
 * @property string|null $channel
 * @property string|null $description
 * @property string|null $reference_id
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\Organization $organization
 */
class NotificationCreditTransaction extends Entity
{
    /**
     * Transaction types
     */
    public const TYPE_USAGE = 'usage';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_MONTHLY_GRANT = 'monthly_grant';
    public const TYPE_MANUAL_ADJUSTMENT = 'manual_adjustment';
    public const TYPE_REFUND = 'refund';

    /**
     * Valid transaction types
     */
    public const VALID_TYPES = [
        self::TYPE_USAGE,
        self::TYPE_PURCHASE,
        self::TYPE_MONTHLY_GRANT,
        self::TYPE_MANUAL_ADJUSTMENT,
        self::TYPE_REFUND,
    ];

    /**
     * Fields that can be mass assigned.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'type' => true,
        'amount' => true,
        'balance_after' => true,
        'channel' => true,
        'description' => true,
        'reference_id' => true,
        'created' => true,
    ];
}
