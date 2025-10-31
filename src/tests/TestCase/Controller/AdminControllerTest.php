<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AdminController Test Case
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
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);
    }

    /**
     * Test index method with authentication
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

        $this->assertResponseOk();
        $this->assertResponseContains('Dashboard');
        $this->assertResponseContains('Monitores Total');
        $this->assertResponseContains('Incidentes Ativos');
        $this->assertResponseContains('Inscritos Total');
        $this->assertResponseContains('Verificações Hoje');
    }

    /**
     * Test that statistics are correctly calculated
     */
    public function testIndexStatistics(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);

        $this->get('/admin');

        $this->assertResponseOk();

        // Check that view variables are set
        $this->assertNotEmpty($this->viewVariable('stats'));
        $stats = $this->viewVariable('stats');

        $this->assertArrayHasKey('monitors', $stats);
        $this->assertArrayHasKey('incidents', $stats);
        $this->assertArrayHasKey('subscribers', $stats);
        $this->assertArrayHasKey('checks', $stats);

        // Check monitors stats structure
        $this->assertArrayHasKey('total', $stats['monitors']);
        $this->assertArrayHasKey('online', $stats['monitors']);
        $this->assertArrayHasKey('offline', $stats['monitors']);

        // Check incidents stats structure
        $this->assertArrayHasKey('active', $stats['incidents']);
        $this->assertArrayHasKey('resolved_today', $stats['incidents']);

        // Check subscribers stats structure
        $this->assertArrayHasKey('total', $stats['subscribers']);
        $this->assertArrayHasKey('active', $stats['subscribers']);

        // Check checks stats structure
        $this->assertArrayHasKey('total_today', $stats['checks']);
        $this->assertArrayHasKey('failed_today', $stats['checks']);
    }

    /**
     * Test that recent monitors are loaded
     */
    public function testIndexRecentMonitors(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);

        $this->get('/admin');

        $this->assertResponseOk();
        $this->assertNotNull($this->viewVariable('recentMonitors'));
    }

    /**
     * Test that recent incidents are loaded
     */
    public function testIndexRecentIncidents(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);

        $this->get('/admin');

        $this->assertResponseOk();
        $this->assertNotNull($this->viewVariable('recentIncidents'));
    }

    /**
     * Test that admin layout is used
     */
    public function testIndexUsesAdminLayout(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);

        $this->get('/admin');

        $this->assertResponseOk();
        $this->assertLayout('admin');
    }
}
