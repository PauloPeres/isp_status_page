<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\OrganizationUser;
use App\Service\PermissionService;
use App\Tenant\TenantContext;
use Cake\TestSuite\TestCase;

/**
 * PermissionService Test Case
 *
 * Tests the RBAC permission matrix:
 * | Action              | Owner | Admin | Member | Viewer |
 * |---------------------|-------|-------|--------|--------|
 * | Manage billing      | Yes   | No    | No     | No     |
 * | Delete org          | Yes   | No    | No     | No     |
 * | Manage team         | Yes   | Yes   | No     | No     |
 * | Manage resources    | Yes   | Yes   | Yes    | No     |
 * | Manage settings     | Yes   | Yes   | No     | No     |
 * | View                | Yes   | Yes   | Yes    | Yes    |
 */
class PermissionServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
    ];

    /**
     * @var \App\Service\PermissionService
     */
    protected PermissionService $permissionService;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionService = new PermissionService();
        // Set tenant context to org 1 for most tests
        TenantContext::setCurrentOrgId(1);
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        TenantContext::reset();
        unset($this->permissionService);
        parent::tearDown();
    }

    /**
     * Test getUserRole returns the correct role for each user.
     */
    public function testGetUserRole(): void
    {
        // User 1 is owner in org 1
        $this->assertSame('owner', $this->permissionService->getUserRole(1, 1));

        // User 1 is admin in org 2
        $this->assertSame('admin', $this->permissionService->getUserRole(1, 2));

        // User 2 is member in org 1
        $this->assertSame('member', $this->permissionService->getUserRole(2, 1));

        // User 3 is viewer in org 1
        $this->assertSame('viewer', $this->permissionService->getUserRole(3, 1));
    }

    /**
     * Test getUserRole returns null for non-member.
     */
    public function testGetUserRoleReturnsNullForNonMember(): void
    {
        // User 2 is not a member of org 2
        $this->assertNull($this->permissionService->getUserRole(2, 2));

        // Non-existent user
        $this->assertNull($this->permissionService->getUserRole(999, 1));
    }

    /**
     * Test getUserRole uses TenantContext when orgId is null.
     */
    public function testGetUserRoleUsesTenantContext(): void
    {
        TenantContext::setCurrentOrgId(1);
        $this->assertSame('owner', $this->permissionService->getUserRole(1));
    }

    /**
     * Test getUserRole returns null when no tenant context and no orgId.
     */
    public function testGetUserRoleReturnsNullWithoutContext(): void
    {
        TenantContext::reset();
        $this->assertNull($this->permissionService->getUserRole(1));
    }

    /**
     * Test owner can do everything.
     */
    public function testOwnerCanDoEverything(): void
    {
        // User 1 is owner in org 1
        $this->assertTrue($this->permissionService->canManageBilling(1, 1));
        $this->assertTrue($this->permissionService->canDeleteOrg(1, 1));
        $this->assertTrue($this->permissionService->canManageTeam(1, 1));
        $this->assertTrue($this->permissionService->canManageSettings(1, 1));
        $this->assertTrue($this->permissionService->canManageResources(1, 1));
        $this->assertTrue($this->permissionService->canView(1, 1));
    }

    /**
     * Test admin can manage team, settings, resources, and view, but not billing/delete.
     */
    public function testAdminPermissions(): void
    {
        // User 1 is admin in org 2
        $this->assertFalse($this->permissionService->canManageBilling(1, 2));
        $this->assertFalse($this->permissionService->canDeleteOrg(1, 2));
        $this->assertTrue($this->permissionService->canManageTeam(1, 2));
        $this->assertTrue($this->permissionService->canManageSettings(1, 2));
        $this->assertTrue($this->permissionService->canManageResources(1, 2));
        $this->assertTrue($this->permissionService->canView(1, 2));
    }

    /**
     * Test member can manage resources and view, but not team/settings/billing.
     */
    public function testMemberPermissions(): void
    {
        // User 2 is member in org 1
        $this->assertFalse($this->permissionService->canManageBilling(2, 1));
        $this->assertFalse($this->permissionService->canDeleteOrg(2, 1));
        $this->assertFalse($this->permissionService->canManageTeam(2, 1));
        $this->assertFalse($this->permissionService->canManageSettings(2, 1));
        $this->assertTrue($this->permissionService->canManageResources(2, 1));
        $this->assertTrue($this->permissionService->canView(2, 1));
    }

    /**
     * Test viewer can only view.
     */
    public function testViewerCanOnlyView(): void
    {
        // User 3 is viewer in org 1
        $this->assertFalse($this->permissionService->canManageBilling(3, 1));
        $this->assertFalse($this->permissionService->canDeleteOrg(3, 1));
        $this->assertFalse($this->permissionService->canManageTeam(3, 1));
        $this->assertFalse($this->permissionService->canManageSettings(3, 1));
        $this->assertFalse($this->permissionService->canManageResources(3, 1));
        $this->assertTrue($this->permissionService->canView(3, 1));
    }

    /**
     * Test viewer cannot create monitors (manage resources).
     */
    public function testViewerCannotCreateMonitors(): void
    {
        $this->assertFalse(
            $this->permissionService->canManageResources(3, 1),
            'Viewer should not be able to create/edit/delete monitors'
        );
    }

    /**
     * Test member can create monitors but not manage settings.
     */
    public function testMemberCanCreateButNotManageSettings(): void
    {
        $this->assertTrue(
            $this->permissionService->canManageResources(2, 1),
            'Member should be able to create monitors'
        );
        $this->assertFalse(
            $this->permissionService->canManageSettings(2, 1),
            'Member should not be able to manage settings'
        );
    }

    /**
     * Test canWithRole works correctly with role string.
     */
    public function testCanWithRole(): void
    {
        // Owner
        $this->assertTrue($this->permissionService->canWithRole('owner', PermissionService::ACTION_MANAGE_BILLING));
        $this->assertTrue($this->permissionService->canWithRole('owner', PermissionService::ACTION_VIEW));

        // Admin
        $this->assertFalse($this->permissionService->canWithRole('admin', PermissionService::ACTION_MANAGE_BILLING));
        $this->assertTrue($this->permissionService->canWithRole('admin', PermissionService::ACTION_MANAGE_TEAM));

        // Member
        $this->assertFalse($this->permissionService->canWithRole('member', PermissionService::ACTION_MANAGE_TEAM));
        $this->assertTrue($this->permissionService->canWithRole('member', PermissionService::ACTION_MANAGE_RESOURCES));

        // Viewer
        $this->assertFalse($this->permissionService->canWithRole('viewer', PermissionService::ACTION_MANAGE_RESOURCES));
        $this->assertTrue($this->permissionService->canWithRole('viewer', PermissionService::ACTION_VIEW));
    }

    /**
     * Test canWithRole returns false for unknown action.
     */
    public function testCanWithRoleReturnsFalseForUnknownAction(): void
    {
        $this->assertFalse($this->permissionService->canWithRole('owner', 'nonexistent_action'));
    }

    /**
     * Test can returns false for non-member user.
     */
    public function testCanReturnsFalseForNonMember(): void
    {
        // User 2 is not a member of org 2
        $this->assertFalse($this->permissionService->can(2, PermissionService::ACTION_VIEW, 2));
    }

    /**
     * Test getPermissionsForRole returns correct permission map.
     */
    public function testGetPermissionsForRole(): void
    {
        $ownerPerms = $this->permissionService->getPermissionsForRole('owner');
        $this->assertTrue($ownerPerms[PermissionService::ACTION_MANAGE_BILLING]);
        $this->assertTrue($ownerPerms[PermissionService::ACTION_DELETE_ORG]);
        $this->assertTrue($ownerPerms[PermissionService::ACTION_MANAGE_TEAM]);
        $this->assertTrue($ownerPerms[PermissionService::ACTION_MANAGE_SETTINGS]);
        $this->assertTrue($ownerPerms[PermissionService::ACTION_MANAGE_RESOURCES]);
        $this->assertTrue($ownerPerms[PermissionService::ACTION_VIEW]);

        $viewerPerms = $this->permissionService->getPermissionsForRole('viewer');
        $this->assertFalse($viewerPerms[PermissionService::ACTION_MANAGE_BILLING]);
        $this->assertFalse($viewerPerms[PermissionService::ACTION_DELETE_ORG]);
        $this->assertFalse($viewerPerms[PermissionService::ACTION_MANAGE_TEAM]);
        $this->assertFalse($viewerPerms[PermissionService::ACTION_MANAGE_SETTINGS]);
        $this->assertFalse($viewerPerms[PermissionService::ACTION_MANAGE_RESOURCES]);
        $this->assertTrue($viewerPerms[PermissionService::ACTION_VIEW]);
    }
}
