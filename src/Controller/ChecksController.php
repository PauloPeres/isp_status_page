<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\DateTime;

/**
 * Checks Controller
 *
 * Controller for viewing monitor check history in the admin panel.
 * Allows viewing check logs, filtering by monitor/status/period, and statistics.
 *
 * @property \App\Model\Table\MonitorChecksTable $MonitorChecks
 */
class ChecksController extends AppController
{
    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->MonitorChecks = $this->fetchTable('MonitorChecks');
    }

    /**
     * Index method
     *
     * Lists all monitor checks with optional filters.
     * Supports filtering by monitor, status, and time period.
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        // Build query with filters
        $query = $this->MonitorChecks->find()
            ->contain(['Monitors']);

        // Filter by monitor
        if ($this->request->getQuery('monitor_id')) {
            $query->where(['MonitorChecks.monitor_id' => $this->request->getQuery('monitor_id')]);
        }

        // Filter by status
        if ($this->request->getQuery('status')) {
            $query->where(['MonitorChecks.status' => $this->request->getQuery('status')]);
        }

        // Filter by period
        $period = $this->request->getQuery('period', '24h');
        $periodStart = $this->getPeriodStartDate($period);
        if ($periodStart) {
            $query->where(['MonitorChecks.checked_at >=' => $periodStart]);
        }

        // Order by most recent first
        $query->orderBy(['MonitorChecks.checked_at' => 'DESC']);

        $checks = $this->paginate($query, [
            'limit' => 50,
        ]);

        // Calculate statistics
        $statsQuery = $this->MonitorChecks->find();
        if ($periodStart) {
            $statsQuery->where(['checked_at >=' => $periodStart]);
        }

        $totalChecks = $statsQuery->count();
        $successChecks = $statsQuery->where(['status' => 'success'])->count();
        $failedChecks = $statsQuery->where(['status' => 'failed'])->count();

        $successRate = $totalChecks > 0 ? round(($successChecks / $totalChecks) * 100, 2) : 0;

        // Calculate average response time (only for successful checks)
        $avgResponseTime = $statsQuery
            ->where(['status' => 'success', 'response_time IS NOT' => null])
            ->select(['avg' => $statsQuery->func()->avg('response_time')])
            ->first();

        $stats = [
            'total' => $totalChecks,
            'success' => $successChecks,
            'failed' => $failedChecks,
            'successRate' => $successRate,
            'avgResponseTime' => $avgResponseTime && $avgResponseTime->avg ? round($avgResponseTime->avg, 2) : null,
        ];

        // Get list of monitors for filter dropdown
        $monitors = $this->MonitorChecks->Monitors
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->where(['status' => 'active'])
            ->orderBy(['name' => 'ASC'])
            ->toArray();

        $this->set(compact('checks', 'stats', 'monitors', 'period'));
    }

    /**
     * View method
     *
     * Displays detailed information about a specific check.
     *
     * @param string|null $id Check id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $check = $this->MonitorChecks->get($id, [
            'contain' => ['Monitors'],
        ]);

        // Get surrounding checks for context (5 before, 5 after)
        $previousChecks = $this->MonitorChecks->find()
            ->where([
                'monitor_id' => $check->monitor_id,
                'checked_at <' => $check->checked_at,
            ])
            ->orderBy(['checked_at' => 'DESC'])
            ->limit(5)
            ->all();

        $nextChecks = $this->MonitorChecks->find()
            ->where([
                'monitor_id' => $check->monitor_id,
                'checked_at >' => $check->checked_at,
            ])
            ->orderBy(['checked_at' => 'ASC'])
            ->limit(5)
            ->all();

        // Get monitor statistics
        $monitorStats = [
            'totalChecks' => $this->MonitorChecks->find()
                ->where(['monitor_id' => $check->monitor_id])
                ->count(),
            'successChecks' => $this->MonitorChecks->find()
                ->where(['monitor_id' => $check->monitor_id, 'status' => 'success'])
                ->count(),
            'avgResponseTime' => $this->MonitorChecks->find()
                ->where(['monitor_id' => $check->monitor_id, 'status' => 'success', 'response_time IS NOT' => null])
                ->select(['avg' => $this->MonitorChecks->find()->func()->avg('response_time')])
                ->first(),
        ];

        $this->set(compact('check', 'previousChecks', 'nextChecks', 'monitorStats'));
    }

    /**
     * Get period start date based on period string
     *
     * @param string $period Period string (24h, 7d, 30d, all)
     * @return \Cake\I18n\DateTime|null Start date or null for all
     */
    protected function getPeriodStartDate(string $period): ?DateTime
    {
        return match ($period) {
            '24h' => DateTime::now()->subHours(24),
            '7d' => DateTime::now()->subDays(7),
            '30d' => DateTime::now()->subDays(30),
            'all' => null,
            default => DateTime::now()->subHours(24),
        };
    }
}
