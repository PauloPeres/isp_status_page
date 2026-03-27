<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Organization;
use App\Model\Entity\OrganizationUser;

/**
 * Organization Policy
 *
 * Determines what actions a user can perform on an organization
 * based on their role in that organization.
 */
class OrganizationPolicy
{
    /**
     * Check if the user can manage billing (owner only).
     *
     * @param string $role The user's role in the organization.
     * @return bool
     */
    public function canManageBilling(string $role): bool
    {
        return $role === OrganizationUser::ROLE_OWNER;
    }

    /**
     * Check if the user can delete the organization (owner only).
     *
     * @param string $role The user's role in the organization.
     * @return bool
     */
    public function canDelete(string $role): bool
    {
        return $role === OrganizationUser::ROLE_OWNER;
    }

    /**
     * Check if the user can manage team members (owner or admin).
     *
     * @param string $role The user's role in the organization.
     * @return bool
     */
    public function canManageTeam(string $role): bool
    {
        return in_array($role, [
            OrganizationUser::ROLE_OWNER,
            OrganizationUser::ROLE_ADMIN,
        ], true);
    }

    /**
     * Check if the user can manage settings (owner or admin).
     *
     * @param string $role The user's role in the organization.
     * @return bool
     */
    public function canManageSettings(string $role): bool
    {
        return in_array($role, [
            OrganizationUser::ROLE_OWNER,
            OrganizationUser::ROLE_ADMIN,
        ], true);
    }

    /**
     * Check if the user can view the organization (any role).
     *
     * @param string $role The user's role in the organization.
     * @return bool
     */
    public function canView(string $role): bool
    {
        return in_array($role, [
            OrganizationUser::ROLE_OWNER,
            OrganizationUser::ROLE_ADMIN,
            OrganizationUser::ROLE_MEMBER,
            OrganizationUser::ROLE_VIEWER,
        ], true);
    }
}
