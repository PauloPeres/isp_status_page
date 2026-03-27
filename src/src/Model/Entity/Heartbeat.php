<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Heartbeat Entity
 *
 * @property int $id
 * @property int $monitor_id
 * @property int $organization_id
 * @property string $token
 * @property \Cake\I18n\DateTime|null $last_ping_at
 * @property int $expected_interval
 * @property int|null $grace_period
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\Monitor $monitor
 * @property \App\Model\Entity\Organization $organization
 */
class Heartbeat extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'monitor_id' => true,
        'organization_id' => true,
        'token' => true,
        'last_ping_at' => true,
        'expected_interval' => true,
        'grace_period' => true,
        'created' => true,
        'monitor' => true,
        'organization' => true,
    ];

    /**
     * Check if the heartbeat is overdue (past expected interval + grace period)
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        if ($this->last_ping_at === null) {
            return true;
        }

        $gracePeriod = $this->grace_period ?? 60;
        $deadline = $this->last_ping_at->addSeconds($this->expected_interval + $gracePeriod);

        return $deadline->isPast();
    }
}
