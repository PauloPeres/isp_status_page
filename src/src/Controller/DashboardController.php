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

        $uptimeData = [];
        foreach ($monitors as $monitor) {
            $totalChecks = $checksTable->find()
                ->where([
                    'monitor_id' => $monitor->id,
                    'checked_at >=' => $since24h->toDateTimeString(),
                ])
                ->count();

            $successChecks = $checksTable->find()
                ->where([
                    'monitor_id' => $monitor->id,
                    'status' => 'success',
                    'checked_at >=' => $since24h->toDateTimeString(),
                ])
                ->count();

            $uptimeData[] = [
                'name' => $monitor->name,
                'uptime' => $totalChecks > 0 ? round(($successChecks / $totalChecks) * 100, 2) : 0,
            ];
        }

        // Response time chart data: avg response time per monitor
        $responseTimeData = [];
        foreach ($monitors as $monitor) {
            $avgQuery = $checksTable->find();
            $avgResult = $avgQuery->select([
                    'avg_response' => $avgQuery->func()->avg('response_time'),
                ])
                ->where([
                    'monitor_id' => $monitor->id,
                    'checked_at >=' => $since24h->toDateTimeString(),
                    'response_time IS NOT' => null,
                ])
                ->first();

            $responseTimeData[] = [
                'name' => $monitor->name,
                'avg_response_time' => $avgResult && $avgResult->avg_response
                    ? round((float)$avgResult->avg_response, 0)
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
