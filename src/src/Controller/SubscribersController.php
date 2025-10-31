<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Subscribers Controller
 *
 * @property \App\Model\Table\SubscribersTable $Subscribers
 */
class SubscribersController extends AppController
{
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
