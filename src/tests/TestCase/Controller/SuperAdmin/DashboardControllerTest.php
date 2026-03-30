<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\SuperAdmin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SuperAdmin\DashboardController Test Case
 *
 * @uses \App\Controller\SuperAdmin\DashboardController
 */
class DashboardControllerTest extends TestCase
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
        'app.Plans',
        'app.AlertLogs',
        'app.Subscriptions',
    ];

    /**
     * Test that unauthenticated users are rejected with 403
     *
     * The SuperAdminMiddleware returns 403 when no identity is present
     * (it does not redirect to login like the normal auth flow).
     */
    public function testIndexUnauthenticated(): void
    {
        $this->get('/super-admin');
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

        $this->get('/super-admin');
        $this->assertResponseCode(403);
    }

    /**
     * Test that a super admin can access the dashboard
     */
    public function testIndexSuperAdmin(): void
    {
        $this->markTestSkipped('Legacy web controller — super admin moved to Angular SPA');
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
                'is_super_admin' => true,
            ],
        ]);

        $this->get('/super-admin');
        $this->assertResponseOk();
    }
}
