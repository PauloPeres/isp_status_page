<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SecurityAuditLog Entity (TASK-AUTH-018)
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $organization_id
 * @property string $event_type
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $details
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\User|null $user
 * @property \App\Model\Entity\Organization|null $organization
 */
class SecurityAuditLog extends Entity
{
    /**
     * Fields that can be mass assigned.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'organization_id' => true,
        'event_type' => true,
        'ip_address' => true,
        'user_agent' => true,
        'details' => true,
        'created' => true,
    ];
}
