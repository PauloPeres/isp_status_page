<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Model\Entity\Plan;
use App\Service\PlanService;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * PlanService Test Case
 */
class PlanServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Plans',
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Monitors',
    ];

    /**
     * @var \App\Service\PlanService
     */
    protected PlanService $planService;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->planService = new PlanService();
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        unset($this->planService);
        parent::tearDown();
    }

    /**
     * Test getPlanForOrganization returns the correct plan
     */
    public function testGetPlanForOrganizationReturnsPlan(): void
    {
        // Organization 1 (Acme ISP) is on the 'free' plan
        $plan = $this->planService->getPlanForOrganization(1);

        $this->assertInstanceOf(Plan::class, $plan);
        $this->assertEquals('free', $plan->slug);
        $this->assertEquals('Free', $plan->name);
    }

    /**
     * Test getPlanForOrganization returns pro plan for org 2
     */
    public function testGetPlanForOrganizationReturnsProPlan(): void
    {
        // Organization 2 (Pro Networks) is on the 'pro' plan
        $plan = $this->planService->getPlanForOrganization(2);

        $this->assertInstanceOf(Plan::class, $plan);
        $this->assertEquals('pro', $plan->slug);
        $this->assertEquals('Pro', $plan->name);
    }

    /**
     * Test getPlanForOrganization throws for nonexistent org
     */
    public function testGetPlanForOrganizationThrowsForInvalidOrg(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Organization with ID 9999 not found');

        $this->planService->getPlanForOrganization(9999);
    }

    /**
     * Test free plan cannot add monitors beyond limit
     *
     * Org 1 is on free plan (monitor_limit=1) and already has 3 monitors in fixtures
     */
    public function testFreeOrgCannotAddMonitorBeyondLimit(): void
    {
        // Org 1 has 3 monitors in the fixture, free plan allows 1
        $this->assertFalse($this->planService->canAddMonitor(1));
    }

    /**
     * Test pro plan can add monitors when under limit
     *
     * Org 2 is on pro plan (monitor_limit=50) and has 0 monitors in fixtures
     */
    public function testProOrgCanAddMonitorUnderLimit(): void
    {
        // Org 2 (pro) has 0 monitors, pro plan allows 50
        $this->assertTrue($this->planService->canAddMonitor(2));
    }

    /**
     * Test canAddTeamMember for free plan
     *
     * Org 1 has 2 organization_users in fixture, free plan allows 1
     */
    public function testFreeOrgCannotAddTeamMemberBeyondLimit(): void
    {
        // Org 1 has 2 members (user 1 as owner, user 2 as member), free allows 1
        $this->assertFalse($this->planService->canAddTeamMember(1));
    }

    /**
     * Test canAddTeamMember for pro plan
     *
     * Org 2 has 1 organization_user in fixture, pro plan allows 5
     */
    public function testProOrgCanAddTeamMemberUnderLimit(): void
    {
        // Org 2 has 1 member, pro plan allows 5
        $this->assertTrue($this->planService->canAddTeamMember(2));
    }

    /**
     * Test canUseFeature for free plan — email_alerts allowed
     */
    public function testFreeOrgCanUseEmailAlerts(): void
    {
        $this->assertTrue($this->planService->canUseFeature(1, 'email_alerts'));
    }

    /**
     * Test canUseFeature for free plan — slack_alerts not allowed
     */
    public function testFreeOrgCannotUseSlackAlerts(): void
    {
        $this->assertFalse($this->planService->canUseFeature(1, 'slack_alerts'));
    }

    /**
     * Test canUseFeature for free plan — api_access not allowed
     */
    public function testFreeOrgCannotUseApiAccess(): void
    {
        $this->assertFalse($this->planService->canUseFeature(1, 'api_access'));
    }

    /**
     * Test canUseFeature for pro plan — slack_alerts allowed
     */
    public function testProOrgCanUseSlackAlerts(): void
    {
        $this->assertTrue($this->planService->canUseFeature(2, 'slack_alerts'));
    }

    /**
     * Test canUseFeature for pro plan — api_access allowed
     */
    public function testProOrgCanUseApiAccess(): void
    {
        $this->assertTrue($this->planService->canUseFeature(2, 'api_access'));
    }

    /**
     * Test canUseFeature for pro plan — sms_alerts not allowed
     */
    public function testProOrgCannotUseSmsAlerts(): void
    {
        $this->assertFalse($this->planService->canUseFeature(2, 'sms_alerts'));
    }

    /**
     * Test getMinCheckInterval returns correct value per plan
     */
    public function testGetMinCheckIntervalForFreePlan(): void
    {
        // Free plan: 300 seconds (5 minutes)
        $this->assertEquals(300, $this->planService->getMinCheckInterval(1));
    }

    /**
     * Test getMinCheckInterval for pro plan
     */
    public function testGetMinCheckIntervalForProPlan(): void
    {
        // Pro plan: 60 seconds (1 minute)
        $this->assertEquals(60, $this->planService->getMinCheckInterval(2));
    }

    /**
     * Test enforceLimit throws when monitor limit reached
     */
    public function testEnforceLimitThrowsWhenMonitorLimitReached(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Plan limit reached');

        // Org 1 is on free (limit=1) with 3 monitors
        $this->planService->enforceLimit(1, 'monitor');
    }

    /**
     * Test enforceLimit does not throw when under limit
     */
    public function testEnforceLimitPassesWhenUnderLimit(): void
    {
        // Org 2 is on pro (limit=50) with 0 monitors — should not throw
        $this->planService->enforceLimit(2, 'monitor');
        $this->assertTrue(true); // No exception means success
    }

    /**
     * Test enforceLimit throws for team_member when over limit
     */
    public function testEnforceLimitThrowsWhenTeamMemberLimitReached(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Plan limit reached');

        // Org 1 is on free (team_member_limit=1) with 2 org users
        $this->planService->enforceLimit(1, 'team_member');
    }

    /**
     * Test enforceLimit throws for unknown limit type
     */
    public function testEnforceLimitThrowsForUnknownType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown limit type: invalid_type');

        $this->planService->enforceLimit(1, 'invalid_type');
    }

    /**
     * Test unlimited plans (Business plan with -1 limits)
     *
     * We need to create a business org to test this.
     */
    public function testUnlimitedPlanAllowsAnyNumberOfMonitors(): void
    {
        // Create a business org
        $orgsTable = $this->getTableLocator()->get('Organizations');
        $businessOrg = $orgsTable->newEntity([
            'name' => 'Business Corp',
            'slug' => 'business-corp',
            'plan' => 'business',
            'timezone' => 'UTC',
            'language' => 'en',
            'active' => true,
        ]);
        $orgsTable->saveOrFail($businessOrg);

        $this->assertTrue($this->planService->canAddMonitor($businessOrg->id));
    }

    /**
     * Test unlimited team members for business plan
     */
    public function testUnlimitedPlanAllowsAnyNumberOfTeamMembers(): void
    {
        $orgsTable = $this->getTableLocator()->get('Organizations');
        $businessOrg = $orgsTable->newEntity([
            'name' => 'Business Corp 2',
            'slug' => 'business-corp-2',
            'plan' => 'business',
            'timezone' => 'UTC',
            'language' => 'en',
            'active' => true,
        ]);
        $orgsTable->saveOrFail($businessOrg);

        $this->assertTrue($this->planService->canAddTeamMember($businessOrg->id));
    }

    /**
     * Test business plan has all premium features
     */
    public function testBusinessPlanHasAllPremiumFeatures(): void
    {
        $orgsTable = $this->getTableLocator()->get('Organizations');
        $businessOrg = $orgsTable->newEntity([
            'name' => 'Business Corp 3',
            'slug' => 'business-corp-3',
            'plan' => 'business',
            'timezone' => 'UTC',
            'language' => 'en',
            'active' => true,
        ]);
        $orgsTable->saveOrFail($businessOrg);

        $orgId = $businessOrg->id;

        $this->assertTrue($this->planService->canUseFeature($orgId, 'email_alerts'));
        $this->assertTrue($this->planService->canUseFeature($orgId, 'slack_alerts'));
        $this->assertTrue($this->planService->canUseFeature($orgId, 'sms_alerts'));
        $this->assertTrue($this->planService->canUseFeature($orgId, 'phone_alerts'));
        $this->assertTrue($this->planService->canUseFeature($orgId, 'custom_domain'));
        $this->assertTrue($this->planService->canUseFeature($orgId, 'multi_region'));
        $this->assertTrue($this->planService->canUseFeature($orgId, 'priority_support'));
    }

    /**
     * Test Plan entity is_free virtual field
     */
    public function testPlanEntityIsFreeVirtualField(): void
    {
        $plansTable = $this->getTableLocator()->get('Plans');

        $freePlan = $plansTable->find('bySlug', slug: 'free')->first();
        $this->assertTrue($freePlan->is_free);

        $proPlan = $plansTable->find('bySlug', slug: 'pro')->first();
        $this->assertFalse($proPlan->is_free);
    }

    /**
     * Test Plan entity price formatting
     */
    public function testPlanEntityPriceFormatting(): void
    {
        $plansTable = $this->getTableLocator()->get('Plans');

        $freePlan = $plansTable->find('bySlug', slug: 'free')->first();
        $this->assertEquals('$0.00', $freePlan->getMonthlyPriceFormatted());
        $this->assertEquals('$0.00', $freePlan->getYearlyPriceFormatted());

        $proPlan = $plansTable->find('bySlug', slug: 'pro')->first();
        $this->assertEquals('$15.00', $proPlan->getMonthlyPriceFormatted());
        $this->assertEquals('$144.00', $proPlan->getYearlyPriceFormatted());
    }

    /**
     * Test Plan entity isUnlimited
     */
    public function testPlanEntityIsUnlimited(): void
    {
        $plansTable = $this->getTableLocator()->get('Plans');

        $freePlan = $plansTable->find('bySlug', slug: 'free')->first();
        $this->assertFalse($freePlan->isUnlimited('monitor_limit'));
        $this->assertFalse($freePlan->isUnlimited('team_member_limit'));

        $businessPlan = $plansTable->find('bySlug', slug: 'business')->first();
        $this->assertTrue($businessPlan->isUnlimited('monitor_limit'));
        $this->assertTrue($businessPlan->isUnlimited('team_member_limit'));
    }

    /**
     * Test Plan entity hasFeature
     */
    public function testPlanEntityHasFeature(): void
    {
        $plansTable = $this->getTableLocator()->get('Plans');

        $freePlan = $plansTable->find('bySlug', slug: 'free')->first();
        $this->assertTrue($freePlan->hasFeature('email_alerts'));
        $this->assertFalse($freePlan->hasFeature('slack_alerts'));

        $proPlan = $plansTable->find('bySlug', slug: 'pro')->first();
        $this->assertTrue($proPlan->hasFeature('slack_alerts'));
        $this->assertTrue($proPlan->hasFeature('api_access'));
    }

    /**
     * Test findActive finder returns only active plans in order
     */
    public function testFindActiveReturnsActivePlansInOrder(): void
    {
        $plansTable = $this->getTableLocator()->get('Plans');
        $activePlans = $plansTable->find('active')->all()->toArray();

        $this->assertCount(3, $activePlans);
        $this->assertEquals('Free', $activePlans[0]->name);
        $this->assertEquals('Pro', $activePlans[1]->name);
        $this->assertEquals('Business', $activePlans[2]->name);
    }

    /**
     * Test clearCache works correctly
     */
    public function testClearCacheForcesFreshLookup(): void
    {
        // First call caches
        $plan1 = $this->planService->getPlanForOrganization(1);
        $this->assertEquals('free', $plan1->slug);

        // Clear and call again
        $this->planService->clearCache(1);
        $plan2 = $this->planService->getPlanForOrganization(1);
        $this->assertEquals('free', $plan2->slug);
    }
}
