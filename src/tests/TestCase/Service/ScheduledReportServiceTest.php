<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ScheduledReportService;
use Cake\TestSuite\TestCase;

/**
 * App\Service\ScheduledReportService Test Case
 *
 * NOTE: The ScheduledReportService uses PostgreSQL-specific raw SQL (::numeric casts).
 * Tests that invoke generateReportData() will fail on SQLite, so we skip them
 * when running on a non-PostgreSQL backend and instead test the service constructor
 * and structure.
 */
class ScheduledReportServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
        'app.Settings',
        'app.SlaDefinitions',
        'app.SlaReports',
    ];

    protected ScheduledReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScheduledReportService();
    }

    protected function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ScheduledReportService::class, $this->service);
    }

    public function testGenerateReportDataReturnsArray(): void
    {
        $driver = $this->getTableLocator()->get('MonitorChecks')
            ->getConnection()
            ->getDriver();

        if (!($driver instanceof \Cake\Database\Driver\Postgres)) {
            $this->markTestSkipped('ScheduledReportService uses PostgreSQL-specific raw SQL');
        }

        $data = $this->service->generateReportData(1, 'weekly');
        $this->assertIsArray($data);
        $this->assertArrayHasKey('organization', $data);
        $this->assertArrayHasKey('period', $data);
    }

    public function testGenerateReportDataMonthlyPeriod(): void
    {
        $driver = $this->getTableLocator()->get('MonitorChecks')
            ->getConnection()
            ->getDriver();

        if (!($driver instanceof \Cake\Database\Driver\Postgres)) {
            $this->markTestSkipped('ScheduledReportService uses PostgreSQL-specific raw SQL');
        }

        $data = $this->service->generateReportData(1, 'monthly');
        $this->assertIsArray($data);
    }
}
