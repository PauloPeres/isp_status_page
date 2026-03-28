<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;

/**
 * Reports Controller
 *
 * Provides CSV report exports for uptime, incidents, and response times (P3-010).
 */
class ReportsController extends AppController
{
    /**
     * Index method - report selection page
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');
    }

    /**
     * Uptime Report - generates CSV of per-monitor uptime by day for the selected date range
     *
     * @return \Cake\Http\Response
     */
    public function uptimeReport()
    {
        $this->request->allowMethod(['get']);
        $this->checkPermission('manage_resources');

        $range = (int)$this->request->getQuery('range', '30');
        if ($range < 1 || $range > 365) {
            $range = 30;
        }

        $startDate = DateTime::now()->subDays($range)->startOfDay()->format('Y-m-d H:i:s');
        $conn = ConnectionManager::get('default');

        $stmt = $conn->execute(
            "SELECT m.name as monitor_name, DATE(mc.checked_at) as check_date,
                    COUNT(*) as total,
                    SUM(CASE WHEN mc.status = 'success' THEN 1 ELSE 0 END) as success_count
             FROM monitor_checks mc
             JOIN monitors m ON m.id = mc.monitor_id
             WHERE m.active = true AND mc.checked_at >= ?
             GROUP BY m.name, DATE(mc.checked_at)
             ORDER BY m.name ASC, check_date ASC",
            [$startDate]
        );

        $csv = "Monitor,Date,Uptime %,Checks,Successful\n";

        foreach ($stmt->fetchAll('assoc') as $row) {
            $total = (int)$row['total'];
            $success = (int)$row['success_count'];
            $uptime = $total > 0 ? round(($success / $total) * 100, 2) : 0;
            $name = str_replace(',', ' ', $row['monitor_name']);
            $csv .= "{$name},{$row['check_date']},{$uptime},{$total},{$success}\n";
        }

        $this->response = $this->response
            ->withType('csv')
            ->withHeader('Content-Disposition', 'attachment; filename="uptime_report.csv"')
            ->withStringBody($csv);

        return $this->response;
    }

    /**
     * Incident Report - generates CSV of incidents
     *
     * @return \Cake\Http\Response
     */
    public function incidentReport()
    {
        $this->request->allowMethod(['get']);
        $this->checkPermission('manage_resources');

        $range = (int)$this->request->getQuery('range', '30');
        if ($range < 1 || $range > 365) {
            $range = 30;
        }

        $startDate = DateTime::now()->subDays($range)->startOfDay()->format('Y-m-d H:i:s');
        $conn = ConnectionManager::get('default');

        $stmt = $conn->execute(
            "SELECT i.id, m.name as monitor_name, i.title, i.status, i.severity,
                    i.created, i.resolved_at
             FROM incidents i
             LEFT JOIN monitors m ON m.id = i.monitor_id
             WHERE i.created >= ?
             ORDER BY i.created DESC",
            [$startDate]
        );

        $csv = "ID,Monitor,Title,Status,Severity,Started,Resolved,Duration (min)\n";

        foreach ($stmt->fetchAll('assoc') as $row) {
            $monitorName = str_replace(',', ' ', $row['monitor_name'] ?? 'N/A');
            $title = str_replace([',', "\n", "\r"], [' ', ' ', ''], $row['title'] ?? '');
            $status = $row['status'] ?? 'open';
            $severity = $row['severity'] ?? 'unknown';
            $started = $row['created'] ?? '';
            $resolved = $row['resolved_at'] ?? '';
            $duration = '';
            if (!empty($row['resolved_at']) && !empty($row['created'])) {
                $startTs = strtotime($row['created']);
                $endTs = strtotime($row['resolved_at']);
                if ($startTs && $endTs) {
                    $duration = (string)round(($endTs - $startTs) / 60, 1);
                }
            }

            $csv .= "{$row['id']},{$monitorName},{$title},{$status},{$severity},{$started},{$resolved},{$duration}\n";
        }

        $this->response = $this->response
            ->withType('csv')
            ->withHeader('Content-Disposition', 'attachment; filename="incident_report.csv"')
            ->withStringBody($csv);

        return $this->response;
    }

    /**
     * Response Time Report - generates CSV of avg response times
     *
     * @return \Cake\Http\Response
     */
    public function responseTimeReport()
    {
        $this->request->allowMethod(['get']);
        $this->checkPermission('manage_resources');

        $range = (int)$this->request->getQuery('range', '30');
        if ($range < 1 || $range > 365) {
            $range = 30;
        }

        $startDate = DateTime::now()->subDays($range)->startOfDay()->format('Y-m-d H:i:s');
        $conn = ConnectionManager::get('default');

        $stmt = $conn->execute(
            "SELECT m.name as monitor_name, DATE(mc.checked_at) as check_date,
                    ROUND(AVG(mc.response_time)::numeric, 2) as avg_rt,
                    ROUND(MIN(mc.response_time)::numeric, 2) as min_rt,
                    ROUND(MAX(mc.response_time)::numeric, 2) as max_rt,
                    COUNT(*) as total
             FROM monitor_checks mc
             JOIN monitors m ON m.id = mc.monitor_id
             WHERE m.active = true AND mc.checked_at >= ? AND mc.response_time IS NOT NULL
             GROUP BY m.name, DATE(mc.checked_at)
             ORDER BY m.name ASC, check_date ASC",
            [$startDate]
        );

        $csv = "Monitor,Date,Avg Response Time (ms),Min Response Time (ms),Max Response Time (ms),Checks\n";

        foreach ($stmt->fetchAll('assoc') as $row) {
            $name = str_replace(',', ' ', $row['monitor_name']);
            $csv .= "{$name},{$row['check_date']},{$row['avg_rt']},{$row['min_rt']},{$row['max_rt']},{$row['total']}\n";
        }

        $this->response = $this->response
            ->withType('csv')
            ->withHeader('Content-Disposition', 'attachment; filename="response_time_report.csv"')
            ->withStringBody($csv);

        return $this->response;
    }
}
