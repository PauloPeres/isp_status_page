<?php
declare(strict_types=1);

namespace App\Test\TestCase\Security;

use App\Service\PlanService;
use Cake\TestSuite\TestCase;

/**
 * Security regression tests for PlanService (plan limit enforcement).
 *
 * Functional tests that verify plan limits are enforced correctly,
 * preventing users from exceeding their subscription tier.
 */
class PlanEnforcementTest extends TestCase
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
        'app.Plans',
        'app.Monitors',
    ];

    private PlanService $planService;

    public function setUp(): void
    {
        parent::setUp();
        $this->planService = new PlanService();
    }

    /**
     * Verify checkLimit() returns a structured array with required keys:
     * 'allowed', 'current', 'limit', 'plan_name'.
     */
    public function testCheckLimitReturnsStructuredData(): void
    {
        // Organization 1 is on the 'free' plan
        $result = $this->planService->checkLimit(1, 'monitor');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowed', $result, 'checkLimit must return "allowed" key');
        $this->assertArrayHasKey('current', $result, 'checkLimit must return "current" key');
        $this->assertArrayHasKey('limit', $result, 'checkLimit must return "limit" key');
        $this->assertArrayHasKey('plan_name', $result, 'checkLimit must return "plan_name" key');

        $this->assertIsBool($result['allowed']);
        $this->assertIsInt($result['current']);
        $this->assertIsString($result['plan_name']);
    }

    /**
     * Verify checkFeature() returns a structured array with required keys:
     * 'allowed', 'feature', 'plan_name'.
     */
    public function testCheckFeatureReturnsStructuredData(): void
    {
        // Organization 1 is on the 'free' plan
        $result = $this->planService->checkFeature(1, 'email_alerts');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('allowed', $result, 'checkFeature must return "allowed" key');
        $this->assertArrayHasKey('feature', $result, 'checkFeature must return "feature" key');
        $this->assertArrayHasKey('plan_name', $result, 'checkFeature must return "plan_name" key');

        $this->assertIsBool($result['allowed']);
        $this->assertSame('email_alerts', $result['feature']);
        $this->assertIsString($result['plan_name']);
    }

    /**
     * Verify validateCheckInterval clamps a requested interval to the plan minimum.
     * A free plan has check_interval_min=300. Requesting 60 should return 300.
     */
    public function testValidateCheckIntervalClampsToMin(): void
    {
        // Organization 1 is on 'free' plan with check_interval_min=300
        $result = $this->planService->validateCheckInterval(1, 60);

        $this->assertSame(
            300,
            $result,
            'validateCheckInterval must clamp 60s to the free plan minimum of 300s'
        );

        // Requesting a value above the minimum should be returned as-is
        $result = $this->planService->validateCheckInterval(1, 600);
        $this->assertSame(
            600,
            $result,
            'validateCheckInterval must allow intervals above the plan minimum'
        );
    }
}
