<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Billing\NotificationCreditService;
use App\Service\Billing\StripeService;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Billing Controller
 *
 * Handles Stripe checkout/portal server-side redirects.
 * Plan display and billing UI are now handled by the Angular SPA.
 */
class BillingController extends AppController
{
    use LocatorAwareTrait;

    /**
     * Plans page - redirect to Angular billing page.
     *
     * @return \Cake\Http\Response
     */
    public function plans()
    {
        return $this->redirect('/app/billing');
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

            return $this->redirect('/app/billing');
        }

        $this->checkPermission('manage_billing');

        $amount = (int)$this->request->getData('amount', 100);
        $creditService = new NotificationCreditService();
        $url = $creditService->purchaseCredits($this->currentOrganization['id'], $amount);

        if ($url) {
            return $this->redirect($url);
        }

        $this->Flash->error(__('Could not process credit purchase. Please try again.'));

        return $this->redirect('/app/billing');
    }

    /**
     * Create a Stripe checkout session and redirect to Stripe.
     *
     * @param string $planSlug The target plan slug (e.g., 'pro', 'business')
     * @return \Cake\Http\Response|null
     */
    public function checkout(string $planSlug)
    {
        $this->request->allowMethod(['post']);

        if (!$this->currentOrganization) {
            $this->Flash->error(__('No organization selected.'));

            return $this->redirect('/app/billing');
        }

        $this->checkPermission('manage_billing');

        $orgId = (int)$this->currentOrganization['id'];
        $interval = $this->request->getData('interval', 'monthly');

        $stripeService = new StripeService();

        if (!$stripeService->isConfigured()) {
            $this->Flash->error(__('Stripe is not configured. Please contact the administrator.'));

            return $this->redirect('/app/billing');
        }

        $checkoutUrl = $stripeService->createCheckoutSession($orgId, $planSlug, $interval);

        if (!$checkoutUrl) {
            $this->Flash->error(__('Unable to create checkout session. Please try again.'));

            return $this->redirect('/app/billing');
        }

        return $this->redirect($checkoutUrl);
    }

    /**
     * Create a Stripe customer portal session and redirect to Stripe.
     *
     * @return \Cake\Http\Response|null
     */
    public function portal()
    {
        $this->request->allowMethod(['post']);

        if (!$this->currentOrganization) {
            $this->Flash->error(__('No organization selected.'));

            return $this->redirect('/app/billing');
        }

        $this->checkPermission('manage_billing');

        $orgId = (int)$this->currentOrganization['id'];

        $stripeService = new StripeService();

        if (!$stripeService->isConfigured()) {
            $this->Flash->error(__('Stripe is not configured. Please contact the administrator.'));

            return $this->redirect('/app/billing');
        }

        $portalUrl = $stripeService->createPortalSession($orgId);

        if (!$portalUrl) {
            $this->Flash->error(__('Unable to create billing portal session. Please try again.'));

            return $this->redirect('/app/billing');
        }

        return $this->redirect($portalUrl);
    }

    /**
     * Success page - redirect to Angular billing page with success status.
     *
     * @return \Cake\Http\Response
     */
    public function success()
    {
        return $this->redirect('/app/billing?checkout=success');
    }

    /**
     * Cancel page - redirect to Angular billing page with cancel status.
     *
     * @return \Cake\Http\Response
     */
    public function cancel()
    {
        return $this->redirect('/app/billing?checkout=cancelled');
    }
}
