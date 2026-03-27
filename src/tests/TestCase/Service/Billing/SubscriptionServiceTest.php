<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Billing;

use App\Model\Entity\Plan;
use App\Service\Billing\SubscriptionService;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * SubscriptionService Test Case
 *
 * Tests the business logic for subscription lifecycle management
 * including checkout completion, plan upgrades, downgrades, and
 * payment failure handling.
 */
class SubscriptionServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Plans',
        'app.Organizations',
        'app.Users',
        'app.OrganizationUsers',
        'app.Monitors',
    ];

    /**
     * @var \App\Service\Billing\SubscriptionService
     */
    protected SubscriptionService $subscriptionService;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriptionService = new SubscriptionService();
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        unset($this->subscriptionService);
        parent::tearDown();
    }

    /**
     * Test handleCheckoutCompleted updates organization plan and Stripe IDs
     */
    public function testHandleCheckoutCompletedUpdatesPlan(): void
    {
        $sessionData = [
            'metadata' => [
                'organization_id' => '1',
                'plan_slug' => 'pro',
            ],
            'customer' => 'cus_new_123',
            'subscription' => 'sub_new_456',
        ];

        $this->subscriptionService->handleCheckoutCompleted($sessionData);

        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $org = $organizations->get(1);

        $this->assertEquals('pro', $org->plan);
        $this->assertEquals('cus_new_123', $org->stripe_customer_id);
        $this->assertEquals('sub_new_456', $org->stripe_subscription_id);
    }

    /**
     * Test handleCheckoutCompleted clears trial on paid plan upgrade
     */
    public function testHandleCheckoutCompletedClearsTrial(): void
    {
        // Organization 2 has a trial_ends_at set
        $sessionData = [
            'metadata' => [
                'organization_id' => '2',
                'plan_slug' => 'business',
            ],
            'customer' => 'cus_abc123',
            'subscription' => 'sub_upgraded_789',
        ];

        $this->subscriptionService->handleCheckoutCompleted($sessionData);

        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $org = $organizations->get(2);

        $this->assertEquals('business', $org->plan);
        $this->assertNull($org->trial_ends_at);
        $this->assertEquals('sub_upgraded_789', $org->stripe_subscription_id);
    }

    /**
     * Test handleCheckoutCompleted ignores invalid metadata
     */
    public function testHandleCheckoutCompletedIgnoresInvalidMetadata(): void
    {
        $sessionData = [
            'metadata' => [
                'organization_id' => '0',
                'plan_slug' => '',
            ],
        ];

        // Should not throw, just log and return
        $this->subscriptionService->handleCheckoutCompleted($sessionData);

        // Verify org 1 is unchanged
        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $org = $organizations->get(1);
        $this->assertEquals('free', $org->plan);
    }

    /**
     * Test handleCheckoutCompleted handles non-existent organization
     */
    public function testHandleCheckoutCompletedHandlesNonExistentOrg(): void
    {
        $sessionData = [
            'metadata' => [
                'organization_id' => '9999',
                'plan_slug' => 'pro',
            ],
        ];

        // Should not throw
        $this->subscriptionService->handleCheckoutCompleted($sessionData);
        $this->assertTrue(true, 'No exception thrown for non-existent org');
    }

    /**
     * Test handleSubscriptionDeleted downgrades to free plan
     */
    public function testHandleSubscriptionDeletedDowngradesToFree(): void
    {
        $subscriptionData = [
            'id' => 'sub_xyz789',
            'metadata' => [
                'organization_id' => '2',
            ],
        ];

        $this->subscriptionService->handleSubscriptionDeleted($subscriptionData);

        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $org = $organizations->get(2);

        $this->assertEquals('free', $org->plan);
        $this->assertNull($org->stripe_subscription_id);
        // Customer ID should be preserved for re-subscription
        $this->assertEquals('cus_abc123', $org->stripe_customer_id);
    }

    /**
     * Test handleSubscriptionDeleted finds org by subscription ID
     */
    public function testHandleSubscriptionDeletedFindsOrgBySubscriptionId(): void
    {
        // Organization 2 has stripe_subscription_id = 'sub_xyz789'
        $subscriptionData = [
            'id' => 'sub_xyz789',
            'metadata' => [],
        ];

        $this->subscriptionService->handleSubscriptionDeleted($subscriptionData);

        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $org = $organizations->get(2);

        $this->assertEquals('free', $org->plan);
        $this->assertNull($org->stripe_subscription_id);
    }

    /**
     * Test handleSubscriptionUpdated with plan change
     */
    public function testHandleSubscriptionUpdatedChangesPlan(): void
    {
        $subscriptionData = [
            'id' => 'sub_xyz789',
            'metadata' => [
                'organization_id' => '2',
                'plan_slug' => 'business',
            ],
        ];

        $this->subscriptionService->handleSubscriptionUpdated($subscriptionData);

        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $org = $organizations->get(2);

        $this->assertEquals('business', $org->plan);
    }

    /**
     * Test handlePaymentFailed sets grace period
     */
    public function testHandlePaymentFailedSetsGracePeriod(): void
    {
        $invoiceData = [
            'customer' => 'cus_abc123',
        ];

        $this->subscriptionService->handlePaymentFailed($invoiceData);

        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $org = $organizations->get(2);

        $settings = $org->getSettings();
        $this->assertTrue($settings['payment_failed']);
        $this->assertArrayHasKey('payment_failed_at', $settings);
        $this->assertArrayHasKey('grace_period_ends_at', $settings);
    }

    /**
     * Test handlePaymentFailed ignores unknown customer
     */
    public function testHandlePaymentFailedIgnoresUnknownCustomer(): void
    {
        $invoiceData = [
            'customer' => 'cus_unknown',
        ];

        // Should not throw
        $this->subscriptionService->handlePaymentFailed($invoiceData);
        $this->assertTrue(true, 'No exception thrown for unknown customer');
    }

    /**
     * Test upgradePlan updates organization plan
     */
    public function testUpgradePlanUpdatesOrganization(): void
    {
        $this->subscriptionService->upgradePlan(1, 'pro');

        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $org = $organizations->get(1);

        $this->assertEquals('pro', $org->plan);
    }

    /**
     * Test upgradePlan clears payment failure flags
     */
    public function testUpgradePlanClearsPaymentFailureFlags(): void
    {
        // First set a payment failure
        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $org = $organizations->get(1);
        $org->settings = ['payment_failed' => true, 'payment_failed_at' => '2026-01-01T00:00:00+00:00'];
        $organizations->saveOrFail($org);

        // Now upgrade
        $this->subscriptionService->upgradePlan(1, 'pro');

        $org = $organizations->get(1);
        $settings = $org->getSettings();
        $this->assertArrayNotHasKey('payment_failed', $settings);
        $this->assertArrayNotHasKey('payment_failed_at', $settings);
    }

    /**
     * Test upgradePlan handles non-existent organization
     */
    public function testUpgradePlanHandlesNonExistentOrg(): void
    {
        // Should not throw
        $this->subscriptionService->upgradePlan(9999, 'pro');
        $this->assertTrue(true, 'No exception thrown for non-existent org');
    }

    /**
     * Test upgradePlan handles non-existent plan
     */
    public function testUpgradePlanHandlesNonExistentPlan(): void
    {
        // Should not throw
        $this->subscriptionService->upgradePlan(1, 'nonexistent');

        // Verify org plan unchanged
        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $org = $organizations->get(1);
        $this->assertEquals('free', $org->plan);
    }

    /**
     * Test downgradeToFree sets free plan and clears subscription
     */
    public function testDowngradeToFreeSetsFreeAndClearsSubscription(): void
    {
        $this->subscriptionService->downgradeToFree(2);

        $organizations = TableRegistry::getTableLocator()->get('Organizations');
        $org = $organizations->get(2);

        $this->assertEquals(Plan::SLUG_FREE, $org->plan);
        $this->assertNull($org->stripe_subscription_id);
        // Customer ID preserved
        $this->assertEquals('cus_abc123', $org->stripe_customer_id);
    }

    /**
     * Test downgradeToFree handles non-existent organization
     */
    public function testDowngradeToFreeHandlesNonExistentOrg(): void
    {
        // Should not throw
        $this->subscriptionService->downgradeToFree(9999);
        $this->assertTrue(true, 'No exception thrown for non-existent org');
    }
}
