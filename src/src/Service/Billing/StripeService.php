<?php
declare(strict_types=1);

namespace App\Service\Billing;

use App\Model\Entity\Plan;
use App\Model\Table\OrganizationsTable;
use App\Model\Table\PlansTable;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Stripe Service
 *
 * Wraps the Stripe PHP SDK to provide customer management,
 * checkout sessions, portal sessions, and subscription management.
 * All methods gracefully return null/false when Stripe is not configured,
 * allowing the application to function without Stripe in development.
 */
class StripeService
{
    use LocatorAwareTrait;

    /**
     * Stripe secret key
     *
     * @var string
     */
    private string $secretKey;

    /**
     * Stripe webhook signing secret
     *
     * @var string
     */
    private string $webhookSecret;

    /**
     * Organizations table instance
     *
     * @var \App\Model\Table\OrganizationsTable
     */
    private OrganizationsTable $Organizations;

    /**
     * Plans table instance
     *
     * @var \App\Model\Table\PlansTable
     */
    private PlansTable $Plans;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->secretKey = (string)env('STRIPE_SECRET_KEY', '');
        $this->webhookSecret = (string)env('STRIPE_WEBHOOK_SECRET', '');

        if ($this->secretKey) {
            \Stripe\Stripe::setApiKey($this->secretKey);
        }

