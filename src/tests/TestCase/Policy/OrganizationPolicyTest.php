<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\OrganizationUser;
use App\Policy\OrganizationPolicy;
use Cake\TestSuite\TestCase;

/**
 * OrganizationPolicy Test Case
 */
class OrganizationPolicyTest extends TestCase
{
    /**
     * @var \App\Policy\OrganizationPolicy
     */
    protected OrganizationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new OrganizationPolicy();
    }

    protected function tearDown(): void
    {
        unset($this->policy);
        parent::tearDown();
    }

    public function testCanManageBilling(): void
    {
        $this->assertTrue($this->policy->canManageBilling(OrganizationUser::ROLE_OWNER));
        $this->assertFalse($this->policy->canManageBilling(OrganizationUser::ROLE_ADMIN));
        $this->assertFalse($this->policy->canManageBilling(OrganizationUser::ROLE_MEMBER));
        $this->assertFalse($this->policy->canManageBilling(OrganizationUser::ROLE_VIEWER));
    }

    public function testCanDelete(): void
    {
        $this->assertTrue($this->policy->canDelete(OrganizationUser::ROLE_OWNER));
        $this->assertFalse($this->policy->canDelete(OrganizationUser::ROLE_ADMIN));
        $this->assertFalse($this->policy->canDelete(OrganizationUser::ROLE_MEMBER));
        $this->assertFalse($this->policy->canDelete(OrganizationUser::ROLE_VIEWER));
    }

    public function testCanManageTeam(): void
    {
        $this->assertTrue($this->policy->canManageTeam(OrganizationUser::ROLE_OWNER));
        $this->assertTrue($this->policy->canManageTeam(OrganizationUser::ROLE_ADMIN));
        $this->assertFalse($this->policy->canManageTeam(OrganizationUser::ROLE_MEMBER));
        $this->assertFalse($this->policy->canManageTeam(OrganizationUser::ROLE_VIEWER));
    }

    public function testCanManageSettings(): void
    {
        $this->assertTrue($this->policy->canManageSettings(OrganizationUser::ROLE_OWNER));
        $this->assertTrue($this->policy->canManageSettings(OrganizationUser::ROLE_ADMIN));
        $this->assertFalse($this->policy->canManageSettings(OrganizationUser::ROLE_MEMBER));
        $this->assertFalse($this->policy->canManageSettings(OrganizationUser::ROLE_VIEWER));
    }

    public function testCanView(): void
    {
        $this->assertTrue($this->policy->canView(OrganizationUser::ROLE_OWNER));
        $this->assertTrue($this->policy->canView(OrganizationUser::ROLE_ADMIN));
        $this->assertTrue($this->policy->canView(OrganizationUser::ROLE_MEMBER));
        $this->assertTrue($this->policy->canView(OrganizationUser::ROLE_VIEWER));
    }
}
