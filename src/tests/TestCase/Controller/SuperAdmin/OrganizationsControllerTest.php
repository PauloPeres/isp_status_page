<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\SuperAdmin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SuperAdmin\OrganizationsController Test Case
 *
 * @uses \App\Controller\SuperAdmin\OrganizationsController
 */
class OrganizationsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
    ];

    /**
     * Test that unauthenticated users are rejected with 403
     */
    public function testIndexUnauthenticated(): void
    {
        $this->get('/super-admin/organizations');
        $this->assertResponseCode(403);
    }

    /**
     * Test that a non-super-admin user gets 403
     */
    public function testIndexRequiresSuperAdmin(): void
    {
        $this->session([
            'Auth' => [
                'id' => 2,
                'username' => 'user',
                'active' => true,
            ],
        ]);

        $this->get('/super-admin/organizations');
        $this->assertResponseCode(403);
    }

    /**
     * Test that a non-super-admin user cannot view an organization
     */
    public function testViewRequiresSuperAdmin(): void
    {
        $this->session([
            'Auth' => [
                'id' => 2,
                'username' => 'user',
                'active' => true,
            ],
        ]);

        $this->get('/super-admin/organizations/1');
        $this->assertResponseCode(403);
    }
}
