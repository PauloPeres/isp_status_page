<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Billing;

use App\Service\Billing\StripeService;
use Cake\TestSuite\TestCase;

/**
 * StripeService Test Case
 *
 * Tests the StripeService business logic without making actual Stripe API calls.
 * Since STRIPE_SECRET_KEY is not set in the test environment, all API-dependent
 * methods should gracefully return null/false.
 */
class StripeServiceTest extends TestCase
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
     * @var \App\Service\Billing\StripeService
     */
    protected StripeService $stripeService;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->stripeService = new StripeService();
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        unset($this->stripeService);
        parent::tearDown();
    }

    /**
     * Test isConfigured returns false when no key is set
     */
    public function testIsConfiguredReturnsFalseWithoutKey(): void
    {
        $this->assertFalse($this->stripeService->isConfigured());
    }

    /**
     * Test getWebhookSecret returns empty string when not configured
     */
    public function testGetWebhookSecretReturnsEmptyWhenNotConfigured(): void
    {
        $this->assertEmpty($this->stripeService->getWebhookSecret());
    }

    /**
     * Test createCustomer returns null when Stripe is not configured
     */
    public function testCreateCustomerReturnsNullWhenNotConfigured(): void
    {
        $result = $this->stripeService->createCustomer(1);
        $this->assertNull($result);
    }

    /**
     * Test createCustomer returns null for non-existent organization
     */
    public function testCreateCustomerReturnsNullForMissingOrg(): void
    {
        $result = $this->stripeService->createCustomer(9999);
        $this->assertNull($result);
    }

    /**
     * Test createCheckoutSession returns null when Stripe is not configured
     */
    public function testCreateCheckoutSessionReturnsNullWhenNotConfigured(): void
    {
        $result = $this->stripeService->createCheckoutSession(1, 'pro');
        $this->assertNull($result);
    }

    /**
     * Test createCheckoutSession returns null for non-existent plan
     */
    public function testCreateCheckoutSessionReturnsNullForMissingPlan(): void
    {
        $result = $this->stripeService->createCheckoutSession(1, 'nonexistent');
        $this->assertNull($result);
    }

    /**
     * Test createPortalSession returns null when Stripe is not configured
     */
    public function testCreatePortalSessionReturnsNullWhenNotConfigured(): void
    {
        $result = $this->stripeService->createPortalSession(1);
        $this->assertNull($result);
    }

    /**
     * Test createPortalSession returns null for org without Stripe customer
     */
    public function testCreatePortalSessionReturnsNullForOrgWithoutCustomer(): void
    {
        $result = $this->stripeService->createPortalSession(1);
        $this->assertNull($result);
    }

    /**
     * Test cancelSubscription returns false when Stripe is not configured
     */
    public function testCancelSubscriptionReturnsFalseWhenNotConfigured(): void
    {
        $result = $this->stripeService->cancelSubscription(1);
        $this->assertFalse($result);
    }

    /**
     * Test cancelSubscription returns false for org without subscription
     */
    public function testCancelSubscriptionReturnsFalseForOrgWithoutSubscription(): void
    {
        $result = $this->stripeService->cancelSubscription(1);
        $this->assertFalse($result);
    }

    /**
     * Test getSubscriptionStatus returns null when Stripe is not configured
     */
    public function testGetSubscriptionStatusReturnsNullWhenNotConfigured(): void
    {
        $result = $this->stripeService->getSubscriptionStatus(1);
        $this->assertNull($result);
    }

    /**
     * Test getSubscriptionStatus returns null for org without subscription
     */
    public function testGetSubscriptionStatusReturnsNullForOrgWithoutSubscription(): void
    {
        // Organization 1 has no stripe_subscription_id
        $result = $this->stripeService->getSubscriptionStatus(1);
        $this->assertNull($result);
    }

    /**
     * Test constructWebhookEvent returns null when webhook secret is not set
     */
    public function testConstructWebhookEventReturnsNullWithoutSecret(): void
    {
        $result = $this->stripeService->constructWebhookEvent('payload', 'sig_header');
        $this->assertNull($result);
    }
}
