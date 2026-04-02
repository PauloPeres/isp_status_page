<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDF Report Service
 *
 * Generates branded PDF reports using Dompdf.
 */
class PdfReportService
{
    /**
     * Generate a branded SLA compliance PDF report.
     */
    public function generateSlaPdf(array $sla, array $reportData): string
    {
        $brandName = Configure::read('Brand.name', 'KeepUp');
        $brandUrl = Configure::read('Brand.websiteUrl', 'https://usekeeup.com');

        $status = $reportData['status'] ?? 'unknown';
        $statusLabel = ucfirst(str_replace('_', ' ', $status));
        $statusColor = match ($status) {
            'compliant' => '#00C853',
            'at_risk' => '#F9A825',
            'breached' => '#FF1744',
            default => '#6B7280',
        };

        $actualUptime = number_format((float)($reportData['actual_uptime'] ?? 0), 3);
        $targetUptime = number_format((float)($sla['target_uptime'] ?? 99.9), 2);
        $downtimeMin = number_format((float)($reportData['downtime_minutes'] ?? 0), 1);
        $allowedMin = number_format((float)($reportData['allowed_downtime_minutes'] ?? 0), 1);
        $remainingMin = number_format((float)($reportData['remaining_downtime_minutes'] ?? 0), 1);
        $incidents = (int)($reportData['incidents_count'] ?? 0);
        $longestIncident = $this->formatDuration((float)($reportData['longest_incident_minutes'] ?? 0));
        $totalChecks = (int)($reportData['total_checks'] ?? 0);
        $successChecks = (int)($reportData['successful_checks'] ?? 0);
        $failedChecks = (int)($reportData['failed_checks'] ?? 0);
        $periodStart = $reportData['period_start'] ?? date('Y-m-01');
        $periodEnd = $reportData['period_end'] ?? date('Y-m-t');
        $slaName = htmlspecialchars($sla['name'] ?? 'SLA Report');
        $monitorName = htmlspecialchars($sla['monitor']['name'] ?? $sla['monitor_name'] ?? 'Monitor');
        $period = ucfirst($sla['measurement_period'] ?? 'monthly');
        $generatedDate = date('F j, Y \a\t g:i A T');

        $budgetPct = ((float)$allowedMin > 0) ? min(100, round((float)$downtimeMin / (float)$allowedMin * 100, 1)) : 0;
        $budgetBarColor = $budgetPct <= 50 ? '#00C853' : ($budgetPct <= 80 ? '#F9A825' : '#FF1744');

        // Performance metrics
        $mtbfMinutes = (float)($reportData['mtbf_minutes'] ?? 0);
        $mttrMinutes = (float)($reportData['mttr_minutes'] ?? 0);
        $maintMinutes = (float)($reportData['maintenance_minutes'] ?? 0);
        $avgResponseMs = (float)($reportData['avg_response_ms'] ?? 0);
        $p95ResponseMs = (float)($reportData['p95_response_ms'] ?? 0);
        $maxResponseMs = (float)($reportData['max_response_ms'] ?? 0);

        // Calculate availability excluding maintenance
        $totalMinutes = (float)($reportData['total_minutes'] ?? 1);
        $downtimeRaw = (float)($reportData['downtime_minutes'] ?? 0);
        $availExclMaint = ($totalMinutes - $maintMinutes) > 0
            ? round((($totalMinutes - $maintMinutes - $downtimeRaw) / ($totalMinutes - $maintMinutes)) * 100, 3)
            : 100;

        // ===== Performance Metrics Section =====
        $mtbfFormatted = $this->formatDuration($mtbfMinutes);
        $mttrFormatted = $this->formatDuration($mttrMinutes);
        $avgRespColor = $this->responseColor($avgResponseMs);

        $performanceHtml = <<<PERF
<div class="section-title">Performance Metrics</div>
<div class="metrics-grid">
    <div class="metric-row">
        <div class="metric-cell">
            <div class="metric-value">{$mtbfFormatted}</div>
            <div class="metric-label">MTBF</div>
        </div>
        <div class="metric-cell">
            <div class="metric-value">{$mttrFormatted}</div>
            <div class="metric-label">MTTR</div>
        </div>
        <div class="metric-cell">
            <div class="metric-value">{$availExclMaint}%</div>
            <div class="metric-label">Avail. (excl. maint.)</div>
        </div>
        <div class="metric-cell">
            <div class="metric-value" style="color:{$avgRespColor};">{$avgResponseMs}ms</div>
            <div class="metric-label">Avg Response</div>
        </div>
    </div>
</div>
PERF;

        // ===== Response Times Section =====
        $avgColor = $this->responseColor($avgResponseMs);
        $p95Color = $this->responseColor($p95ResponseMs);
        $maxColor = $this->responseColor($maxResponseMs);
        $avgResp = number_format($avgResponseMs, 0);
        $p95Resp = number_format($p95ResponseMs, 0);
        $maxResp = number_format($maxResponseMs, 0);

        $responseHtml = <<<RESP
<div class="section-title">Response Times</div>
<table class="daily-table">
    <thead><tr><th>Metric</th><th>Value</th></tr></thead>
    <tbody>
        <tr><td>Average</td><td style="color:{$avgColor};font-weight:700;">{$avgResp}ms</td></tr>
        <tr><td>P95</td><td style="color:{$p95Color};font-weight:700;">{$p95Resp}ms</td></tr>
        <tr><td>Max</td><td style="color:{$maxColor};font-weight:700;">{$maxResp}ms</td></tr>
    </tbody>
</table>
RESP;

        // ===== Incidents List Section =====
        $incidentsListHtml = '';
        $incidentsList = $reportData['incidents'] ?? [];
        if (empty($incidentsList)) {
            $incidentsListHtml = <<<INC
<div class="section-title">Incidents</div>
<p style="text-align:center;color:#00C853;padding:12px 0;font-weight:600;">No incidents during this period</p>
INC;
        } else {
            $incidentsListHtml = '<div class="section-title">Incidents</div>';
            $incidentsListHtml .= '<table class="daily-table"><thead><tr><th>Title</th><th>Severity</th><th>Status</th><th>Started</th><th>Resolved</th><th>Duration</th></tr></thead><tbody>';
            foreach ($incidentsList as $inc) {
                $incTitle = htmlspecialchars($inc['title'] ?? '');
                $incSeverity = htmlspecialchars($inc['severity'] ?? '');
                $incStatus = htmlspecialchars($inc['status'] ?? '');
                $incStarted = $inc['started_at'] ?? '';
                $incResolved = $inc['resolved_at'] ?? 'Ongoing';
                $incDuration = $this->formatDuration((float)($inc['duration_minutes'] ?? 0));

                $sevColor = match (strtolower($incSeverity)) {
                    'critical' => '#FF1744',
                    'major' => '#F9A825',
                    default => '#6B7280',
                };
                $statColor = match (strtolower($incStatus)) {
                    'resolved' => '#00C853',
                    'investigating' => '#F9A825',
                    'open' => '#FF1744',
                    default => '#6B7280',
                };

                $incidentsListHtml .= "<tr>";
                $incidentsListHtml .= "<td>{$incTitle}</td>";
                $incidentsListHtml .= "<td><span style=\"background:{$sevColor};color:#fff;padding:2px 8px;border-radius:10px;font-size:8pt;font-weight:600;\">{$incSeverity}</span></td>";
                $incidentsListHtml .= "<td><span style=\"background:{$statColor};color:#fff;padding:2px 8px;border-radius:10px;font-size:8pt;font-weight:600;\">{$incStatus}</span></td>";
                $incidentsListHtml .= "<td>{$incStarted}</td>";
                $incidentsListHtml .= "<td>{$incResolved}</td>";
                $incidentsListHtml .= "<td>{$incDuration}</td>";
                $incidentsListHtml .= "</tr>";
            }
            $incidentsListHtml .= '</tbody></table>';
        }

        // ===== Daily Breakdown =====
        $dailyHtml = '';
        if (!empty($reportData['daily_breakdown'])) {
            $dailyHtml = '<div class="section-title">Daily Availability</div>';
            $dailyHtml .= '<table class="daily-table"><thead><tr><th>Date</th><th>Uptime</th><th>Downtime</th><th>Incidents</th><th>Checks</th></tr></thead><tbody>';
            foreach ($reportData['daily_breakdown'] as $day) {
                $dayUptime = number_format((float)($day['uptime'] ?? 100), 2);
                $dayColor = ($dayUptime >= (float)$targetUptime) ? '#00C853' : (($dayUptime >= 98) ? '#F9A825' : '#FF1744');
                $dayDown = isset($day['downtime_minutes']) ? $this->formatDuration((float)$day['downtime_minutes']) : '0m';
                $dayInc = (int)($day['incidents'] ?? 0);
                $dayChecks = (int)($day['checks'] ?? 0);
                $date = $day['date'] ?? '';
                $dailyHtml .= "<tr><td>{$date}</td><td style=\"color:{$dayColor};font-weight:600\">{$dayUptime}%</td><td>{$dayDown}</td><td>{$dayInc}</td><td>{$dayChecks}</td></tr>";
            }
            $dailyHtml .= '</tbody></table>';
        }

        $totalDownFormatted = $this->formatDuration($downtimeRaw);

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 50px 40px; }
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1A1A2E; font-size: 11pt; line-height: 1.5; }
    .header { text-align: center; border-bottom: 3px solid #1A2332; padding-bottom: 16px; margin-bottom: 24px; }
    .brand { font-size: 22pt; font-weight: 900; color: #1A2332; letter-spacing: 1px; margin-bottom: 4px; }
    .report-title { font-size: 16pt; font-weight: 700; color: #1A2332; margin: 4px 0; }
    .report-meta { font-size: 9pt; color: #6B7280; margin: 2px 0; }

    .status-banner { text-align: center; padding: 20px; border-radius: 8px; margin: 20px 0; }
    .status-label { font-size: 18pt; font-weight: 800; }
    .status-desc { font-size: 10pt; margin-top: 4px; }

    .metrics-grid { display: table; width: 100%; margin: 20px 0; }
    .metric-row { display: table-row; }
    .metric-cell { display: table-cell; width: 25%; text-align: center; padding: 12px 8px; }
    .metric-value { font-size: 20pt; font-weight: 700; }
    .metric-label { font-size: 8pt; color: #6B7280; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }

    .budget-section { margin: 20px 0; padding: 16px; background: #F8F9FB; border-radius: 8px; }
    .budget-title { font-size: 10pt; font-weight: 700; color: #1A2332; margin-bottom: 8px; }
    .budget-bar { height: 16px; background: #E2E8F0; border-radius: 8px; overflow: hidden; margin: 8px 0; }
    .budget-fill { height: 100%; border-radius: 8px; }
    .budget-text { font-size: 9pt; color: #6B7280; }

    .section-title { font-size: 13pt; font-weight: 700; color: #1A2332; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 2px solid #E2E8F0; }

    .info-table { width: 100%; border-collapse: collapse; margin: 12px 0; }
    .info-table td { padding: 8px 12px; border-bottom: 1px solid #E2E8F0; font-size: 10pt; }
    .info-table td:first-child { color: #6B7280; width: 40%; }
    .info-table td:last-child { font-weight: 600; }

    .daily-table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 9pt; }
    .daily-table thead { background: #1A2332; color: #fff; }
    .daily-table th { padding: 8px 12px; text-align: left; font-weight: 600; }
    .daily-table td { padding: 6px 12px; border-bottom: 1px solid #E2E8F0; }
    .daily-table tbody tr:nth-child(even) { background: #F8F9FB; }

    .footer { text-align: center; margin-top: 30px; padding-top: 16px; border-top: 1px solid #E2E8F0; font-size: 8pt; color: #9CA3AF; }
</style>
</head>
<body>

<div class="header">
    <div class="brand">{$brandName}</div>
    <div class="report-title">{$slaName} &mdash; SLA Compliance Report</div>
    <div class="report-meta">Period: {$periodStart} &mdash; {$periodEnd} &bull; {$period}</div>
    <div class="report-meta">Generated: {$generatedDate}</div>
</div>

<div class="status-banner" style="background: {$statusColor}15; border: 2px solid {$statusColor};">
    <div class="status-label" style="color: {$statusColor};">{$statusLabel}</div>
    <div class="status-desc">Monitor: {$monitorName}</div>
</div>

<div class="metrics-grid">
    <div class="metric-row">
        <div class="metric-cell">
            <div class="metric-value" style="color: {$statusColor};">{$actualUptime}%</div>
            <div class="metric-label">Actual Uptime</div>
        </div>
        <div class="metric-cell">
            <div class="metric-value">{$targetUptime}%</div>
            <div class="metric-label">Target Uptime</div>
        </div>
        <div class="metric-cell">
            <div class="metric-value">{$totalDownFormatted}</div>
            <div class="metric-label">Total Downtime</div>
        </div>
    </div>
    <div class="metric-row">
        <div class="metric-cell">
            <div class="metric-value">{$incidents}</div>
            <div class="metric-label">Incidents</div>
        </div>
        <div class="metric-cell">
            <div class="metric-value">{$longestIncident}</div>
            <div class="metric-label">Longest Incident</div>
        </div>
        <div class="metric-cell">
            <div class="metric-value">{$totalChecks}</div>
            <div class="metric-label">Total Checks ({$successChecks}/{$failedChecks})</div>
        </div>
    </div>
</div>

<div class="budget-section">
    <div class="budget-title">Downtime Budget</div>
    <div class="budget-bar">
        <div class="budget-fill" style="width: {$budgetPct}%; background: {$budgetBarColor};"></div>
    </div>
    <div class="budget-text">{$downtimeMin} min used of {$allowedMin} min allowed &bull; {$budgetPct}% consumed &bull; {$remainingMin} min remaining</div>
</div>

{$performanceHtml}

{$responseHtml}

{$incidentsListHtml}

{$dailyHtml}

<div class="section-title">SLA Definition</div>
<table class="info-table">
    <tr><td>SLA Name</td><td>{$slaName}</td></tr>
    <tr><td>Monitor</td><td>{$monitorName}</td></tr>
    <tr><td>Target Uptime</td><td>{$targetUptime}%</td></tr>
    <tr><td>Measurement Period</td><td>{$period}</td></tr>
    <tr><td>Total Downtime</td><td>{$downtimeMin} minutes</td></tr>
    <tr><td>Allowed Downtime</td><td>{$allowedMin} minutes</td></tr>
</table>

<div class="footer">
    Generated by {$brandName} ({$brandUrl}) &bull; {$generatedDate}
</div>

</body>
</html>
HTML;

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Format minutes as human-readable duration.
     */
    private function formatDuration(float $minutes): string
    {
        if ($minutes <= 0) {
            return '0m';
        }
        $d = floor($minutes / 1440);
        $h = floor(($minutes % 1440) / 60);
        $m = floor($minutes % 60);
        $parts = [];
        if ($d > 0) {
            $parts[] = "{$d}d";
        }
        if ($h > 0) {
            $parts[] = "{$h}h";
        }
        if ($m > 0 || empty($parts)) {
            $parts[] = "{$m}m";
        }

        return implode(' ', $parts);
    }

    /**
     * Return a color for a response time value.
     */
    private function responseColor(float $ms): string
    {
        if ($ms < 200) {
            return '#00C853';
        }
        if ($ms < 500) {
            return '#F9A825';
        }

        return '#FF1744';
    }
}
