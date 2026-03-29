<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Billing\NotificationCreditService;
use App\Service\Billing\StripeService;
use App\Service\Billing\UsageService;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Billing Service (Facade)
 *
 * Provides a unified interface for billing operations, delegating to
 * StripeService, UsageService, and NotificationCreditService.
 */
class BillingService
{
    use LocatorAwareTrait;

    /**
     * @var \App\Service\Billing\StripeService
     */
    private StripeService $stripe;

    /**
     * @var \App\Service\Billing\UsageService
     */
    private UsageService $usage;

    /**
     * @var \App\Service\Billing\NotificationCreditService
     */
    private NotificationCreditService $credits;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->stripe = new StripeService();
        $this->usage = new UsageService();
        $this->credits = new NotificationCreditService();
    }

    /**
     * Get available billing plans.
     *
     * @return array
     */
    public function getPlans(): array
    {
        $plansTable = $this->fetchTable('Plans');
        $plans = $plansTable->find()
            ->where(['Plans.active' => true])
            ->orderBy(['Plans.price_monthly' => 'ASC'])
            ->all();

        return $plans->toArray();
    }

    /**
     * Create a Stripe checkout session for a plan.
     *
     * @param int $orgId Organization ID
     * @param string $planSlug Plan slug
     * @return string Checkout URL
     */
    public function createCheckoutSession(int $orgId, string $planSlug): string
    {
        $url = $this->stripe->createCheckoutSession($orgId, $planSlug);

        return $url ?: '';
    }

    /**
     * Create a Stripe customer portal session.
     *
     * @param int $orgId Organization ID
     * @return string Portal URL
     */
    public function createPortalSession(int $orgId): string
    {
        $url = $this->stripe->createPortalSession($orgId);

        return $url ?: '';
    }

    /**
     * Get current usage metrics for the organization.
     *
     * @param int $orgId Organization ID
     * @return array
     */
    public function getUsage(int $orgId): array
    {
        return $this->usage->getUsage($orgId);
    }

    /**
     * Get credit balance for the organization.
     *
     * @param int $orgId Organization ID
     * @return array
     */
    public function getCredits(int $orgId): array
    {
        $credits = $this->credits->getCredits($orgId);

        if ($credits && method_exists($credits, 'toArray')) {
            return $credits->toArray();
        }

        return ['balance' => 0, 'monthly_grant' => 0];
    }
}
