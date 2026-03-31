<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * StatusPages Controller
 *
 * Admin CRUD for custom status pages.
 *
 * @property \App\Model\Table\StatusPagesTable $StatusPages
 */
class StatusPagesController extends AppController
{
    /**
     * Before filter callback.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // Allow public access to the show action (public status page)
        $this->Authentication->addUnauthenticatedActions(['show']);
    }

    /**
     * Index method - list all status pages
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        return $this->redirect('/app/status-pages');
    }

    public function add()
    {
        return $this->redirect('/app/status-pages/new');
    }

    public function edit($id = null)
    {
        return $this->redirect('/app/status-pages/' . $id . '/edit');
    }

    public function view($id = null)
    {
        return $this->redirect('/app/status-pages/' . $id);
    }

    /**
     * Show method - public status page rendered by slug (no auth required)
     *
     * @param string $slug Status Page slug.
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function show($slug = null)
    {
        $this->viewBuilder()->setLayout('status_page');

        $statusPage = $this->StatusPages->find()
            ->where(['slug' => $slug, 'active' => true])
            ->first();

        if ($statusPage === null) {
            throw new \Cake\Http\Exception\NotFoundException(__('Status page not found.'));
        }

        // Password protection check
        if ($statusPage->isPasswordProtected()) {
            $session = $this->request->getSession();
            $sessionKey = 'status_page_auth_' . $statusPage->id;

            if (!$session->read($sessionKey)) {
                if ($this->request->is('post')) {
                    $password = $this->request->getData('password');
                    if ($password === $statusPage->password) {
                        $session->write($sessionKey, true);
                    } else {
                        $this->Flash->error(__('Invalid password.'));
                        $this->set(compact('statusPage'));
                        $this->set('requirePassword', true);

                        return;
                    }
                } else {
                    $this->set(compact('statusPage'));
                    $this->set('requirePassword', true);

                    return;
                }
            }
        }

        // Load associated monitors
        $monitorIds = $statusPage->getMonitorIds();
        $monitors = [];
        if (!empty($monitorIds)) {
            $monitorsTable = $this->fetchTable('Monitors');
            $monitors = $monitorsTable->find()
                ->where(['id IN' => $monitorIds, 'active' => true])
                ->orderBy(['name' => 'ASC'])
                ->all()
                ->toArray();
        }

        // Load incidents if show_incident_history is enabled
        $incidents = [];
        if ($statusPage->show_incident_history && !empty($monitorIds)) {
            $incidentsTable = $this->fetchTable('Incidents');
            $incidents = $incidentsTable->find()
                ->contain([
                    'IncidentUpdates' => function ($q) {
                        return $q->where(['IncidentUpdates.is_public' => true])
                            ->orderBy(['IncidentUpdates.created' => 'ASC']);
                    },
                    'Monitors' => ['fields' => ['id', 'name']],
                ])
                ->where(['Incidents.monitor_id IN' => $monitorIds])
                ->orderBy(['Incidents.created' => 'DESC'])
                ->limit(20)
                ->all()
                ->toArray();
        }

        // Calculate overall status
        $allUp = true;
        $anyDown = false;
        foreach ($monitors as $monitor) {
            if ($monitor->status === 'down') {
                $anyDown = true;
                $allUp = false;
            } elseif ($monitor->status !== 'up') {
                $allUp = false;
            }
        }

        if (empty($monitors)) {
            $overallStatus = 'unknown';
            $overallStatusText = __('No monitors configured');
        } elseif ($allUp) {
            $overallStatus = 'up';
            $overallStatusText = __('All Systems Operational');
        } elseif ($anyDown) {
            $overallStatus = 'down';
            $overallStatusText = __('Some Systems Are Down');
        } else {
            $overallStatus = 'degraded';
            $overallStatusText = __('Some Systems Are Degraded');
        }

        // Load 90-day uptime history per monitor when show_uptime_chart is enabled
        $uptimeHistory = [];
        $showUptimeChart = (bool)$statusPage->show_uptime_chart;
        if ($showUptimeChart && !empty($monitors)) {
            $checksTable = $this->fetchTable('MonitorChecks');
            $startDate = date('Y-m-d', strtotime('-90 days'));

            foreach ($monitors as $monitor) {
                $conn = $checksTable->getConnection();
                $stmt = $conn->execute(
                    "SELECT DATE(checked_at) as check_date,
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count
                     FROM monitor_checks
                     WHERE monitor_id = ? AND checked_at >= ?
                     GROUP BY DATE(checked_at)
                     ORDER BY check_date ASC",
                    [$monitor->id, $startDate]
                );
                $rawDays = [];
                foreach ($stmt->fetchAll('assoc') as $row) {
                    $uptime = $row['total'] > 0 ? round(($row['success_count'] / $row['total']) * 100, 1) : 0;
                    $rawDays[$row['check_date']] = [
                        'date' => $row['check_date'],
                        'uptime' => $uptime,
                        'status' => $uptime >= 99 ? 'up' : ($uptime >= 95 ? 'degraded' : ($row['total'] > 0 ? 'down' : 'empty')),
                    ];
                }

                // Pad to 90 days
                $days = [];
                for ($i = 89; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-{$i} days"));
                    if (isset($rawDays[$date])) {
                        $days[] = $rawDays[$date];
                    } else {
                        $days[] = [
                            'date' => $date,
                            'uptime' => 0,
                            'status' => 'empty',
                        ];
                    }
                }
                $uptimeHistory[$monitor->id] = $days;
            }
        }

        $showIncidentHistory = (bool)$statusPage->show_incident_history;

        // Load upcoming + active maintenance windows
        $maintenanceWindows = [];
        try {
            $mwTable = $this->fetchTable('MaintenanceWindows');
            $now = new \Cake\I18n\DateTime();
            $query = $mwTable->find()
                ->where([
                    'MaintenanceWindows.organization_id' => $statusPage->organization_id,
                    'OR' => [
                        ['MaintenanceWindows.ends_at >=' => $now], // upcoming or active
                        ['MaintenanceWindows.is_recurring' => true, 'MaintenanceWindows.status' => 'scheduled'],
                    ],
                ])
                ->orderBy(['MaintenanceWindows.starts_at' => 'ASC'])
                ->limit(10);
            $maintenanceWindows = $query->all()->toArray();
        } catch (\Exception $e) {
            // Maintenance table may not exist
        }

        // Build 14-day timeline from incidents
        $timeline = [];
        foreach ($incidents as $incident) {
            $date = $incident->created->format('Y-m-d');
            if (!isset($timeline[$date])) {
                $timeline[$date] = [];
            }
            $timeline[$date][] = [
                'type' => 'incident',
                'time' => $incident->created->format('H:i'),
                'title' => $incident->title,
                'severity' => $incident->severity ?? 'minor',
                'status' => $incident->status ?? 'investigating',
                'description' => $incident->description,
                'updates' => $incident->incident_updates ?? [],
                'monitor_name' => $incident->monitor->name ?? '',
            ];
        }
        // Pad timeline to 14 days
        for ($i = 0; $i < 14; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            if (!isset($timeline[$date])) {
                $timeline[$date] = [];
            }
        }
        krsort($timeline); // newest first

        // Theme/branding
        $theme = [];
        if (!empty($statusPage->theme)) {
            $decoded = is_string($statusPage->theme) ? json_decode($statusPage->theme, true) : $statusPage->theme;
            if (is_array($decoded)) {
                $theme = $decoded;
            }
        }

        $this->set(compact(
            'statusPage',
            'monitors',
            'incidents',
            'overallStatus',
            'overallStatusText',
            'uptimeHistory',
            'showUptimeChart',
            'showIncidentHistory',
            'maintenanceWindows',
            'timeline',
            'theme'
        ));
        $this->set('requirePassword', false);
    }

    public function delete($id = null)
    {
        return $this->redirect('/app/status-pages');
    }
}
