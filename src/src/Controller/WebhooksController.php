<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Billing\StripeService;
use App\Service\Billing\SubscriptionService;

/**
 * Webhooks Controller
 *
 * Handles incoming webhook requests from external services.
 * All webhook endpoints are exempt from authentication and CSRF protection.
 */
class WebhooksController extends AppController
{
    /**
     * Before filter callback.
     *
     * Webhooks don't need authentication or CSRF protection.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // Webhooks don't need auth or CSRF
        $this->Authentication->addUnauthenticatedActions(['stripe']);
    }

    /**
     * Handle Stripe webhook events.
     *
     * POST /webhooks/stripe
     *
     * Verifies the webhook signature and dispatches the event
     * to the appropriate handler in SubscriptionService.
     *
     * @return \Cake\Http\Response
     */
    public function stripe()
    {
        $this->request->allowMethod(['post']);
        $this->autoRender = false;

        $payload = (string)$this->request->getBody();
        $sigHeader = $this->request->getHeaderLine('Stripe-Signature');

        $stripeService = new StripeService();
        $event = $stripeService->constructWebhookEvent($payload, $sigHeader);

        if (!$event) {
            return $this->response->withStatus(400)->withStringBody('Invalid webhook');
        }

        $subscriptionService = new SubscriptionService();

        switch ($event->type) {
            case 'checkout.session.completed':
                $subscriptionService->handleCheckoutCompleted($event->data->object->toArray());
                break;
            case 'customer.subscription.updated':
                $subscriptionService->handleSubscriptionUpdated($event->data->object->toArray());
                break;
            case 'customer.subscription.deleted':
                $subscriptionService->handleSubscriptionDeleted($event->data->object->toArray());
                break;
            case 'invoice.payment_failed':
                $subscriptionService->handlePaymentFailed($event->data->object->toArray());
                break;
        }

        return $this->response->withStatus(200)->withStringBody('OK');
    }
}
