<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * Invitation Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property string $email
 * @property string $role
 * @property string $token
 * @property int $invited_by
 * @property \Cake\I18n\DateTime|null $accepted_at
 * @property \Cake\I18n\DateTime $expires_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\User $inviter
 */
class Invitation extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'email' => true,
        'role' => true,
        'token' => true,
        'invited_by' => true,
        'accepted_at' => true,
        'expires_at' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Check if the invitation has been accepted.
     *
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    /**
     * Check if the invitation has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return true;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the invitation is still pending (not accepted, not expired).
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return !$this->isAccepted() && !$this->isExpired();
    }
}
