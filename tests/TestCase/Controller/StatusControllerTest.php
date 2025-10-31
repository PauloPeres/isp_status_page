<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\StatusController Test Case
 *
 * @uses \App\Controller\StatusController
 */
class StatusControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Monitors',
        'app.Incidents',
    ];

    /**
     * Test index method is publicly accessible
     */
    public function testIndexPublicAccess(): void
    {
        $this->get('/status');
        $this->assertResponseOk();
    }

    /**
     * Test index method uses public layout
     */
    public function testIndexUsesPublicLayout(): void
    {
        $this->get('/status');

        $this->assertResponseOk();
        $this->assertLayout('public');
    }

    /**
     * Test index method displays system status
     */
    public function testIndexDisplaysSystemStatus(): void
    {
        $this->get('/status');

        $this->assertResponseOk();
        $this->assertResponseContains('Status dos Serviços');
    }

    /**
     * Test index method with all systems operational
     */
    public function testIndexAllSystemsOperational(): void
    {
        $this->get('/status');

        $this->assertResponseOk();
        $this->assertResponseCode(200);

        $systemStatus = $this->viewVariable('systemStatus');
        $this->assertNotNull($systemStatus);
    }

    /**
     * Test index method sets view variables
     */
    public function testIndexSetsViewVariables(): void
    {
        $this->get('/status');

        $this->assertResponseOk();

        // Check that all required view variables are set
        $this->assertNotNull($this->viewVariable('monitors'));
        $this->assertNotNull($this->viewVariable('systemStatus'));
        $this->assertNotNull($this->viewVariable('systemMessage'));
        $this->assertNotNull($this->viewVariable('systemIcon'));
        $this->assertNotNull($this->viewVariable('totalMonitors'));
        $this->assertNotNull($this->viewVariable('onlineMonitors'));
        $this->assertNotNull($this->viewVariable('offlineMonitors'));
        $this->assertNotNull($this->viewVariable('degradedMonitors'));
        $this->assertNotNull($this->viewVariable('recentIncidents'));
    }

    /**
     * Test index method filters only active monitors
     */
    public function testIndexShowsOnlyActiveMonitors(): void
    {
        $this->get('/status');

        $this->assertResponseOk();

        $monitors = $this->viewVariable('monitors');
        $this->assertNotNull($monitors);

        // All monitors should be active
        foreach ($monitors as $monitor) {
            $this->assertTrue($monitor->active);
        }
    }

    /**
     * Test index method calculates statistics correctly
     */
    public function testIndexCalculatesStatistics(): void
    {
        $this->get('/status');

        $this->assertResponseOk();

        $totalMonitors = $this->viewVariable('totalMonitors');
        $onlineMonitors = $this->viewVariable('onlineMonitors');
        $offlineMonitors = $this->viewVariable('offlineMonitors');
        $degradedMonitors = $this->viewVariable('degradedMonitors');

        // Total should equal sum of online + offline + degraded
        $this->assertEquals(
            $totalMonitors,
            $onlineMonitors + $offlineMonitors + $degradedMonitors
        );
    }

    /**
     * Test history method is publicly accessible
     */
    public function testHistoryPublicAccess(): void
    {
        $this->get('/status/history');
        $this->assertResponseOk();
    }

    /**
     * Test history method uses public layout
     */
    public function testHistoryUsesPublicLayout(): void
    {
        $this->get('/status/history');

        $this->assertResponseOk();
        $this->assertLayout('public');
    }

    /**
     * Test history method sets grouped incidents
     */
    public function testHistorySetsGroupedIncidents(): void
    {
        $this->get('/status/history');

        $this->assertResponseOk();
        $this->assertNotNull($this->viewVariable('groupedIncidents'));
    }

    /**
     * Test HTTP status code for major outage
     *
     * Note: This test would require fixtures with monitors in down state
     * Skipping for now as it requires specific fixture data
     */
    public function testIndexReturns503ForMajorOutage(): void
    {
        $this->markTestSkipped('Requires specific fixture data with monitors in down state');
    }

    /**
     * Test HTTP status code for partial outage
     *
     * Note: This test would require fixtures with some monitors down
     * Skipping for now as it requires specific fixture data
     */
    public function testIndexReturns500ForPartialOutage(): void
    {
        $this->markTestSkipped('Requires specific fixture data with partial outage');
    }
}
