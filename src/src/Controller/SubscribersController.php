<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\EmailService;

/**
 * Subscribers Controller
 *
 * @property \App\Model\Table\SubscribersTable $Subscribers
 * @property \App\Model\Table\SubscriptionsTable $Subscriptions
 */
class SubscribersController extends AppController
{
    /**
     * Email service instance
     */
    private EmailService $emailService;

    /**
     * Initialize callback
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->emailService = new EmailService();
    }
    /**
     * Before filter callback
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // Allow public access to subscribe actions
        $this->Authentication->addUnauthenticatedActions(['subscribe', 'verify', 'unsubscribe']);
    }

    /**
     * Subscribe method - Public subscription form
     *
     * @return \Cake\Http\Response|null Redirects to status page
     */
    public function subscribe()
    {
        $this->request->allowMethod(['post']);

        $email = $this->request->getData('email');

        if (empty($email)) {
            $this->Flash->error(__d('subscribers', 'Please provide a valid email.'));
            return $this->redirect(['controller' => 'Status', 'action' => 'index']);
        }

        // Check if subscriber already exists
        $subscriber = $this->Subscribers->find()
            ->where(['email' => $email])
            ->first();

        if ($subscriber) {
            // Subscriber already exists
            if ($subscriber->verified && $subscriber->active) {
                $this->Flash->info(__d('subscribers', 'This email is already subscribed and active.'));
                return $this->redirect(['controller' => 'Status', 'action' => 'index']);
            }

            if (!$subscriber->verified) {
                // Resend verification
                if (empty($subscriber->verification_token)) {
                    $subscriber->generateVerificationToken();
                }
                $subscriber->active = true;
                $this->Subscribers->save($subscriber);

                // Send verification email
                if ($this->emailService->sendVerificationEmail($subscriber)) {
                    $this->Flash->success(__d('subscribers', 'A verification email has been sent to {0}.', $email));
                } else {
                    $this->Flash->warning(__d('subscribers', 'Unable to send verification email. Please try again later.'));
                }
                return $this->redirect(['controller' => 'Status', 'action' => 'index']);
            }

            if (!$subscriber->active) {
                // Reactivate
                $subscriber->active = true;
                $this->Subscribers->save($subscriber);
                $this->Flash->success(__d('subscribers', 'Your subscription has been reactivated successfully!'));
                return $this->redirect(['controller' => 'Status', 'action' => 'index']);
            }
        }

        // Create new subscriber
        $subscriber = $this->Subscribers->newEntity([
            'email' => $email,
            'verified' => false,
            'active' => true,
        ]);

        // Generate tokens
        $subscriber->generateVerificationToken();
        $subscriber->generateUnsubscribeToken();

        if ($this->Subscribers->save($subscriber)) {
            // Create global subscription (all monitors)
            $SubscriptionsTable = $this->fetchTable('Subscriptions');
            $subscription = $SubscriptionsTable->newEntity([
                'subscriber_id' => $subscriber->id,
                'monitor_id' => null, // Global - all monitors
                'notify_on_down' => true,
                'notify_on_up' => false,
                'notify_on_degraded' => true,
            ]);

            $SubscriptionsTable->save($subscription);

            // Send verification email
            if ($this->emailService->sendVerificationEmail($subscriber)) {
                $this->Flash->success(__d('subscribers', 'Thank you for subscribing! A verification email has been sent to {0}.', $email));
            } else {
                $this->Flash->warning(__d('subscribers', 'Subscription completed, but unable to send verification email. Please request a resend.'));
            }
        } else {
            $errors = $subscriber->getErrors();
            if (isset($errors['email']['unique'])) {
                $this->Flash->error(__d('subscribers', 'This email is already registered.'));
            } else {
                $this->Flash->error(__d('subscribers', 'Unable to complete subscription. Please try again.'));
            }
        }

        return $this->redirect(['controller' => 'Status', 'action' => 'index']);
    }

