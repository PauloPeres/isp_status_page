<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\OrganizationUser;
use App\Policy\MonitorPolicy;
use Cake\TestSuite\TestCase;

/**
 * MonitorPolicy Test Case
 */
class MonitorPolicyTest extends TestCase
{
    /**
     * @var \App\Policy\MonitorPolicy
     */
    protected MonitorPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new MonitorPolicy();
    }

    protected function tearDown(): void
    {
        unset($this->policy);
        parent::tearDown();
    }

    public function testCanAdd(): void
    {
        $this->assertTrue($this->policy->canAdd(OrganizationUser::ROLE_OWNER));
        $this->assertTrue($this->policy->canAdd(OrganizationUser::ROLE_ADMIN));
        $this->assertTrue($this->policy->canAdd(OrganizationUser::ROLE_MEMBER));
        $this->assertFalse($this->policy->canAdd(OrganizationUser::ROLE_VIEWER));
    }

    public function testCanEdit(): void
    {
        $this->assertTrue($this->policy->canEdit(OrganizationUser::ROLE_OWNER));
        $this->assertTrue($this->policy->canEdit(OrganizationUser::ROLE_ADMIN));
        $this->assertTrue($this->policy->canEdit(OrganizationUser::ROLE_MEMBER));
        $this->assertFalse($this->policy->canEdit(OrganizationUser::ROLE_VIEWER));
    }

    public function testCanDelete(): void
    {
        $this->assertTrue($this->policy->canDelete(OrganizationUser::ROLE_OWNER));
        $this->assertTrue($this->policy->canDelete(OrganizationUser::ROLE_ADMIN));
        $this->assertTrue($this->policy->canDelete(OrganizationUser::ROLE_MEMBER));
        $this->assertFalse($this->policy->canDelete(OrganizationUser::ROLE_VIEWER));
    }

    public function testCanView(): void
    {
        $this->assertTrue($this->policy->canView(OrganizationUser::ROLE_OWNER));
        $this->assertTrue($this->policy->canView(OrganizationUser::ROLE_ADMIN));
        $this->assertTrue($this->policy->canView(OrganizationUser::ROLE_MEMBER));
        $this->assertTrue($this->policy->canView(OrganizationUser::ROLE_VIEWER));
    }
}
