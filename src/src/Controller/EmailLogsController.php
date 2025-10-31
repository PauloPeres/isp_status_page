<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\DateTime;

/**
 * EmailLogs Controller
 *
 * Controller for viewing email logs sent by the system.
 * Uses AlertLogs table filtered by channel='email'.
 *
 * @property \App\Model\Table\AlertLogsTable $AlertLogs
 */
class EmailLogsController extends AppController
{
    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->AlertLogs = $this->fetchTable('AlertLogs');
    }

    /**
     * Index method - List all email logs with filters
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        // Build query - filter only emails
        $query = $this->AlertLogs->find()
            ->where(['AlertLogs.channel' => 'email'])
            ->contain(['Monitors', 'Incidents', 'AlertRules']);

        // Filter by status
        $status = $this->request->getQuery('status');
        if ($status) {
            $query->where(['AlertLogs.status' => $status]);
        }

        // Search by recipient or subject
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $query->where([
                'OR' => [
                    'AlertLogs.recipient LIKE' => '%' . $search . '%',
                    'Monitors.name LIKE' => '%' . $search . '%',
                ],
            ]);
        }

        // Filter by period
        $period = $this->request->getQuery('period', '7d');
        $periodStart = $this->getPeriodStartDate($period);
        if ($periodStart) {
            $query->where(['AlertLogs.created >=' => $periodStart]);
        }

        // Order by most recent first
        $query->orderBy(['AlertLogs.created' => 'DESC']);

        // Paginate
        $this->paginate = [
            'limit' => 50,
        ];
        $emailLogs = $this->paginate($query);

        // Calculate statistics
        $statsQuery = $this->AlertLogs->find()
            ->where(['channel' => 'email']);

        // Apply same period filter to stats
        if ($periodStart) {
            $statsQuery->where(['AlertLogs.created >=' => $periodStart]);
        }

        $total = $statsQuery->count();
        $sent = $statsQuery->where(['status' => 'sent'])->count();
        $failed = $statsQuery->where(['status' => 'failed'])->count();
        $successRate = $total > 0 ? round(($sent / $total) * 100, 2) : 0;

        // Today's emails
        $today = new DateTime('today');
        $todayEmails = $this->AlertLogs->find()
            ->where([
                'channel' => 'email',
                'created >=' => $today,
            ])
            ->count();

        $stats = [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'successRate' => $successRate,
            'today' => $todayEmails,
        ];

        $this->set(compact('emailLogs', 'stats', 'period'));
    }

    /**
     * View method - View email log details
     *
     * @param string|null $id Email log id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $emailLog = $this->AlertLogs->get($id, contain: [
            'Monitors',
            'Incidents',
            'AlertRules',
        ]);

        // Verify it's an email log
        if ($emailLog->channel !== 'email') {
            $this->Flash->error(__('Este não é um log de email.'));
            return $this->redirect(['action' => 'index']);
        }

        $this->set(compact('emailLog'));
    }

    /**
     * Resend email (future implementation)
     *
     * @param string|null $id Email log id.
     * @return \Cake\Http\Response|null Redirects to referer or view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function resend($id = null)
    {
        $this->request->allowMethod(['post']);

        $emailLog = $this->AlertLogs->get($id);

        // Verify it's an email log
        if ($emailLog->channel !== 'email') {
            $this->Flash->error(__('Este não é um log de email.'));
            return $this->redirect(['action' => 'index']);
        }

        // TODO: Implement email resend when EmailService is ready
        $this->Flash->info(__('Funcionalidade de reenvio será implementada quando o serviço de email estiver configurado.'));

        return $this->redirect($this->referer(['action' => 'view', $id]));
    }

    /**
     * Get period start date based on period string
     *
     * @param string $period Period string (24h, 7d, 30d, all)
     * @return \DateTime|null
     */
    private function getPeriodStartDate(string $period): ?\DateTime
    {
        return match ($period) {
            '24h' => new \DateTime('-24 hours'),
            '7d' => new \DateTime('-7 days'),
            '30d' => new \DateTime('-30 days'),
            'all' => null,
            default => new \DateTime('-7 days'),
        };
    }
}
