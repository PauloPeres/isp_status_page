<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * NotificationPolicy Entity
 *
 * A reusable notification chain that defines WHEN and HOW to notify.
 * Contains ordered steps, each referencing a notification channel.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $description
 * @property string $trigger_type
 * @property int $repeat_interval_minutes
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\NotificationPolicyStep[] $notification_policy_steps
 * @property \App\Model\Entity\Monitor[] $monitors
 */
class NotificationPolicy extends Entity
{
    /**
     * Trigger types
     */
    public const TRIGGER_DOWN = 'down';
    public const TRIGGER_UP = 'up';
    public const TRIGGER_DEGRADED = 'degraded';
    public const TRIGGER_ANY = 'any';

    /**
     * All valid trigger types
     */
    public const VALID_TRIGGER_TYPES = [
        self::TRIGGER_DOWN,
        self::TRIGGER_UP,
        self::TRIGGER_DEGRADED,
        self::TRIGGER_ANY,
    ];

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'public_id' => true,
        'organization_id' => true,
        'name' => true,
        'description' => true,
        'trigger_type' => true,
        'repeat_interval_minutes' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'organization' => true,
        'notification_policy_steps' => true,
        'monitors' => true,
    ];

    /**
     * Virtual fields to expose
     *
     * @var array<string>
     */
    protected array $_virtual = ['step_count'];

    /**
     * Get the number of notification policy steps.
     *
     * @return int
     */
    protected function _getStepCount(): int
    {
        if (!isset($this->notification_policy_steps)) {
            return 0;
        }

        return count($this->notification_policy_steps);
    }

    /**
     * Get the number of monitors using this policy.
     *
     * @return int
     */
    public function getMonitorCount(): int
    {
        if (!isset($this->monitors)) {
            return 0;
        }

        return count($this->monitors);
    }

    /**
     * Check if the policy is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Get human-readable trigger type name.
     *
     * @return string
     */
    public function getTriggerTypeName(): string
    {
        return match ($this->trigger_type) {
            self::TRIGGER_DOWN => 'Down',
            self::TRIGGER_UP => 'Up',
            self::TRIGGER_DEGRADED => 'Degraded',
            self::TRIGGER_ANY => 'Any',
            default => ucfirst($this->trigger_type),
        };
    }
}
