<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\OrganizationUser;

/**
 * AlertRule Policy
 *
 * Determines what actions a user can perform on alert rules.
 * CRUD: owner, admin, or member. View: any role.
 */
class AlertRulePolicy
{
    /**
     * Check if the user can add an alert rule.
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
     * Check if the user can edit an alert rule.
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
     * Check if the user can delete an alert rule.
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
     * Check if the user can view alert rules.
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
