<?php
declare(strict_types=1);

namespace App\Test\TestCase\Tenant;

use App\Tenant\TenantContext;
use Cake\TestSuite\TestCase;

/**
 * Tests for TenantContext static holder.
 */
class TenantContextTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        TenantContext::reset();
    }

    public function testIsSetReturnsFalseByDefault(): void
    {
        TenantContext::reset();
        $this->assertFalse(TenantContext::isSet());
    }

    public function testSetAndGetCurrentOrgId(): void
    {
        TenantContext::setCurrentOrgId(42);
        $this->assertTrue(TenantContext::isSet());
        $this->assertSame(42, TenantContext::getCurrentOrgId());
    }

    public function testGetCurrentOrgIdReturnsNullWhenNotSet(): void
    {
        TenantContext::reset();
        $this->assertNull(TenantContext::getCurrentOrgId());
    }

    public function testSetAndGetCurrentOrganization(): void
    {
        $org = ['id' => 1, 'name' => 'Test Org', 'slug' => 'test-org'];
        TenantContext::setCurrentOrganization($org);
        $this->assertSame($org, TenantContext::getCurrentOrganization());
    }

    public function testGetCurrentOrganizationReturnsNullWhenNotSet(): void
    {
        TenantContext::reset();
        $this->assertNull(TenantContext::getCurrentOrganization());
    }

    public function testResetClearsBothValues(): void
    {
        TenantContext::setCurrentOrgId(10);
        TenantContext::setCurrentOrganization(['id' => 10, 'name' => 'Org']);

        TenantContext::reset();

        $this->assertFalse(TenantContext::isSet());
        $this->assertNull(TenantContext::getCurrentOrgId());
        $this->assertNull(TenantContext::getCurrentOrganization());
    }

    public function testSetCurrentOrgIdToNull(): void
    {
        TenantContext::setCurrentOrgId(5);
        $this->assertTrue(TenantContext::isSet());

        TenantContext::setCurrentOrgId(null);
        $this->assertFalse(TenantContext::isSet());
        $this->assertNull(TenantContext::getCurrentOrgId());
    }

    public function testSetCurrentOrganizationToNull(): void
    {
        TenantContext::setCurrentOrganization(['id' => 1]);
        TenantContext::setCurrentOrganization(null);
        $this->assertNull(TenantContext::getCurrentOrganization());
    }
}