        $this->Organizations = $this->fetchTable('Organizations');
        $this->Plans = $this->fetchTable('Plans');
    }

    /**
     * Check if Stripe is configured with a valid secret key
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * Get the webhook signing secret
     *
     * @return string
     */
    public function getWebhookSecret(): string
    {
        return $this->webhookSecret;
    }

    /**
     * Create a Stripe customer for an organization
     *
     * If the organization already has a stripe_customer_id, returns it.
     * Otherwise creates a new Stripe customer and saves the ID.
     *
     * @param int $orgId Organization ID
     * @return string|null The Stripe customer ID, or null on failure
     */
    public function createCustomer(int $orgId): ?string
    {
        if (!$this->isConfigured()) {
            Log::warning("Stripe not configured, cannot create customer for org {$orgId}");

            return null;
        }

        $organization = $this->Organizations->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$organization) {
            Log::error("Organization {$orgId} not found for Stripe customer creation");

            return null;
        }

        // Return existing customer ID if already set
        if (!empty($organization->stripe_customer_id)) {
            return $organization->stripe_customer_id;
        }

        try {
            $customer = \Stripe\Customer::create([
                'name' => $organization->name,
                'metadata' => [
                    'organization_id' => (string)$orgId,
                    'organization_slug' => $organization->slug,
                ],
            ]);

            $organization->stripe_customer_id = $customer->id;
            $this->Organizations->saveOrFail($organization);

            Log::info("Created Stripe customer {$customer->id} for org {$orgId}");

            return $customer->id;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error("Stripe API error creating customer for org {$orgId}: {$e->getMessage()}");

            return null;
        } catch (\Exception $e) {
            Log::error("Error creating Stripe customer for org {$orgId}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Create a Stripe Checkout session for plan upgrade
     *
     * Returns the checkout session URL that the user should be redirected to.
     *
     * @param int $orgId Organization ID
     * @param string $planSlug Target plan slug (e.g., 'pro', 'business')
     * @param string $interval Billing interval: 'monthly' or 'yearly'
     * @return string|null The checkout session URL, or null on failure
     */
    public function createCheckoutSession(int $orgId, string $planSlug, string $interval = 'monthly'): ?string
    {
        if (!$this->isConfigured()) {
            Log::warning("Stripe not configured, cannot create checkout session for org {$orgId}");

            return null;
        }

        $organization = $this->Organizations->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$organization) {
            Log::error("Organization {$orgId} not found for checkout session");

            return null;
        }

        $plan = $this->Plans->find('bySlug', slug: $planSlug)->first();
        if (!$plan) {
            Log::error("Plan '{$planSlug}' not found for checkout session");

            return null;
        }

        // Determine the price ID based on interval
        $priceId = $interval === 'yearly'
            ? $plan->stripe_price_id_yearly
            : $plan->stripe_price_id_monthly;

        if (empty($priceId)) {
            Log::error("No Stripe price ID for plan '{$planSlug}' interval '{$interval}'");

            return null;
        }

        // Ensure customer exists
        $customerId = $this->createCustomer($orgId);
        if (!$customerId) {
            return null;
        }

        try {
            $successUrl = env('APP_URL', 'http://localhost') . '/billing/success?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = env('APP_URL', 'http://localhost') . '/billing/cancel';

            $session = \Stripe\Checkout\Session::create([
                'customer' => $customerId,
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'organization_id' => (string)$orgId,
                    'plan_slug' => $planSlug,
                    'interval' => $interval,
                ],
                'subscription_data' => [
                    'metadata' => [
                        'organization_id' => (string)$orgId,
                        'plan_slug' => $planSlug,
                    ],
                ],
            ]);

            Log::info("Created Stripe checkout session {$session->id} for org {$orgId}, plan {$planSlug}");

            return $session->url;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error("Stripe API error creating checkout session for org {$orgId}: {$e->getMessage()}");

            return null;
        } catch (\Exception $e) {
            Log::error("Error creating Stripe checkout session for org {$orgId}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Create a Stripe Customer Portal session for managing subscription
     *
     * Returns the portal session URL that the user should be redirected to.
     *
     * @param int $orgId Organization ID
     * @return string|null The portal session URL, or null on failure
     */
    public function createPortalSession(int $orgId): ?string
    {
        if (!$this->isConfigured()) {
            Log::warning("Stripe not configured, cannot create portal session for org {$orgId}");

            return null;
        }

        $organization = $this->Organizations->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$organization || empty($organization->stripe_customer_id)) {
            Log::error("Organization {$orgId} has no Stripe customer ID for portal session");

            return null;
        }

        try {
            $returnUrl = env('APP_URL', 'http://localhost') . '/billing';

            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $organization->stripe_customer_id,
                'return_url' => $returnUrl,
            ]);

            Log::info("Created Stripe portal session for org {$orgId}");

            return $session->url;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error("Stripe API error creating portal session for org {$orgId}: {$e->getMessage()}");

            return null;
        } catch (\Exception $e) {
            Log::error("Error creating Stripe portal session for org {$orgId}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Cancel an organization's subscription
     *
     * @param int $orgId Organization ID
     * @param bool $atPeriodEnd If true, cancel at end of billing period; if false, cancel immediately
     * @return bool True if cancellation was successful
     */
    public function cancelSubscription(int $orgId, bool $atPeriodEnd = true): bool
    {
        if (!$this->isConfigured()) {
            Log::warning("Stripe not configured, cannot cancel subscription for org {$orgId}");

            return false;
        }

        $organization = $this->Organizations->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$organization || empty($organization->stripe_subscription_id)) {
            Log::error("Organization {$orgId} has no active Stripe subscription to cancel");

            return false;
        }

        try {
            if ($atPeriodEnd) {
                \Stripe\Subscription::update($organization->stripe_subscription_id, [
                    'cancel_at_period_end' => true,
                ]);
                Log::info("Scheduled subscription cancellation at period end for org {$orgId}");
            } else {
                $subscription = \Stripe\Subscription::retrieve($organization->stripe_subscription_id);
                $subscription->cancel();
                Log::info("Immediately cancelled subscription for org {$orgId}");
            }

            return true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error("Stripe API error cancelling subscription for org {$orgId}: {$e->getMessage()}");

            return false;
        } catch (\Exception $e) {
            Log::error("Error cancelling subscription for org {$orgId}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Get subscription status for an organization
     *
     * Returns an array with subscription details, or null if no subscription exists.
     *
     * @param int $orgId Organization ID
     * @return array|null Subscription status array with keys: status, plan, current_period_end, cancel_at_period_end
     */
    public function getSubscriptionStatus(int $orgId): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        $organization = $this->Organizations->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$organization || empty($organization->stripe_subscription_id)) {
            return null;
        }

        try {
            $subscription = \Stripe\Subscription::retrieve($organization->stripe_subscription_id);

            return [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'plan_slug' => $subscription->metadata['plan_slug'] ?? $organization->plan,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error("Stripe API error getting subscription status for org {$orgId}: {$e->getMessage()}");

            return null;
        } catch (\Exception $e) {
            Log::error("Error getting subscription status for org {$orgId}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Construct a Stripe webhook event from the request payload and signature
     *
     * @param string $payload Raw request body
     * @param string $sigHeader Stripe-Signature header value
     * @return \Stripe\Event|null The verified webhook event, or null on failure
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): ?\Stripe\Event
    {
        if (empty($this->webhookSecret)) {
            Log::error('Stripe webhook secret not configured');

            return null;
        }

        try {
            return \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $this->webhookSecret
            );
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error("Stripe webhook signature verification failed: {$e->getMessage()}");

            return null;
        } catch (\Exception $e) {
            Log::error("Error constructing Stripe webhook event: {$e->getMessage()}");

            return null;
        }
    }
}