    /**
     * Verify method - Verify subscriber email with token
     *
     * @param string|null $token Verification token
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function verify($token = null)
    {
        $this->viewBuilder()->setLayout('public');

        if (empty($token)) {
            $this->Flash->error(__d('subscribers', 'Invalid verification token.'));
            return $this->redirect(['controller' => 'Status', 'action' => 'index']);
        }

        $subscriber = $this->Subscribers->find()
            ->where(['verification_token' => $token])
            ->first();

        if (!$subscriber) {
            $this->Flash->error(__d('subscribers', 'Invalid or expired verification token.'));
            return $this->redirect(['controller' => 'Status', 'action' => 'index']);
        }

        if ($subscriber->verified) {
            $this->Flash->info(__d('subscribers', 'This email has already been verified.'));
            return $this->redirect(['controller' => 'Status', 'action' => 'index']);
        }

        // Verify subscriber
        $subscriber->verified = true;
        $subscriber->verified_at = new \DateTime();
        $subscriber->verification_token = null; // Clear token after use

        if ($this->Subscribers->save($subscriber)) {
            $this->set('subscriber', $subscriber);
            $this->Flash->success(__d('subscribers', 'Email verified successfully! You will start receiving notifications.'));
        } else {
            $this->Flash->error(__d('subscribers', 'Unable to verify email. Please try again.'));
            return $this->redirect(['controller' => 'Status', 'action' => 'index']);
        }
    }

    /**
     * Unsubscribe method - Unsubscribe with token
     *
     * @param string|null $token Unsubscribe token
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function unsubscribe($token = null)
    {
        $this->viewBuilder()->setLayout('public');

        if (empty($token)) {
            $this->Flash->error(__d('subscribers', 'Invalid unsubscribe token.'));
            return $this->redirect(['controller' => 'Status', 'action' => 'index']);
        }

        $subscriber = $this->Subscribers->find()
            ->where(['unsubscribe_token' => $token])
            ->first();

        if (!$subscriber) {
            $this->Flash->error(__d('subscribers', 'Invalid unsubscribe token.'));
            return $this->redirect(['controller' => 'Status', 'action' => 'index']);
        }

        if ($this->request->is('post')) {
            // Confirm unsubscribe
            $subscriber->active = false;

            if ($this->Subscribers->save($subscriber)) {
                $this->set('success', true);
                $this->Flash->success(__d('subscribers', 'You have been unsubscribed successfully. You will no longer receive notifications.'));
            } else {
                $this->set('success', false);
                $this->Flash->error(__d('subscribers', 'Unable to process unsubscription. Please try again.'));
            }
        }

        $this->set('subscriber', $subscriber);
    }

    /**
     * Index - redirect to Angular subscribers admin.
     *
     * @return \Cake\Http\Response
     */
    public function index()
    {
        return $this->redirect('/app/subscribers');
    }

    /**
     * View - redirect to Angular subscriber detail.
     *
     * @param string|null $id Subscriber id.
     * @return \Cake\Http\Response
     */
    public function view($id = null)
    {
        return $this->redirect('/app/subscribers/' . $id);
    }

    /**
     * Delete - redirect to Angular subscribers list.
     *
     * @param string|null $id Subscriber id.
     * @return \Cake\Http\Response
     */
    public function delete($id = null)
    {
        return $this->redirect('/app/subscribers');
    }

    /**
     * Toggle - redirect to Angular subscribers list.
     *
     * @param string|null $id Subscriber id.
     * @return \Cake\Http\Response
     */
    public function toggle($id = null)
    {
        return $this->redirect('/app/subscribers');
    }

    /**
     * Resend verification - redirect to Angular.
     *
     * @param string|null $id Subscriber id.
     * @return \Cake\Http\Response
     */
    public function resendVerification($id = null)
    {
        return $this->redirect('/app/subscribers/' . $id);
    }
}
