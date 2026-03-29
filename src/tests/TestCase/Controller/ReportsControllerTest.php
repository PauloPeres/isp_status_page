<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ReportsController Test Case
 *
 * NOTE: The ReportsController uses PostgreSQL-specific raw SQL (::numeric casts).
 * CSV export tests that trigger raw SQL are skipped on SQLite.
 *
 * @uses \App\Controller\ReportsController
 */
class ReportsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
                'organization_id' => 1,
            ],
            'current_organization_id' => 1,
        ]);
    }

    public function testIndexRequiresAuth(): void
    {
        $this->_session = [];
        $this->cookie('csrfToken', '');

        $this->get('/reports');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexLoadsForAuthenticatedUser(): void
    {
        $this->get('/reports');
        $this->assertResponseOk();
    }

    public function testUptimeReportReturnsCsv(): void
    {
        $driver = $this->getTableLocator()->get('MonitorChecks')
            ->getConnection()
            ->getDriver();

        if (!($driver instanceof \Cake\Database\Driver\Postgres)) {
            $this->markTestSkipped('ReportsController uses PostgreSQL-specific raw SQL');
        }

        $this->get('/reports/uptime-report?range=30');
        $this->assertResponseOk();
        $this->assertContentType('text/csv');
        $this->assertHeaderContains('Content-Disposition', 'uptime_report.csv');
    }

    public function testIncidentReportReturnsCsv(): void
    {
        $driver = $this->getTableLocator()->get('MonitorChecks')
            ->getConnection()
            ->getDriver();

        if (!($driver instanceof \Cake\Database\Driver\Postgres)) {
            $this->markTestSkipped('ReportsController uses PostgreSQL-specific raw SQL');
        }

        $this->get('/reports/incident-report?range=30');
        $this->assertResponseOk();
        $this->assertContentType('text/csv');
    }

    public function testResponseTimeReportReturnsCsv(): void
    {
        $driver = $this->getTableLocator()->get('MonitorChecks')
            ->getConnection()
            ->getDriver();

        if (!($driver instanceof \Cake\Database\Driver\Postgres)) {
            $this->markTestSkipped('ReportsController uses PostgreSQL-specific raw SQL');
        }

        $this->get('/reports/response-time-report?range=30');
        $this->assertResponseOk();
        $this->assertContentType('text/csv');
    }
}
