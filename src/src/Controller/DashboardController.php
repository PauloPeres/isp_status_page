<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\MonitorCacheService;
use App\Service\UptimeCalculationService;
use Cake\I18n\DateTime;

/**
 * Dashboard Controller
 *
 * Enhanced admin dashboard with Chart.js charts, summary cards,
 * and real-time monitoring data. Uses UptimeCalculationService
 * for rollup-aware queries and MonitorCacheService for caching.
 */
class DashboardController extends AppController
{
    /**
     * Index method - Enhanced Admin Dashboard
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        $monitorsTable = $this->fetchTable('Monitors');
        $incidentsTable = $this->fetchTable('Incidents');
        $checksTable = $this->fetchTable('MonitorChecks');
        $alertLogsTable = $this->fetchTable('AlertLogs');

        $cacheService = new MonitorCacheService();
        $uptimeService = new UptimeCalculationService();

        // Determine current org ID for cache key (0 for non-tenant context)
        $orgId = 0;
        $identity = $this->request->getAttribute('identity');
        if ($identity && $identity->get('organization_id')) {
            $orgId = (int)$identity->get('organization_id');
        }

        // Cache the summary computation
        $summary = $cacheService->getDashboardSummary($orgId, function () use ($monitorsTable) {
            return [
                'total' => $monitorsTable->find()->count(),
                'up' => $monitorsTable->find()->where(['status' => 'up'])->count(),
                'down' => $monitorsTable->find()->where(['status' => 'down'])->count(),
                'degraded' => $monitorsTable->find()->where(['status' => 'degraded'])->count(),
                'unknown' => $monitorsTable->find()->where(['status' => 'unknown'])->count(),
            ];
        });

        // Active incidents with severity
        $activeIncidents = $incidentsTable->find()
            ->where(['status !=' => 'resolved'])
            ->orderBy(['severity' => 'ASC', 'started_at' => 'DESC'])
            ->all();

        $incidentsBySeverity = [
            'critical' => 0,
            'major' => 0,
            'minor' => 0,
            'maintenance' => 0,
        ];
        foreach ($activeIncidents as $incident) {
            $severity = $incident->severity ?? 'minor';
            if (isset($incidentsBySeverity[$severity])) {
                $incidentsBySeverity[$severity]++;
            }
        }

        // Get active monitors
        $monitors = $monitorsTable->find()
            ->where(['active' => true])
            ->orderBy(['name' => 'ASC'])
            ->all();

        $monitorIds = [];
        foreach ($monitors as $monitor) {
            $monitorIds[] = $monitor->id;
        }

        // Bulk uptime and response time via UptimeCalculationService (single query each)
        $bulkUptime = $uptimeService->getBulkUptime($monitorIds, 1);
        $bulkResponseTimes = $uptimeService->getBulkResponseTimes($monitorIds, 1);

        // Build uptime data from bulk results
        $uptimeData = [];
        foreach ($monitors as $monitor) {
            $uptimeData[] = [
                'name' => $monitor->name,
                'uptime' => $bulkUptime[$monitor->id] ?? 0,
            ];
        }

        // Build response time data from bulk results
        $responseTimeData = [];
        foreach ($monitors as $monitor) {
            $avgResponse = $bulkResponseTimes[$monitor->id] ?? null;

            $responseTimeData[] = [
                'name' => $monitor->name,
                'avg_response_time' => $avgResponse !== null
                    ? round((float)$avgResponse, 0)
                    : 0,
            ];
        }

        // Recent checks table: last 20
        $recentChecks = $checksTable->find()
            ->contain(['Monitors'])
            ->orderBy(['MonitorChecks.checked_at' => 'DESC'])
            ->limit(20)
            ->all();

        // Recent alerts table: last 10
        $recentAlerts = $alertLogsTable->find()
            ->contain(['AlertRules', 'Monitors'])
            ->orderBy(['AlertLogs.created' => 'DESC'])
            ->limit(10)
            ->all();

        $this->set(compact(
            'summary',
            'activeIncidents',
            'incidentsBySeverity',
            'uptimeData',
            'responseTimeData',
            'recentChecks',
            'recentAlerts'
        ));
    }
}
