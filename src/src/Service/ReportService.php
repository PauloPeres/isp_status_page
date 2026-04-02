<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * ReportService
 *
 * Generates CSV reports for uptime, incidents, and response times.
 */
class ReportService
{
    /**
     * Generate uptime CSV report.
     */
    public function generateUptimeCsv(int $orgId, ?string $from, ?string $to): string
    {
        $monitorsTable = TableRegistry::getTableLocator()->get('Monitors');
        $checksTable = TableRegistry::getTableLocator()->get('MonitorChecks');

        $monitors = $monitorsTable->find()
            ->where(['Monitors.organization_id' => $orgId])
            ->orderBy(['Monitors.name' => 'ASC'])
            ->all();

        $fromDate = $from ? new \DateTime($from) : new \DateTime('-30 days');
        $toDate = $to ? new \DateTime($to) : new \DateTime();

        $rows = [];
        foreach ($monitors as $monitor) {
            $total = $checksTable->find()
                ->where([
                    'monitor_id' => $monitor->id,
                    'created >=' => $fromDate->format('Y-m-d H:i:s'),
                    'created <=' => $toDate->format('Y-m-d 23:59:59'),
                ])
                ->count();

            $success = $checksTable->find()
                ->where([
                    'monitor_id' => $monitor->id,
                    'status' => 'success',
                    'created >=' => $fromDate->format('Y-m-d H:i:s'),
                    'created <=' => $toDate->format('Y-m-d 23:59:59'),
                ])
                ->count();

            $uptime = $total > 0 ? round(($success / $total) * 100, 2) : 0;

            $rows[] = [
                $monitor->name,
                $monitor->type,
                $monitor->target,
                $total,
                $success,
                $total - $success,
                $uptime . '%',
            ];
        }

        return $this->buildCsv(
            ['Monitor', 'Type', 'Target', 'Total Checks', 'Successful', 'Failed', 'Uptime %'],
            $rows
        );
    }

    /**
     * Generate incidents CSV report.
     */
    public function generateIncidentsCsv(int $orgId, ?string $from, ?string $to): string
    {
        $incidentsTable = TableRegistry::getTableLocator()->get('Incidents');

        $fromDate = $from ? new \DateTime($from) : new \DateTime('-30 days');
        $toDate = $to ? new \DateTime($to) : new \DateTime();

        $incidents = $incidentsTable->find()
            ->contain(['Monitors' => ['fields' => ['id', 'name']]])
            ->where([
                'Incidents.organization_id' => $orgId,
                'Incidents.started_at >=' => $fromDate->format('Y-m-d H:i:s'),
                'Incidents.started_at <=' => $toDate->format('Y-m-d 23:59:59'),
            ])
            ->orderBy(['Incidents.started_at' => 'DESC'])
            ->all();

        $rows = [];
        foreach ($incidents as $incident) {
            $duration = '';
            if ($incident->resolved_at) {
                $diff = $incident->started_at->diff($incident->resolved_at);
                $duration = $diff->format('%dd %hh %im');
            }

            $rows[] = [
                $incident->monitor->name ?? 'N/A',
                $incident->title ?? '',
                $incident->severity ?? '',
                $incident->status,
                $incident->started_at->format('Y-m-d H:i:s'),
                $incident->resolved_at ? $incident->resolved_at->format('Y-m-d H:i:s') : 'Ongoing',
                $duration,
            ];
        }

        return $this->buildCsv(
            ['Monitor', 'Title', 'Severity', 'Status', 'Started', 'Resolved', 'Duration'],
            $rows
        );
    }

    /**
     * Generate response times CSV report.
     */
    public function generateResponseTimesCsv(int $orgId, ?string $from, ?string $to): string
    {
        $monitorsTable = TableRegistry::getTableLocator()->get('Monitors');
        $checksTable = TableRegistry::getTableLocator()->get('MonitorChecks');

        $monitors = $monitorsTable->find()
            ->where(['Monitors.organization_id' => $orgId])
            ->orderBy(['Monitors.name' => 'ASC'])
            ->all();

        $fromDate = $from ? new \DateTime($from) : new \DateTime('-30 days');
        $toDate = $to ? new \DateTime($to) : new \DateTime();

        $rows = [];
        foreach ($monitors as $monitor) {
            $query = $checksTable->find()
                ->where([
                    'monitor_id' => $monitor->id,
                    'response_time IS NOT' => null,
                    'created >=' => $fromDate->format('Y-m-d H:i:s'),
                    'created <=' => $toDate->format('Y-m-d 23:59:59'),
                ]);

            $stats = $checksTable->find()
                ->where([
                    'monitor_id' => $monitor->id,
                    'response_time IS NOT' => null,
                    'created >=' => $fromDate->format('Y-m-d H:i:s'),
                    'created <=' => $toDate->format('Y-m-d 23:59:59'),
                ])
                ->select([
                    'avg_rt' => $query->func()->avg('response_time'),
                    'min_rt' => $query->func()->min('response_time'),
                    'max_rt' => $query->func()->max('response_time'),
                    'check_count' => $query->func()->count('id'),
                ])
                ->first();

            if ($stats && $stats->check_count > 0) {
                $rows[] = [
                    $monitor->name,
                    $monitor->type,
                    round((float)$stats->avg_rt, 1) . 'ms',
                    $stats->min_rt . 'ms',
                    $stats->max_rt . 'ms',
                    $stats->check_count,
                ];
            }
        }

        return $this->buildCsv(
            ['Monitor', 'Type', 'Avg Response Time', 'Min', 'Max', 'Total Checks'],
            $rows
        );
    }

    /**
     * Build CSV string from headers and rows.
     */
    private function buildCsv(array $headers, array $rows): string
    {
        $output = fopen('php://temp', 'r+');

        // UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, $headers);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
