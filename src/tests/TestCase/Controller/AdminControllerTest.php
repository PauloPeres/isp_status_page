<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AdminController Test Case
 *
 * AdminController now redirects to DashboardController.
 *
 * @uses \App\Controller\AdminController
 */
class AdminControllerTest extends TestCase
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
        'app.Incidents',
        'app.Subscribers',
        'app.MonitorChecks',
    ];

    /**
     * Test index method requires authentication
     */
    public function testIndexUnauthenticated(): void
    {
        $this->get('/admin');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test index method redirects to dashboard when authenticated
     */
    public function testIndexAuthenticated(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);

        $this->get('/admin');

        $this->assertRedirectContains('/dashboard');
    }
}
