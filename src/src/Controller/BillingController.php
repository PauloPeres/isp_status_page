<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Plan;
use App\Service\Billing\NotificationCreditService;
use App\Service\Billing\StripeService;
use App\Service\Billing\UsageService;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Billing Controller
 *
 * Handles plan display, Stripe checkout sessions, portal sessions,
 * and post-checkout success/cancel pages.
 */
class BillingController extends AppController
{
    use LocatorAwareTrait;

    /**
     * Display the pricing page with available plans.
     *
     * Shows all active plans with the current plan highlighted.
     * Uses the admin layout.
     *
     * @return void
     */
    public function plans(): void
    {
        $this->viewBuilder()->setLayout('admin');

        $plansTable = $this->fetchTable('Plans');
        $plans = $plansTable->find('active')->toArray();

        $currentPlan = Plan::SLUG_FREE;
        $orgId = null;
        if ($this->currentOrganization) {
            $currentPlan = $this->currentOrganization['plan'] ?? Plan::SLUG_FREE;
            $orgId = (int)$this->currentOrganization['id'];
        }

        $usage = [];
        $limits = [];
        if ($orgId) {
            $usageService = new UsageService();
            $usage = $usageService->getUsage($orgId);
            $limits = $usageService->getLimits($orgId);
        }

        $credits = null;
        $recentTransactions = [];
        $monthlyUsage = [];
        if ($orgId) {
            $creditService = new NotificationCreditService();
            $credits = $creditService->getCredits($orgId);
            $monthlyUsage = $creditService->getMonthlyUsage($orgId);
            $recentTransactions = $this->fetchTable('NotificationCreditTransactions')
                ->find()
                ->where(['organization_id' => $orgId])
                ->orderBy(['created' => 'DESC'])
                ->limit(10)
                ->all();
        }

        $this->set(compact('plans', 'currentPlan', 'usage', 'limits', 'credits', 'recentTransactions', 'monthlyUsage'));
    }

    /**
     * Purchase notification credits via Stripe.
     *
     * POST action that creates a Stripe checkout session for credit purchase.
     *
     * @return \Cake\Http\Response|null
     */
    public function purchaseCredits()
    {
        $this->request->allowMethod(['post']);

        if (!$this->currentOrganization) {
            $this->Flash->error(__('No organization selected.'));

            return $this->redirect(['action' => 'plans']);
        }

        $this->checkPermission('manage_billing');

        $amount = (int)$this->request->getData('amount', 100);
        $creditService = new NotificationCreditService();
        $url = $creditService->purchaseCredits($this->currentOrganization['id'], $amount);

        if ($url) {
            return $this->redirect($url);
        }

        $this->Flash->error(__('Could not process credit purchase. Please try again.'));

        return $this->redirect(['action' => 'plans']);
    }

    /**
     * Create a Stripe checkout session and redirect to Stripe.
     *
     * POST action that creates a checkout session for the specified plan.
     * Redirects to Stripe's hosted checkout page.
     *
     * @param string $planSlug The target plan slug (e.g., 'pro', 'business')
     * @return \Cake\Http\Response|null
     */
    public function checkout(string $planSlug)
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->setLayout('admin');

        if (!$this->currentOrganization) {
            $this->Flash->error(__('No organization selected.'));

            return $this->redirect(['action' => 'plans']);
        }

        $this->checkPermission('manage_billing');

        $orgId = (int)$this->currentOrganization['id'];
        $interval = $this->request->getData('interval', 'monthly');

        $stripeService = new StripeService();

        if (!$stripeService->isConfigured()) {
            $this->Flash->error(__('Stripe is not configured. Please contact the administrator.'));

            return $this->redirect(['action' => 'plans']);
        }

        $checkoutUrl = $stripeService->createCheckoutSession($orgId, $planSlug, $interval);

        if (!$checkoutUrl) {
            $this->Flash->error(__('Unable to create checkout session. Please try again.'));

            return $this->redirect(['action' => 'plans']);
        }

        return $this->redirect($checkoutUrl);
    }

    /**
     * Create a Stripe customer portal session and redirect to Stripe.
     *
     * POST action that creates a portal session for managing the current subscription.
     *
     * @return \Cake\Http\Response|null
     */
    public function portal()
    {
        $this->request->allowMethod(['post']);

        if (!$this->currentOrganization) {
            $this->Flash->error(__('No organization selected.'));

            return $this->redirect(['action' => 'plans']);
        }

        $this->checkPermission('manage_billing');

        $orgId = (int)$this->currentOrganization['id'];

        $stripeService = new StripeService();

        if (!$stripeService->isConfigured()) {
            $this->Flash->error(__('Stripe is not configured. Please contact the administrator.'));

            return $this->redirect(['action' => 'plans']);
        }

        $portalUrl = $stripeService->createPortalSession($orgId);

        if (!$portalUrl) {
            $this->Flash->error(__('Unable to create billing portal session. Please try again.'));

            return $this->redirect(['action' => 'plans']);
        }

        return $this->redirect($portalUrl);
    }

    /**
     * Success page displayed after a successful checkout.
     *
     * @return void
     */
    public function success(): void
    {
        $this->viewBuilder()->setLayout('admin');

        $currentPlan = Plan::SLUG_FREE;
        if ($this->currentOrganization) {
            $currentPlan = $this->currentOrganization['plan'] ?? Plan::SLUG_FREE;
        }

        $plansTable = $this->fetchTable('Plans');
        $plan = $plansTable->find('bySlug', slug: $currentPlan)->first();

        $this->set(compact('plan', 'currentPlan'));
    }

    /**
     * Cancel page displayed when a user cancels checkout.
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->viewBuilder()->setLayout('admin');
    }
}
