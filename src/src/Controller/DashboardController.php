<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\DateTime;

/**
 * Dashboard Controller
 *
 * Enhanced admin dashboard with Chart.js charts, summary cards,
 * and real-time monitoring data.
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

        // Summary cards
        $summary = [
            'total' => $monitorsTable->find()->count(),
            'up' => $monitorsTable->find()->where(['status' => 'up'])->count(),
            'down' => $monitorsTable->find()->where(['status' => 'down'])->count(),
            'degraded' => $monitorsTable->find()->where(['status' => 'degraded'])->count(),
            'unknown' => $monitorsTable->find()->where(['status' => 'unknown'])->count(),
        ];

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

        // Uptime chart data: last 24h uptime % per monitor
        $since24h = DateTime::now()->subHours(24);
        $monitors = $monitorsTable->find()
            ->where(['active' => true])
            ->orderBy(['name' => 'ASC'])
            ->all();

        // Single aggregate query: uptime stats per monitor (eliminates N+1)
        $uptimeStats = $checksTable->find()
            ->select([
                'monitor_id',
                'total' => $checksTable->find()->func()->count('*'),
                'success' => $checksTable->find()->func()->sum(
                    "CASE WHEN status = 'success' THEN 1 ELSE 0 END"
                ),
            ])
            ->where(['checked_at >=' => $since24h])
            ->groupBy(['monitor_id'])
            ->disableAutoFields()
            ->all()
            ->combine('monitor_id', function ($row) {
                return ['total' => $row->total, 'success' => $row->success];
            })
            ->toArray();

        // Single aggregate query: avg response time per monitor (eliminates N+1)
        $responseStats = $checksTable->find()
            ->select([
                'monitor_id',
                'avg_response' => $checksTable->find()->func()->avg('response_time'),
            ])
            ->where(['checked_at >=' => $since24h, 'response_time IS NOT' => null])
            ->groupBy(['monitor_id'])
            ->disableAutoFields()
            ->all()
            ->combine('monitor_id', 'avg_response')
            ->toArray();

        // Build uptime data from aggregate results
        $uptimeData = [];
        foreach ($monitors as $monitor) {
            $stats = $uptimeStats[$monitor->id] ?? null;
            $total = $stats ? (int)$stats['total'] : 0;
            $success = $stats ? (int)$stats['success'] : 0;

            $uptimeData[] = [
                'name' => $monitor->name,
                'uptime' => $total > 0 ? round(($success / $total) * 100, 2) : 0,
            ];
        }

        // Build response time data from aggregate results
        $responseTimeData = [];
        foreach ($monitors as $monitor) {
            $avgResponse = $responseStats[$monitor->id] ?? null;

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
