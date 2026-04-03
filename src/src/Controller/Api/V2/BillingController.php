<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * BillingController (TASK-NG-009)
 *
 * Billing plans, checkout, portal, usage, and credits.
 */
class BillingController extends AppController
{
    protected \App\Service\BillingService $billingService;

    public function initialize(): void
    {
        parent::initialize();
        $this->billingService = new \App\Service\BillingService();
    }

    /**
     * GET /api/v2/billing/plans
     *
     * List available billing plans.
     *
     * @return void
     */
    public function plans(): void
    {
        $this->request->allowMethod(['get']);

        try {
            $service = $this->billingService;
            $plans = $service->getPlans();

            // Include current plan slug for the authenticated organization
            $currentPlan = 'free';
            if ($this->currentOrgId > 0) {
                $orgsTable = $this->fetchTable('Organizations');
                $org = $orgsTable->find()
                    ->where(['Organizations.id' => $this->currentOrgId])
                    ->first();
                if ($org && !empty($org->plan)) {
                    $currentPlan = $org->plan;
                }
            }

            $this->success(['plans' => $plans, 'current_plan' => $currentPlan]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch plans: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v2/billing/checkout
     *
     * Create a Stripe checkout session for a plan.
     *
     * @return void
     */
    public function checkout(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner'])) {
            return;
        }

        $planSlug = $this->request->getData('plan');
        if (empty($planSlug)) {
            $this->error('Plan is required', 400);

            return;
        }

        try {
            $service = $this->billingService;
            $session = $service->createCheckoutSession($this->currentOrgId, $planSlug);

            if (empty($session)) {
                $this->error('Stripe is not configured. Contact support to upgrade your plan.', 422);

                return;
            }

            $this->success(['checkout_url' => $session]);
        } catch (\Exception $e) {
            $this->error('Failed to create checkout session: ' . $e->getMessage(), 422);
        }
    }

    /**
     * POST /api/v2/billing/portal
     *
     * Create a Stripe customer portal session.
     *
     * @return void
     */
    public function portal(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner'])) {
            return;
        }

        try {
            $service = $this->billingService;
            $url = $service->createPortalSession($this->currentOrgId);

            $this->success(['portal_url' => $url]);
        } catch (\Exception $e) {
            $this->error('Failed to create portal session: ' . $e->getMessage(), 422);
        }
    }

    /**
     * GET /api/v2/billing/usage
     *
     * Return current usage metrics for the organization.
     *
     * @return void
     */
    public function usage(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        try {
            $service = $this->billingService;
            $usage = $service->getUsage($this->currentOrgId);

            $this->success(['usage' => $usage]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch usage: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v2/billing/credits
     *
     * Return credit balance for the organization.
     *
     * @return void
     */
    public function credits(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        try {
            $service = $this->billingService;
            $credits = $service->getCredits($this->currentOrgId);

            $this->success(['credits' => $credits]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch credits: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v2/billing/credits/buy
     *
     * Create a Stripe checkout session for purchasing notification credits.
     *
     * @return void
     */
    public function buyCredits(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner'])) {
            return;
        }

        $amount = (int)$this->request->getData('amount', 100);
        if ($amount <= 0) {
            $this->error('Amount must be greater than 0', 400);

            return;
        }

        try {
            $creditService = new \App\Service\Billing\NotificationCreditService();
            $checkoutUrl = $creditService->purchaseCredits($this->currentOrgId, $amount);

            if ($checkoutUrl === null) {
                $this->error('Stripe is not configured. Contact support to purchase credits.', 422);

                return;
            }

            $this->success(['checkout_url' => $checkoutUrl]);
        } catch (\Exception $e) {
            $this->error('Failed to create credit purchase session: ' . $e->getMessage(), 422);
        }
    }
}
