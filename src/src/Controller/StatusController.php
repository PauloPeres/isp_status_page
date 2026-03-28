<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\SettingService;
use Cake\I18n\DateTime;

/**
 * Status Controller
 *
 * Public status page displaying service health
 */
class StatusController extends AppController
{
    /**
     * Setting service instance
     *
     * @var \App\Service\SettingService
     */
    private SettingService $settingService;

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->settingService = new SettingService();
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

        // Allow public access to all status pages
        $this->Authentication->addUnauthenticatedActions(['index', 'history']);
    }

    /**
     * Index method - Main status page
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // Check if status page is public
        $isPublic = $this->settingService->get('status_page_public', true);

        // If not public, require authentication
        if (!$isPublic) {
            $identity = $this->Authentication->getIdentity();
            if (!$identity) {
                $this->Flash->error(__d('status', 'The status page is not publicly available. Please log in.'));
                return $this->redirect(['controller' => 'Users', 'action' => 'login']);
            }
        }

        $this->viewBuilder()->setLayout('public');

        // Enable caching based on settings
        $cacheSeconds = (int)$this->settingService->get('status_page_cache_seconds', 30);
        $this->response = $this->response
            ->withCache("-{$cacheSeconds} seconds", "+{$cacheSeconds} seconds")
            ->withHeader('Cache-Control', $isPublic ? "public, max-age={$cacheSeconds}" : "private, max-age={$cacheSeconds}");

        // Get all active monitors
        $monitors = $this->fetchTable('Monitors')
            ->find()
            ->where(['active' => true])
            ->orderBy(['name' => 'ASC'])
            ->all();

        // Calculate overall status
        $totalMonitors = $monitors->count();
        $onlineMonitors = 0;
        $offlineMonitors = 0;
        $degradedMonitors = 0;

        foreach ($monitors as $monitor) {
            if ($monitor->status === 'up') {
                $onlineMonitors++;
            } elseif ($monitor->status === 'down') {
                $offlineMonitors++;
            } else {
                $degradedMonitors++;
            }
        }

        // Determine overall system status
        if ($offlineMonitors > 0) {
            if ($offlineMonitors >= $totalMonitors / 2) {
                $systemStatus = 'major-outage';
                $systemMessage = __('We are experiencing major issues');
                $systemIcon = '🔴';
            } else {
                $systemStatus = 'partial-outage';
                $systemMessage = __('Some services are having issues');
                $systemIcon = '🟡';
            }
        } elseif ($degradedMonitors > 0) {
            $systemStatus = 'partial-outage';
            $systemMessage = __('Some services are degraded');
            $systemIcon = '🟡';
        } else {
            $systemStatus = 'all-systems-operational';
            $systemMessage = __('All systems operational');
            $systemIcon = '🟢';
        }

        // Get recent incidents (last 7 days)
        $recentIncidents = $this->fetchTable('Incidents')
            ->find()
            ->where([
                'created >=' => date('Y-m-d H:i:s', strtotime('-7 days'))
            ])
            ->orderBy(['created' => 'DESC'])
            ->limit(5)
            ->all();

        // Load settings
        $siteName = $this->settingService->get('site_name', 'ISP Status');
        $statusPageTitle = $this->settingService->get('status_page_title', 'Service Status');
        $logoUrl = $this->settingService->get('site_logo_url', null);
        $supportEmail = $this->settingService->get('support_email', 'support@example.com');

        // Set HTTP status code based on system status
        if ($systemStatus === 'major-outage') {
            $this->response = $this->response->withStatus(503); // Service Unavailable
        } elseif ($systemStatus === 'partial-outage') {
            $this->response = $this->response->withStatus(200); // Degraded but page renders OK
        }

        // P2-011: Compute 30-day uptime bars for each monitor on the status page
        $monitorIds = [];
        foreach ($monitors as $m) {
            $monitorIds[] = $m->id;
        }

        $monitorsUptimeData = [];
        if (!empty($monitorIds)) {
            $checksTable = $this->fetchTable('MonitorChecks');
            $conn = $checksTable->getConnection();
            $placeholders = implode(',', array_fill(0, count($monitorIds), '?'));
            $since = DateTime::now()->subDays(29)->startOfDay()->format('Y-m-d H:i:s');
            $params = array_merge($monitorIds, [$since]);

            $stmt = $conn->execute(
                "SELECT monitor_id, DATE(checked_at) as check_date,
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count
                 FROM monitor_checks
                 WHERE monitor_id IN ({$placeholders}) AND checked_at >= ?
                 GROUP BY monitor_id, DATE(checked_at)
                 ORDER BY check_date ASC",
                $params
            );
            $dailyByMonitor = [];
            foreach ($stmt->fetchAll('assoc') as $row) {
                $dailyByMonitor[$row['monitor_id']][$row['check_date']] = $row;
            }

            foreach ($monitorIds as $mid) {
                $data = [];
                for ($i = 29; $i >= 0; $i--) {
                    $dayStr = DateTime::now()->subDays($i)->format('Y-m-d');
                    $total = (int)($dailyByMonitor[$mid][$dayStr]['total'] ?? 0);
                    $success = (int)($dailyByMonitor[$mid][$dayStr]['success_count'] ?? 0);
                    $data[] = [
                        'date' => $dayStr,
                        'uptime' => $total > 0 ? ($success / $total) * 100 : 0,
                        'checks' => $total,
                    ];
                }
                $monitorsUptimeData[$mid] = $data;
            }
        }

        // P3-014: Active/upcoming maintenance windows
        $maintenanceWindows = $this->fetchTable('MaintenanceWindows')->find()
            ->where(['OR' => [
                ['MaintenanceWindows.status' => 'scheduled', 'MaintenanceWindows.starts_at >=' => DateTime::now()],
                ['MaintenanceWindows.status' => 'in_progress'],
            ]])
            ->orderBy(['MaintenanceWindows.starts_at' => 'ASC'])
            ->limit(5)
            ->all();

        $this->set(compact(
            'monitors',
            'systemStatus',
            'systemMessage',
            'systemIcon',
            'totalMonitors',
            'onlineMonitors',
            'offlineMonitors',
            'degradedMonitors',
            'recentIncidents',
            'siteName',
            'statusPageTitle',
            'logoUrl',
            'supportEmail',
            'monitorsUptimeData',
            'maintenanceWindows'
        ));
    }

    /**
     * History method - Incident history
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function history()
    {
        // Check if status page is public
        $isPublic = $this->settingService->get('status_page_public', true);

        // If not public, require authentication
        if (!$isPublic) {
            $identity = $this->Authentication->getIdentity();
            if (!$identity) {
                $this->Flash->error(__d('status', 'The status page is not publicly available. Please log in.'));
                return $this->redirect(['controller' => 'Users', 'action' => 'login']);
            }
        }

        $this->viewBuilder()->setLayout('public');

        // Get incidents from last 30 days
        $incidents = $this->fetchTable('Incidents')
            ->find()
            ->where([
                'created >=' => date('Y-m-d H:i:s', strtotime('-30 days'))
            ])
            ->orderBy(['created' => 'DESC'])
            ->all();

        // Group incidents by date
        $groupedIncidents = [];
        foreach ($incidents as $incident) {
            $date = $incident->created->format('Y-m-d');
            if (!isset($groupedIncidents[$date])) {
                $groupedIncidents[$date] = [];
            }
            $groupedIncidents[$date][] = $incident;
        }

        // Load settings for footer
        $siteName = $this->settingService->get('site_name', 'ISP Status');
        $supportEmail = $this->settingService->get('support_email', 'support@example.com');

        $this->set(compact('groupedIncidents', 'siteName', 'supportEmail'));
    }
}
