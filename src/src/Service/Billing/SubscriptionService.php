<?php
declare(strict_types=1);

namespace App\Service\Billing;

use App\Model\Entity\Plan;
use App\Model\Table\OrganizationsTable;
use App\Model\Table\PlansTable;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Subscription Service
 *
 * Handles subscription lifecycle events including checkout completion,
 * plan upgrades/downgrades, and webhook event processing.
 * Business logic is separated from Stripe API calls for testability.
 */
class SubscriptionService
{
    use LocatorAwareTrait;

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
        $this->Organizations = $this->fetchTable('Organizations');
        $this->Plans = $this->fetchTable('Plans');
    }

    /**
     * Handle a successful Stripe checkout session
     *
     * Updates the organization's plan, Stripe customer ID, and subscription ID.
     *
     * @param array $sessionData Checkout session data from Stripe webhook
     * @return void
     */
    public function handleCheckoutCompleted(array $sessionData): void
    {
        $metadata = $sessionData['metadata'] ?? [];
        $orgId = (int)($metadata['organization_id'] ?? 0);
        $planSlug = $metadata['plan_slug'] ?? '';

        if ($orgId <= 0 || empty($planSlug)) {
            Log::error('Invalid checkout session metadata', [
                'metadata' => $metadata,
            ]);

            return;
        }

        $organization = $this->Organizations->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$organization) {
            Log::error("Organization {$orgId} not found for checkout completion");

            return;
        }

        $plan = $this->Plans->find('bySlug', slug: $planSlug)->first();
        if (!$plan) {
            Log::error("Plan '{$planSlug}' not found for checkout completion");

            return;
        }

        $organization->plan = $planSlug;

        if (!empty($sessionData['customer'])) {
            $organization->stripe_customer_id = $sessionData['customer'];
        }

        if (!empty($sessionData['subscription'])) {
            $organization->stripe_subscription_id = $sessionData['subscription'];
        }

        // Clear trial if upgrading to a paid plan
        if ($planSlug !== Plan::SLUG_FREE) {
            $organization->trial_ends_at = null;
        }

        if ($this->Organizations->save($organization)) {
            Log::info("Organization {$orgId} upgraded to plan '{$planSlug}' via checkout");
        } else {
            Log::error("Failed to save organization {$orgId} after checkout completion");
        }
    }

    /**
     * Handle a Stripe subscription updated event
     *
     * Updates the organization's plan if the subscription plan changed.
     *
     * @param array $subscriptionData Subscription data from Stripe webhook
     * @return void
     */
    public function handleSubscriptionUpdated(array $subscriptionData): void
    {
        $metadata = $subscriptionData['metadata'] ?? [];
        $orgId = (int)($metadata['organization_id'] ?? 0);
        $planSlug = $metadata['plan_slug'] ?? '';

        if ($orgId <= 0) {
            // Try to find org by subscription ID
            $subscriptionId = $subscriptionData['id'] ?? '';
            if (!empty($subscriptionId)) {
                $organization = $this->Organizations->find()
                    ->where(['Organizations.stripe_subscription_id' => $subscriptionId])
                    ->first();

                if ($organization) {
                    $orgId = $organization->id;
                }
            }
        }

        if ($orgId <= 0) {
            Log::warning('Could not determine organization for subscription update', [
                'subscription_id' => $subscriptionData['id'] ?? 'unknown',
            ]);

            return;
        }

        if (empty($planSlug)) {
            return;
        }

        $this->upgradePlan($orgId, $planSlug);
    }

    /**
     * Handle a Stripe subscription deleted event
     *
     * Downgrades the organization to the free plan and clears Stripe IDs.
     *
     * @param array $subscriptionData Subscription data from Stripe webhook
     * @return void
     */
    public function handleSubscriptionDeleted(array $subscriptionData): void
    {
        $metadata = $subscriptionData['metadata'] ?? [];
        $orgId = (int)($metadata['organization_id'] ?? 0);

        if ($orgId <= 0) {
            // Try to find org by subscription ID
            $subscriptionId = $subscriptionData['id'] ?? '';
            if (!empty($subscriptionId)) {
                $organization = $this->Organizations->find()
                    ->where(['Organizations.stripe_subscription_id' => $subscriptionId])
                    ->first();

                if ($organization) {
                    $orgId = $organization->id;
                }
            }
        }

        if ($orgId <= 0) {
            Log::warning('Could not determine organization for subscription deletion', [
                'subscription_id' => $subscriptionData['id'] ?? 'unknown',
            ]);

            return;
        }

        $this->downgradeToFree($orgId);
    }

    /**
     * Handle a failed payment event
     *
     * Sets a grace period on the organization. The organization keeps its current
     * plan but receives a warning. After repeated failures, Stripe will eventually
     * cancel the subscription (handled by handleSubscriptionDeleted).
     *
     * @param array $invoiceData Invoice data from Stripe webhook
     * @return void
     */
    public function handlePaymentFailed(array $invoiceData): void
    {
        $customerId = $invoiceData['customer'] ?? '';

        if (empty($customerId)) {
            Log::warning('Payment failed event without customer ID');

            return;
        }

        $organization = $this->Organizations->find()
            ->where(['Organizations.stripe_customer_id' => $customerId])
            ->first();

        if (!$organization) {
            Log::warning("Organization not found for customer '{$customerId}' on payment failure");

            return;
        }

        // Set a 7-day grace period from now
        $gracePeriodEnd = new DateTime('+7 days');

        $settings = $organization->getSettings();
        $settings['payment_failed'] = true;
        $settings['payment_failed_at'] = (new DateTime())->toIso8601String();
        $settings['grace_period_ends_at'] = $gracePeriodEnd->toIso8601String();
        $organization->settings = $settings;

        if ($this->Organizations->save($organization)) {
            Log::warning("Payment failed for org {$organization->id}, grace period set until {$gracePeriodEnd->toIso8601String()}");
        } else {
            Log::error("Failed to save grace period for org {$organization->id}");
        }
    }

    /**
     * Upgrade an organization to a specified plan
     *
     * @param int $orgId Organization ID
     * @param string $planSlug Target plan slug
     * @return void
     */
    public function upgradePlan(int $orgId, string $planSlug): void
    {
        $organization = $this->Organizations->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$organization) {
            Log::error("Organization {$orgId} not found for plan upgrade");

            return;
        }

        $plan = $this->Plans->find('bySlug', slug: $planSlug)->first();
        if (!$plan) {
            Log::error("Plan '{$planSlug}' not found for upgrade");

            return;
        }

        $oldPlan = $organization->plan;
        $organization->plan = $planSlug;

        // Clear any payment failure flags on successful upgrade
        $settings = $organization->getSettings();
        unset($settings['payment_failed'], $settings['payment_failed_at'], $settings['grace_period_ends_at']);
        $organization->settings = !empty($settings) ? $settings : '';

        if ($this->Organizations->save($organization)) {
            Log::info("Organization {$orgId} upgraded from '{$oldPlan}' to '{$planSlug}'");
        } else {
            Log::error("Failed to upgrade organization {$orgId} to plan '{$planSlug}'");
        }
    }

    /**
     * Downgrade an organization to the free plan
     *
     * Clears the Stripe subscription ID but preserves the customer ID
     * so the customer can re-subscribe later.
     *
     * @param int $orgId Organization ID
     * @return void
     */
    public function downgradeToFree(int $orgId): void
    {
        $organization = $this->Organizations->find()
            ->where(['Organizations.id' => $orgId])
            ->first();

        if (!$organization) {
            Log::error("Organization {$orgId} not found for downgrade");

            return;
        }

        $oldPlan = $organization->plan;
        $organization->plan = Plan::SLUG_FREE;
        $organization->stripe_subscription_id = null;

        // Clear any payment failure flags
        $settings = $organization->getSettings();
        unset($settings['payment_failed'], $settings['payment_failed_at'], $settings['grace_period_ends_at']);
        $organization->settings = !empty($settings) ? $settings : '';

        if ($this->Organizations->save($organization)) {
            Log::info("Organization {$orgId} downgraded from '{$oldPlan}' to free plan");
        } else {
            Log::error("Failed to downgrade organization {$orgId} to free plan");
        }
    }
}
