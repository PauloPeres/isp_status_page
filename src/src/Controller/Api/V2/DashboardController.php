<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\MonitorCacheService;
use App\Service\SlaService;
use App\Service\UptimeCalculationService;

/**
 * DashboardController — API v2
 *
 * Provides dashboard summary data, uptime/response-time chart data,
 * recent checks, and recent alerts for the Angular SPA.
 *
 * TASK-NG-003
 */
class DashboardController extends AppController
{
    protected UptimeCalculationService $uptimeCalculationService;

    public function initialize(): void
    {
        parent::initialize();
        $this->uptimeCalculationService = new UptimeCalculationService();
    }

    /**
     * GET /api/v2/dashboard/summary
     *
     * Returns summary cards: monitor counts by status, active incidents
     * grouped by severity, and SLA summary.
     *
     * @return void
     */
    public function summary(): void
    {
        $this->request->allowMethod(['get']);

        $monitorsTable = $this->fetchTable('Monitors');
        $incidentsTable = $this->fetchTable('Incidents');

        $cacheService = new MonitorCacheService();

        $summary = $cacheService->getDashboardSummary($this->currentOrgId, function () use ($monitorsTable) {
            return [
                'total' => $monitorsTable->find()->count(),
                'up' => $monitorsTable->find()->where(['status' => 'up'])->count(),
                'down' => $monitorsTable->find()->where(['status' => 'down'])->count(),
                'degraded' => $monitorsTable->find()->where(['status' => 'degraded'])->count(),
                'unknown' => $monitorsTable->find()->where(['status' => 'unknown'])->count(),
            ];
        });

        // Active incidents grouped by severity
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

        // SLA summary
        $slaSummary = [];
        try {
            $slaService = new SlaService();
            $slaSummary = $slaService->getDashboardSummary($this->currentOrgId);
        } catch (\Exception $e) {
            // SLA tables may not exist yet
        }

        $this->success([
            'monitors' => $summary,
            'active_incidents' => [
                'total' => count($activeIncidents),
                'by_severity' => $incidentsBySeverity,
            ],
            'sla' => $slaSummary,
        ]);
    }

    /**
     * GET /api/v2/dashboard/uptime
     *
     * Returns per-monitor uptime percentages for chart rendering.
     * Query param: ?days=1 (default 1, max 30)
     *
     * @return void
     */
    public function uptime(): void
    {
        $this->request->allowMethod(['get']);

        $days = min((int)$this->request->getQuery('days', 1), 30);

        $monitorsTable = $this->fetchTable('Monitors');
        $uptimeService = $this->uptimeCalculationService;

        $monitors = $monitorsTable->find()
            ->where(['active' => true])
            ->orderBy(['name' => 'ASC'])
            ->all();

        $monitorIds = [];
        foreach ($monitors as $monitor) {
            $monitorIds[] = $monitor->id;
        }

        $bulkUptime = $uptimeService->getBulkUptime($monitorIds, $days);

        $uptimeData = [];
        foreach ($monitors as $monitor) {
            $uptimeData[] = [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'uptime' => $bulkUptime[$monitor->id] ?? 0,
            ];
        }

        $this->success(['items' => $uptimeData]);
    }

    /**
     * GET /api/v2/dashboard/response-times
     *
     * Returns per-monitor average response times for chart rendering.
     * Query param: ?days=1 (default 1, max 30)
     *
     * @return void
     */
    public function responseTimes(): void
    {
        $this->request->allowMethod(['get']);

        $days = min((int)$this->request->getQuery('days', 1), 30);

        $monitorsTable = $this->fetchTable('Monitors');
        $uptimeService = $this->uptimeCalculationService;

        $monitors = $monitorsTable->find()
            ->where(['active' => true])
            ->orderBy(['name' => 'ASC'])
            ->all();

        $monitorIds = [];
        foreach ($monitors as $monitor) {
            $monitorIds[] = $monitor->id;
        }

        $bulkResponseTimes = $uptimeService->getBulkResponseTimes($monitorIds, $days);

        $responseTimeData = [];
        foreach ($monitors as $monitor) {
            $avgResponse = $bulkResponseTimes[$monitor->id] ?? null;
            $responseTimeData[] = [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'avg_response_time' => $avgResponse !== null
                    ? round((float)$avgResponse, 0)
                    : 0,
            ];
        }

        $this->success(['items' => $responseTimeData]);
    }

    /**
     * GET /api/v2/dashboard/recent-checks
     *
     * Returns the last 20 checks across all monitors.
     *
     * @return void
     */
    public function recentChecks(): void
    {
        $this->request->allowMethod(['get']);

        $checksTable = $this->fetchTable('MonitorChecks');

        $checks = $checksTable->find()
            ->contain(['Monitors'])
            ->orderBy(['MonitorChecks.checked_at' => 'DESC'])
            ->limit(20)
            ->toArray();

        $this->success(['items' => $checks]);
    }

    /**
     * GET /api/v2/dashboard/recent-alerts
     *
     * Returns the last 10 alert log entries.
     *
     * @return void
     */
    public function recentAlerts(): void
    {
        $this->request->allowMethod(['get']);

        $alertLogsTable = $this->fetchTable('AlertLogs');

        $alerts = $alertLogsTable->find()
            ->contain(['AlertRules', 'Monitors'])
            ->orderBy(['AlertLogs.created' => 'DESC'])
            ->limit(10)
            ->toArray();

        $this->success(['items' => $alerts]);
    }
}
