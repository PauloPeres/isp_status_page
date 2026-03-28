<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\SuperAdmin;

use App\Service\SuperAdmin\MetricsService;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;

/**
 * MetricsService Test Case
 */
class MetricsServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.Plans',
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
        'app.AlertLogs',
        'app.Users',
        'app.ApiKeys',
        'app.OrganizationUsers',
    ];

    /**
     * @var \App\Service\SuperAdmin\MetricsService
     */
    protected MetricsService $metricsService;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Configure super_admin cache for testing (use array engine)
        if (!Cache::getConfig('super_admin')) {
            Cache::setConfig('super_admin', [
                'className' => 'Cake\Cache\Engine\ArrayEngine',
                'duration' => 300,
                'prefix' => 'sa_',
            ]);
        }

        $this->metricsService = new MetricsService();
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        Cache::clear('super_admin');
        unset($this->metricsService);
        parent::tearDown();
    }

    /**
     * Test getRevenueMetrics returns correct structure
     */
    public function testGetRevenueMetricsStructure(): void
    {
        $result = $this->metricsService->getRevenueMetrics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('mrr', $result);
        $this->assertArrayHasKey('arr', $result);
        $this->assertArrayHasKey('arpu', $result);
        $this->assertArrayHasKey('revenue_by_plan', $result);
        $this->assertArrayHasKey('paid_orgs', $result);
        $this->assertArrayHasKey('total_orgs', $result);

        $this->assertIsNumeric($result['mrr']);
        $this->assertIsNumeric($result['arr']);
        $this->assertIsNumeric($result['arpu']);
        $this->assertIsArray($result['revenue_by_plan']);
        $this->assertIsInt($result['paid_orgs']);
        $this->assertIsInt($result['total_orgs']);
    }

    /**
     * Test ARR equals MRR * 12
     */
    public function testArrEqualsMrrTimes12(): void
    {
        $result = $this->metricsService->getRevenueMetrics();
        $this->assertEquals($result['mrr'] * 12, $result['arr']);
    }

    /**
     * Test revenue_by_plan contains expected keys
     */
    public function testRevenueByPlanKeys(): void
    {
        $result = $this->metricsService->getRevenueMetrics();

        $this->assertArrayHasKey('free', $result['revenue_by_plan']);
        $this->assertArrayHasKey('pro', $result['revenue_by_plan']);
        $this->assertArrayHasKey('business', $result['revenue_by_plan']);
        $this->assertEquals(0, $result['revenue_by_plan']['free']);
    }

    /**
     * Test getGrowthMetrics returns correct structure
     */
    public function testGetGrowthMetricsStructure(): void
    {
        $result = $this->metricsService->getGrowthMetrics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('signups_by_day', $result);
        $this->assertArrayHasKey('total_new', $result);
        $this->assertArrayHasKey('new_this_week', $result);

        $this->assertIsArray($result['signups_by_day']);
        $this->assertIsInt($result['total_new']);
        $this->assertIsInt($result['new_this_week']);
    }

    /**
     * Test getGrowthMetrics accepts custom days parameter
     */
    public function testGetGrowthMetricsCustomDays(): void
    {
        $result7 = $this->metricsService->getGrowthMetrics(7);
        $result30 = $this->metricsService->getGrowthMetrics(30);

        $this->assertIsArray($result7);
        $this->assertIsArray($result30);
        // 30-day window should have at least as many days as 7-day window
        $this->assertGreaterThanOrEqual(count($result7['signups_by_day']), count($result30['signups_by_day']));
    }

    /**
     * Test getTrialMetrics returns correct structure
     */
    public function testGetTrialMetricsStructure(): void
    {
        $result = $this->metricsService->getTrialMetrics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('active_trials', $result);
        $this->assertArrayHasKey('converted', $result);
        $this->assertArrayHasKey('total_trialed', $result);
        $this->assertArrayHasKey('conversion_rate', $result);

        $this->assertIsInt($result['active_trials']);
        $this->assertIsInt($result['converted']);
        $this->assertIsInt($result['total_trialed']);
        $this->assertIsNumeric($result['conversion_rate']);
    }

    /**
     * Test conversion rate is between 0 and 100
     */
    public function testConversionRateBounds(): void
    {
        $result = $this->metricsService->getTrialMetrics();

        $this->assertGreaterThanOrEqual(0, $result['conversion_rate']);
        $this->assertLessThanOrEqual(100, $result['conversion_rate']);
    }

    /**
     * Test getCustomerMetrics returns correct structure
     */
    public function testGetCustomerMetricsStructure(): void
    {
        $result = $this->metricsService->getCustomerMetrics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_active', $result);
        $this->assertArrayHasKey('total_inactive', $result);
        $this->assertArrayHasKey('by_plan', $result);
        $this->assertArrayHasKey('top_by_monitors', $result);
        $this->assertArrayHasKey('recent_signups', $result);

        $this->assertIsInt($result['total_active']);
        $this->assertIsInt($result['total_inactive']);
        $this->assertIsArray($result['by_plan']);
        $this->assertIsArray($result['top_by_monitors']);
        $this->assertIsArray($result['recent_signups']);
    }

    /**
     * Test by_plan contains expected plan keys
     */
    public function testByPlanKeys(): void
    {
        $result = $this->metricsService->getCustomerMetrics();

        $this->assertArrayHasKey('free', $result['by_plan']);
        $this->assertArrayHasKey('pro', $result['by_plan']);
        $this->assertArrayHasKey('business', $result['by_plan']);
    }

    /**
     * Test getPlatformHealthMetrics returns correct structure
     */
    public function testGetPlatformHealthMetricsStructure(): void
    {
        $result = $this->metricsService->getPlatformHealthMetrics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_monitors', $result);
        $this->assertArrayHasKey('active_monitors', $result);
        $this->assertArrayHasKey('checks_today', $result);
        $this->assertArrayHasKey('checks_this_week', $result);
        $this->assertArrayHasKey('checks_this_month', $result);
        $this->assertArrayHasKey('active_incidents', $result);
        $this->assertArrayHasKey('alerts_today', $result);

        foreach ($result as $key => $value) {
            $this->assertIsInt($value, "Expected '{$key}' to be integer");
        }
    }

    /**
     * Test getUserEngagementMetrics returns correct structure
     */
    public function testGetUserEngagementMetricsStructure(): void
    {
        $result = $this->metricsService->getUserEngagementMetrics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dau', $result);
        $this->assertArrayHasKey('wau', $result);
        $this->assertArrayHasKey('mau', $result);
        $this->assertArrayHasKey('total_users', $result);
        $this->assertArrayHasKey('api_adoption_rate', $result);

        $this->assertIsInt($result['dau']);
        $this->assertIsInt($result['wau']);
        $this->assertIsInt($result['mau']);
        $this->assertIsInt($result['total_users']);
        $this->assertIsNumeric($result['api_adoption_rate']);
    }

    /**
     * Test DAU <= WAU <= MAU
     */
    public function testEngagementMetricsOrdering(): void
    {
        $result = $this->metricsService->getUserEngagementMetrics();

        $this->assertLessThanOrEqual($result['wau'], $result['dau']);
        $this->assertLessThanOrEqual($result['mau'], $result['wau']);
    }

    /**
     * Test clearCache does not throw
     */
    public function testClearCache(): void
    {
        // First populate cache
        $this->metricsService->getRevenueMetrics();

        // Clear should not throw
        $this->metricsService->clearCache();
        $this->assertTrue(true); // If we got here, no exception was thrown
    }

    /**
     * Test caching works (second call returns same data)
     */
    public function testCachingReturnsSameData(): void
    {
        $result1 = $this->metricsService->getRevenueMetrics();
        $result2 = $this->metricsService->getRevenueMetrics();

        $this->assertEquals($result1, $result2);
    }
}
