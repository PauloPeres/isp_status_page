<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * OrganizationUser Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property int $user_id
 * @property string $role
 * @property int|null $invited_by
 * @property \Cake\I18n\DateTime|null $invited_at
 * @property \Cake\I18n\DateTime|null $accepted_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\User $user
 */
class OrganizationUser extends Entity
{
    /**
     * Role constants
     */
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'user_id' => true,
        'role' => true,
        'invited_by' => true,
        'invited_at' => true,
        'accepted_at' => true,
        'created' => true,
        'modified' => true,
        'organization' => true,
        'user' => true,
    ];

    /**
     * Check if user has the owner role
     *
     * @return bool
     */
    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Check if user has the admin role
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user has the member role
     *
     * @return bool
     */
    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    /**
     * Check if user has the viewer role
     *
     * @return bool
     */
    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    /**
     * Check if user has admin-level access (owner or admin)
     *
     * @return bool
     */
    public function hasAdminAccess(): bool
    {
        return $this->isOwner() || $this->isAdmin();
    }

    /**
     * Check if user has write access (owner, admin, or member)
     *
     * @return bool
     */
    public function hasWriteAccess(): bool
    {
        return $this->isOwner() || $this->isAdmin() || $this->isMember();
    }
}
