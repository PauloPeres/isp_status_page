<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\OrganizationUser;

/**
 * Integration Policy
 *
 * Determines what actions a user can perform on integrations.
 * CRUD: owner, admin, or member. View: any role.
 */
class IntegrationPolicy
{
    /**
     * Check if the user can add an integration.
     *
     * @param string $role The user's role in the organization.
     * @return bool
     */
    public function canAdd(string $role): bool
    {
        return in_array($role, [
            OrganizationUser::ROLE_OWNER,
            OrganizationUser::ROLE_ADMIN,
            OrganizationUser::ROLE_MEMBER,
        ], true);
    }

    /**
     * Check if the user can edit an integration.
     *
     * @param string $role The user's role in the organization.
     * @return bool
     */
    public function canEdit(string $role): bool
    {
        return in_array($role, [
            OrganizationUser::ROLE_OWNER,
            OrganizationUser::ROLE_ADMIN,
            OrganizationUser::ROLE_MEMBER,
        ], true);
    }

    /**
     * Check if the user can delete an integration.
     *
     * @param string $role The user's role in the organization.
     * @return bool
     */
    public function canDelete(string $role): bool
    {
        return in_array($role, [
            OrganizationUser::ROLE_OWNER,
            OrganizationUser::ROLE_ADMIN,
            OrganizationUser::ROLE_MEMBER,
        ], true);
    }

    /**
     * Check if the user can view integrations.
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
