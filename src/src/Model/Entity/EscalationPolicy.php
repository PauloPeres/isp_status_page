<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * EscalationPolicy Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $description
 * @property bool $repeat_enabled
 * @property int $repeat_after_minutes
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\EscalationStep[] $escalation_steps
 * @property \App\Model\Entity\Monitor[] $monitors
 */
class EscalationPolicy extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'name' => true,
        'description' => true,
        'repeat_enabled' => true,
        'repeat_after_minutes' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'organization' => true,
        'escalation_steps' => true,
        'monitors' => true,
    ];

    /**
     * Get the number of escalation steps.
     *
     * @return int
     */
    public function getStepCount(): int
    {
        if (!isset($this->escalation_steps)) {
            return 0;
        }

        return count($this->escalation_steps);
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
}
