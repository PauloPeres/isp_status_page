<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Mailer\Mailer;
use Cake\Log\Log;

/**
 * Contact Controller
 *
 * Handles the Contact Sales form for Enterprise plan inquiries.
 */
class ContactController extends AppController
{
    /**
     * Sales contact form.
     *
     * GET: displays the contact form.
     * POST: validates and sends the inquiry email to the platform operator.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function sales()
    {
        $this->viewBuilder()->setLayout('default');

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Basic validation
            $errors = [];
            if (empty($data['name'])) {
                $errors['name'] = __('Name is required.');
            }
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = __('A valid email address is required.');
            }
            if (empty($data['company'])) {
                $errors['company'] = __('Company name is required.');
            }
            if (empty($data['message'])) {
                $errors['message'] = __('Message is required.');
            }
            if (empty($data['expected_monitors']) || !is_numeric($data['expected_monitors'])) {
                $errors['expected_monitors'] = __('Expected monitor count is required.');
            }

            if (!empty($errors)) {
                $this->set('errors', $errors);
                $this->set('formData', $data);
                $this->Flash->error(__('Please correct the errors below.'));

                return;
            }

            try {
                $mailer = new Mailer('default');
                $mailer
                    ->setFrom([$data['email'] => $data['name']])
                    ->setTo(env('SALES_EMAIL', env('ADMIN_EMAIL', 'admin@localhost')))
                    ->setSubject(__('Enterprise Plan Inquiry from {0} ({1})', $data['name'], $data['company']))
                    ->setEmailFormat('html')
                    ->deliver(
                        '<h2>' . __('Enterprise Plan Inquiry') . '</h2>' .
                        '<p><strong>' . __('Name:') . '</strong> ' . h($data['name']) . '</p>' .
                        '<p><strong>' . __('Email:') . '</strong> ' . h($data['email']) . '</p>' .
                        '<p><strong>' . __('Company:') . '</strong> ' . h($data['company']) . '</p>' .
                        '<p><strong>' . __('Expected Monitors:') . '</strong> ' . h($data['expected_monitors']) . '</p>' .
                        '<p><strong>' . __('Message:') . '</strong></p>' .
                        '<p>' . nl2br(h($data['message'])) . '</p>'
                    );

                $this->Flash->success(__('Thank you! Your inquiry has been sent. Our team will contact you within 1 business day.'));

                return $this->redirect(['action' => 'sales']);
            } catch (\Exception $e) {
                Log::error('Failed to send sales contact email: ' . $e->getMessage());
                $this->Flash->error(__('Sorry, we could not send your message. Please try again or email us directly.'));
                $this->set('formData', $data);
            }
        }

        $this->set('errors', []);
        $this->set('formData', []);
    }

    /**
     * Allow unauthenticated access to the sales form.
     *
     * @param \Cake\Event\EventInterface $event The beforeFilter event.
     * @return void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);

        if (method_exists($this, 'Authentication')) {
            $this->Authentication->addUnauthenticatedActions(['sales']);
        }

        // CakePHP 5.x authentication component
        try {
            $authentication = $this->components()->get('Authentication');
            if ($authentication) {
                $authentication->addUnauthenticatedActions(['sales']);
            }
        } catch (\Exception $e) {
            // Authentication component not loaded, skip
        }
    }
}
