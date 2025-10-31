<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Subscribers Controller
 *
 * @property \App\Model\Table\SubscribersTable $Subscribers
 * @property \App\Model\Table\SubscriptionsTable $Subscriptions
 */
class SubscribersController extends AppController
{
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
            $this->Flash->error(__('Por favor, informe um email válido.'));
            return $this->redirect(['controller' => 'Status', 'action' => 'index']);
        }

        // Check if subscriber already exists
        $subscriber = $this->Subscribers->find()
            ->where(['email' => $email])
            ->first();

        if ($subscriber) {
            // Subscriber already exists
            if ($subscriber->verified && $subscriber->active) {
                $this->Flash->info(__('Este email já está inscrito e ativo.'));
                return $this->redirect(['controller' => 'Status', 'action' => 'index']);
            }

            if (!$subscriber->verified) {
                // Resend verification
                if (empty($subscriber->verification_token)) {
                    $subscriber->generateVerificationToken();
                }
                $subscriber->active = true;
                $this->Subscribers->save($subscriber);

                // TODO: Send verification email
                $this->Flash->success(__('Um email de verificação foi enviado para {0}.', $email));
                return $this->redirect(['controller' => 'Status', 'action' => 'index']);
            }

            if (!$subscriber->active) {
                // Reactivate
                $subscriber->active = true;
                $this->Subscribers->save($subscriber);
                $this->Flash->success(__('Sua inscrição foi reativada com sucesso!'));
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

            // TODO: Send verification email when EmailService is ready
            $this->Flash->success(__('Obrigado por se inscrever! Um email de verificação foi enviado para {0}.', $email));
        } else {
            $errors = $subscriber->getErrors();
            if (isset($errors['email']['unique'])) {
                $this->Flash->error(__('Este email já está cadastrado.'));
            } else {
                $this->Flash->error(__('Não foi possível completar a inscrição. Por favor, tente novamente.'));
            }
        }

        return $this->redirect(['controller' => 'Status', 'action' => 'index']);
    }

    /**
     * Index method - List all subscribers with filters
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        $query = $this->Subscribers->find()->contain(['Subscriptions']);

        // Filter by verification status
        $status = $this->request->getQuery('status');
        if ($status === 'verified') {
            $query->where(['Subscribers.verified' => true]);
        } elseif ($status === 'unverified') {
            $query->where(['Subscribers.verified' => false]);
        }

        // Filter by active status
        $active = $this->request->getQuery('active');
        if ($active === 'active') {
            $query->where(['Subscribers.active' => true]);
        } elseif ($active === 'inactive') {
            $query->where(['Subscribers.active' => false]);
        }

        // Search by email
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $query->where([
                'OR' => [
                    'Subscribers.email LIKE' => '%' . $search . '%',
                    'Subscribers.name LIKE' => '%' . $search . '%',
                ],
            ]);
        }

        // Filter by period
        $period = $this->request->getQuery('period', '30d');
        $periodStart = $this->getPeriodStartDate($period);
        if ($periodStart) {
            $query->where(['Subscribers.created >=' => $periodStart]);
        }

        // Order by most recent first
        $query->orderBy(['Subscribers.created' => 'DESC']);

        // Paginate
        $this->paginate = [
            'limit' => 50,
        ];
        $subscribers = $this->paginate($query);

        // Calculate statistics
        $stats = [
            'total' => $this->Subscribers->find()->count(),
            'verified' => $this->Subscribers->find()->where(['verified' => true])->count(),
            'unverified' => $this->Subscribers->find()->where(['verified' => false])->count(),
            'active' => $this->Subscribers->find()->where(['active' => true])->count(),
            'recentlyAdded' => $this->Subscribers->find()
                ->where(['Subscribers.created >=' => new \DateTime('-7 days')])
                ->count(),
        ];

        $this->set(compact('subscribers', 'stats', 'period'));
    }

    /**
     * View method - View subscriber details
     *
     * @param string|null $id Subscriber id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $subscriber = $this->Subscribers->get($id, contain: [
            'Subscriptions' => ['Monitors'],
        ]);

        // Get email logs count (if EmailLogs table exists)
        $emailLogsCount = 0;
        try {
            $EmailLogs = $this->fetchTable('EmailLogs');
            $emailLogsCount = $EmailLogs->find()
                ->where(['recipient' => $subscriber->email])
                ->count();
        } catch (\Exception $e) {
            // EmailLogs table doesn't exist yet
        }

        $this->set(compact('subscriber', 'emailLogsCount'));
    }

    /**
     * Delete method - Delete a subscriber
     *
     * @param string|null $id Subscriber id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $subscriber = $this->Subscribers->get($id);

        if ($this->Subscribers->delete($subscriber)) {
            $this->Flash->success(__('O inscrito foi excluído com sucesso.'));
        } else {
            $this->Flash->error(__('Não foi possível excluir o inscrito. Por favor, tente novamente.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Toggle active status
     *
     * @param string|null $id Subscriber id.
     * @return \Cake\Http\Response|null Redirects to referer or index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function toggle($id = null)
    {
        $this->request->allowMethod(['post']);
        $subscriber = $this->Subscribers->get($id);

        $subscriber->active = !$subscriber->active;

        if ($this->Subscribers->save($subscriber)) {
            $status = $subscriber->active ? 'ativado' : 'desativado';
            $this->Flash->success(__("O inscrito foi {$status} com sucesso."));
        } else {
            $this->Flash->error(__('Não foi possível atualizar o status. Por favor, tente novamente.'));
        }

        return $this->redirect($this->referer(['action' => 'index']));
    }

    /**
     * Resend verification email
     *
     * @param string|null $id Subscriber id.
     * @return \Cake\Http\Response|null Redirects to referer or view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function resendVerification($id = null)
    {
        $this->request->allowMethod(['post']);
        $subscriber = $this->Subscribers->get($id);

        if ($subscriber->verified) {
            $this->Flash->warning(__('Este inscrito já está verificado.'));
            return $this->redirect($this->referer(['action' => 'view', $id]));
        }

        // Generate new token if needed
        if (empty($subscriber->verification_token)) {
            $subscriber->generateVerificationToken();
            $this->Subscribers->save($subscriber);
        }

        // TODO: Send verification email when EmailService is implemented
        $this->Flash->info(__('Email de verificação será enviado quando o serviço de email estiver configurado.'));

        return $this->redirect($this->referer(['action' => 'view', $id]));
    }

    /**
     * Get period start date based on period string
     *
     * @param string $period Period string (7d, 30d, 90d, all)
     * @return \DateTime|null
     */
    private function getPeriodStartDate(string $period): ?\DateTime
    {
        return match ($period) {
            '7d' => new \DateTime('-7 days'),
            '30d' => new \DateTime('-30 days'),
            '90d' => new \DateTime('-90 days'),
            'all' => null,
            default => new \DateTime('-30 days'),
        };
    }
}
