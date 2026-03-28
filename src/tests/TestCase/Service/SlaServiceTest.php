<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\SlaService;
use Cake\TestSuite\TestCase;

/**
 * App\Service\SlaService Test Case
 */
class SlaServiceTest extends TestCase
{
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
        'app.SlaDefinitions',
        'app.SlaReports',
    ];

    /**
     * @var \App\Service\SlaService
     */
    protected SlaService $slaService;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->slaService = new SlaService();
    }

    /**
     * Test calculateCurrentSla returns an array with expected keys
     */
    public function testCalculateCurrentSlaReturnsArray(): void
    {
        $result = $this->slaService->calculateCurrentSla(1, 'monthly', 99.9);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('target_uptime', $result);
        $this->assertArrayHasKey('actual_uptime', $result);
        $this->assertArrayHasKey('total_minutes', $result);
        $this->assertArrayHasKey('downtime_minutes', $result);
        $this->assertArrayHasKey('allowed_downtime_minutes', $result);
        $this->assertArrayHasKey('remaining_downtime_minutes', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('incidents_count', $result);
        $this->assertArrayHasKey('period_start', $result);
        $this->assertArrayHasKey('period_end', $result);
    }

    /**
     * Test determineStatus returns compliant when uptime exceeds warning threshold
     */
    public function testDetermineStatusCompliant(): void
    {
        $status = $this->slaService->determineStatus(99.99, 99.9, 99.95);
        $this->assertSame('compliant', $status);
    }

    /**
     * Test determineStatus returns at_risk when uptime is between target and warning
     */
    public function testDetermineStatusAtRisk(): void
    {
        $status = $this->slaService->determineStatus(99.92, 99.9, 99.95);
        $this->assertSame('at_risk', $status);
    }

    /**
     * Test determineStatus returns breached when uptime is below target
     */
    public function testDetermineStatusBreached(): void
    {
        $status = $this->slaService->determineStatus(99.5, 99.9, 99.95);
        $this->assertSame('breached', $status);
    }

    /**
     * Test getPeriodDates returns correct structure for monthly period
     */
    public function testGetPeriodDatesMonthly(): void
    {
        $result = $this->slaService->getPeriodDates('monthly');

        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('end', $result);

        // Start should be the first day of the current month
        $this->assertSame('01', $result['start']->format('d'));

        // End should be the last day of the current month
        $expectedEnd = date('t'); // last day of current month
        $this->assertSame($expectedEnd, $result['end']->format('d'));
    }
}
