<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\OrganizationUser;
use App\Tenant\TenantContext;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * PermissionService
 *
 * Lightweight authorization service that checks permissions based on
 * the current user's role in the current organization.
 *
 * This is a simpler approach than the full cakephp/authorization plugin.
 * It uses the OrganizationUser role constants and the permission matrix:
 *
 * | Action              | Owner | Admin | Member | Viewer |
 * |---------------------|-------|-------|--------|--------|
 * | Manage billing      | Yes   | No    | No     | No     |
 * | Delete org          | Yes   | No    | No     | No     |
 * | Manage team         | Yes   | Yes   | No     | No     |
 * | CRUD monitors       | Yes   | Yes   | Yes    | No     |
 * | CRUD integrations   | Yes   | Yes   | Yes    | No     |
 * | CRUD alert rules    | Yes   | Yes   | Yes    | No     |
 * | Manage settings     | Yes   | Yes   | No     | No     |
 * | View dashboard/data | Yes   | Yes   | Yes    | Yes    |
 * | View status page    | Yes   | Yes   | Yes    | Yes    |
 */
class PermissionService
{
    use LocatorAwareTrait;

    /**
     * Permission action constants
     */
    public const ACTION_MANAGE_BILLING = 'manage_billing';
    public const ACTION_DELETE_ORG = 'delete_org';
    public const ACTION_MANAGE_TEAM = 'manage_team';
    public const ACTION_MANAGE_RESOURCES = 'manage_resources';
    public const ACTION_MANAGE_SETTINGS = 'manage_settings';
    public const ACTION_VIEW = 'view';

    /**
     * Permission matrix: action => allowed roles
     *
     * @var array<string, array<string>>
     */
    private array $permissions = [
        self::ACTION_MANAGE_BILLING => [
            OrganizationUser::ROLE_OWNER,
        ],
        self::ACTION_DELETE_ORG => [
            OrganizationUser::ROLE_OWNER,
        ],
        self::ACTION_MANAGE_TEAM => [
            OrganizationUser::ROLE_OWNER,
            OrganizationUser::ROLE_ADMIN,
        ],
        self::ACTION_MANAGE_SETTINGS => [
            OrganizationUser::ROLE_OWNER,
            OrganizationUser::ROLE_ADMIN,
        ],
        self::ACTION_MANAGE_RESOURCES => [
            OrganizationUser::ROLE_OWNER,
            OrganizationUser::ROLE_ADMIN,
            OrganizationUser::ROLE_MEMBER,
        ],
        self::ACTION_VIEW => [
            OrganizationUser::ROLE_OWNER,
            OrganizationUser::ROLE_ADMIN,
            OrganizationUser::ROLE_MEMBER,
            OrganizationUser::ROLE_VIEWER,
        ],
    ];

    /**
     * Get the user's role in the current organization.
     *
     * @param int $userId The user ID.
     * @param int|null $orgId The organization ID (defaults to current tenant).
     * @return string|null The role, or null if not a member.
     */
    public function getUserRole(int $userId, ?int $orgId = null): ?string
    {
        if ($orgId === null) {
            $orgId = TenantContext::getCurrentOrgId();
        }

        if ($orgId === null) {
            return null;
        }

        $orgUsersTable = $this->fetchTable('OrganizationUsers');

        $orgUser = $orgUsersTable->find()
            ->where([
                'OrganizationUsers.organization_id' => $orgId,
                'OrganizationUsers.user_id' => $userId,
            ])
            ->select(['OrganizationUsers.role'])
            ->disableHydration()
            ->first();

        return $orgUser ? $orgUser['role'] : null;
    }

    /**
     * Check if a user can perform an action in the current organization.
     *
     * @param int $userId The user ID.
     * @param string $action The action to check (use ACTION_* constants).
     * @param int|null $orgId The organization ID (defaults to current tenant).
     * @return bool
     */
    public function can(int $userId, string $action, ?int $orgId = null): bool
    {
        if (!isset($this->permissions[$action])) {
            return false;
        }

        $role = $this->getUserRole($userId, $orgId);

        if ($role === null) {
            return false;
        }

        return in_array($role, $this->permissions[$action], true);
    }

    /**
     * Check if a user can perform an action based on a known role.
     * Useful when the role has already been loaded (e.g., in AppController).
     *
     * @param string $role The user's role.
     * @param string $action The action to check (use ACTION_* constants).
     * @return bool
     */
    public function canWithRole(string $role, string $action): bool
    {
        if (!isset($this->permissions[$action])) {
            return false;
        }

        return in_array($role, $this->permissions[$action], true);
    }

    /**
     * Check if the user can manage billing (owner only).
     *
     * @param int $userId The user ID.
     * @param int|null $orgId The organization ID (defaults to current tenant).
     * @return bool
     */
    public function canManageBilling(int $userId, ?int $orgId = null): bool
    {
        return $this->can($userId, self::ACTION_MANAGE_BILLING, $orgId);
    }

    /**
     * Check if the user can delete the organization (owner only).
     *
     * @param int $userId The user ID.
     * @param int|null $orgId The organization ID (defaults to current tenant).
     * @return bool
     */
    public function canDeleteOrg(int $userId, ?int $orgId = null): bool
    {
        return $this->can($userId, self::ACTION_DELETE_ORG, $orgId);
    }

    /**
     * Check if the user can manage team members (owner or admin).
     *
     * @param int $userId The user ID.
     * @param int|null $orgId The organization ID (defaults to current tenant).
     * @return bool
     */
    public function canManageTeam(int $userId, ?int $orgId = null): bool
    {
        return $this->can($userId, self::ACTION_MANAGE_TEAM, $orgId);
    }

    /**
     * Check if the user can manage settings (owner or admin).
     *
     * @param int $userId The user ID.
     * @param int|null $orgId The organization ID (defaults to current tenant).
     * @return bool
     */
    public function canManageSettings(int $userId, ?int $orgId = null): bool
    {
        return $this->can($userId, self::ACTION_MANAGE_SETTINGS, $orgId);
    }

    /**
     * Check if the user can manage resources - monitors, integrations, alert rules
     * (owner, admin, or member).
     *
     * @param int $userId The user ID.
     * @param int|null $orgId The organization ID (defaults to current tenant).
     * @return bool
     */
    public function canManageResources(int $userId, ?int $orgId = null): bool
    {
        return $this->can($userId, self::ACTION_MANAGE_RESOURCES, $orgId);
    }

    /**
     * Check if the user can view dashboard and data (any role).
     *
     * @param int $userId The user ID.
     * @param int|null $orgId The organization ID (defaults to current tenant).
     * @return bool
     */
    public function canView(int $userId, ?int $orgId = null): bool
    {
        return $this->can($userId, self::ACTION_VIEW, $orgId);
    }

    /**
     * Get all permissions for a given role.
     *
     * @param string $role The role to check.
     * @return array<string, bool> Map of action => allowed.
     */
    public function getPermissionsForRole(string $role): array
    {
        $result = [];

        foreach ($this->permissions as $action => $allowedRoles) {
            $result[$action] = in_array($role, $allowedRoles, true);
        }

        return $result;
    }
}
