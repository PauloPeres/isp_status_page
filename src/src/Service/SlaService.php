<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\SlaDefinition;
use App\Model\Entity\SlaReport;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * SLA Service
 *
 * Calculates SLA compliance, generates reports, and provides
 * dashboard summary data for SLA tracking.
 */
class SlaService
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * Calculate current SLA status for a monitor within a given period.
     *
     * Uses UptimeCalculationService to leverage rollup data for efficiency.
     *
     * @param int $monitorId Monitor ID
     * @param string $period Measurement period (monthly, quarterly, yearly)
     * @param float $targetUptime Target uptime percentage
     * @param float|null $warningThreshold Warning threshold percentage
     * @return array SLA calculation results
     */
    public function calculateCurrentSla(
        int $monitorId,
        string $period = 'monthly',
        float $targetUptime = 99.9,
        ?float $warningThreshold = null,
        bool $detailed = false
    ): array {
        $periodDates = $this->getPeriodDates($period);
        $periodStart = $periodDates['start'];
        $periodEnd = $periodDates['end'];

        // Calculate total minutes in the period (up to now if period is current)
        $now = new DateTime();
        $effectiveEnd = $periodEnd->format('Y-m-d') > $now->format('Y-m-d')
            ? $now
            : new DateTime($periodEnd->format('Y-m-d') . ' 23:59:59');
        $startDt = new DateTime($periodStart->format('Y-m-d') . ' 00:00:00');

        $totalMinutes = max(1, (int)round(($effectiveEnd->getTimestamp() - $startDt->getTimestamp()) / 60));

        // Calculate days in the period for the uptime service query
        $days = max(1, (int)ceil($totalMinutes / (24 * 60)));

        // Use UptimeCalculationService for rollup-aware uptime calculation
        $uptimeService = new UptimeCalculationService();
        $actualUptime = $uptimeService->getUptime($monitorId, $days);

        // Calculate downtime from uptime percentage
        $downtimeMinutes = round($totalMinutes * (100 - $actualUptime) / 100, 2);
        $allowedDowntimeMinutes = round($totalMinutes * (100 - $targetUptime) / 100, 2);
        $remainingDowntimeMinutes = max(0, round($allowedDowntimeMinutes - $downtimeMinutes, 2));

        // Fetch incidents in this period
        $incidentsTable = $this->fetchTable('Incidents');
        $incidents = $incidentsTable->find()
            ->where([
                'Incidents.monitor_id' => $monitorId,
                'Incidents.started_at >=' => $startDt->format('Y-m-d H:i:s'),
                'Incidents.started_at <=' => $effectiveEnd->format('Y-m-d H:i:s'),
            ])
            ->orderBy(['Incidents.started_at' => 'DESC'])
            ->all();

        $incidentsCount = $incidents->count();
        $longestIncidentMinutes = 0;
        $totalIncidentMinutes = 0;
        $incidentsList = [];
        foreach ($incidents as $incident) {
            $incEnd = $incident->resolved_at ?? $effectiveEnd;
            $incDuration = max(0, ($incEnd->getTimestamp() - $incident->started_at->getTimestamp()) / 60);
            $totalIncidentMinutes += $incDuration;
            if ($incDuration > $longestIncidentMinutes) {
                $longestIncidentMinutes = $incDuration;
            }
            $incidentsList[] = [
                'id' => $incident->id,
                'title' => $incident->title,
                'severity' => $incident->severity,
                'status' => $incident->status,
                'started_at' => $incident->started_at->format('Y-m-d H:i:s'),
                'resolved_at' => $incident->resolved_at ? $incident->resolved_at->format('Y-m-d H:i:s') : null,
                'duration_minutes' => round($incDuration, 1),
            ];
        }

        // MTBF / MTTR
        $mtbfMinutes = $incidentsCount > 0 ? ($totalMinutes - $totalIncidentMinutes) / $incidentsCount : $totalMinutes;
        $mttrMinutes = $incidentsCount > 0 ? $totalIncidentMinutes / $incidentsCount : 0;

        // Check stats for response times and totals
        $checksTable = $this->fetchTable('MonitorChecks');
        $checkStats = $checksTable->find()
            ->where([
                'monitor_id' => $monitorId,
                'created >=' => $startDt->format('Y-m-d H:i:s'),
                'created <=' => $effectiveEnd->format('Y-m-d H:i:s'),
            ])
            ->select([
                'total' => $checksTable->find()->func()->count('*'),
                'success_count' => $checksTable->find()->func()->sum(
                    'CASE WHEN status = \'success\' THEN 1 ELSE 0 END'
                ),
                'failure_count' => $checksTable->find()->func()->sum(
                    'CASE WHEN status = \'failure\' THEN 1 ELSE 0 END'
                ),
                'avg_response' => $checksTable->find()->func()->avg('response_time'),
                'max_response' => $checksTable->find()->func()->max('response_time'),
            ])
            ->first();

        $totalChecks = (int)($checkStats->total ?? 0);
        $successChecks = (int)($checkStats->success_count ?? 0);
        $failedChecks = (int)($checkStats->failure_count ?? 0);
        $avgResponse = $checkStats->avg_response ? round((float)$checkStats->avg_response, 1) : null;
        $maxResponse = $checkStats->max_response ? (int)$checkStats->max_response : null;

        // P95 response time
        $p95Response = null;
        if ($totalChecks > 0) {
            $p95Row = $checksTable->find()
                ->where([
                    'monitor_id' => $monitorId,
                    'created >=' => $startDt->format('Y-m-d H:i:s'),
                    'created <=' => $effectiveEnd->format('Y-m-d H:i:s'),
                    'response_time IS NOT' => null,
                ])
                ->orderBy(['response_time' => 'ASC'])
                ->offset((int)floor($totalChecks * 0.95))
                ->limit(1)
                ->first();
            $p95Response = $p95Row ? (int)$p95Row->response_time : null;
        }

        // Daily breakdown (skip when not detailed to avoid timeout on long periods)
        $dailyBreakdown = [];
        if ($detailed) {
            $cursor = clone $startDt;
            while ($cursor <= $effectiveEnd) {
                $dayStr = $cursor->format('Y-m-d');
                $dayStart = $dayStr . ' 00:00:00';
                $dayEnd = $dayStr . ' 23:59:59';

                $dayStats = $checksTable->find()
                    ->where([
                        'monitor_id' => $monitorId,
                        'created >=' => $dayStart,
                        'created <=' => $dayEnd,
                    ])
                    ->select([
                        'total' => $checksTable->find()->func()->count('*'),
                        'success_count' => $checksTable->find()->func()->sum(
                            'CASE WHEN status = \'success\' THEN 1 ELSE 0 END'
                        ),
                    ])
                    ->first();

                $dayTotal = (int)($dayStats->total ?? 0);
                $daySuccess = (int)($dayStats->success_count ?? 0);
                $dayUptime = $dayTotal > 0 ? round(($daySuccess / $dayTotal) * 100, 2) : null;
                $dayDownMinutes = $dayTotal > 0 ? round(1440 * (100 - $dayUptime) / 100, 1) : 0;

                $dayIncidents = $incidentsTable->find()
                    ->where([
                        'Incidents.monitor_id' => $monitorId,
                        'Incidents.started_at >=' => $dayStart,
                        'Incidents.started_at <=' => $dayEnd,
                    ])
                    ->count();

                $dailyBreakdown[] = [
                    'date' => $dayStr,
                    'uptime' => $dayUptime,
                    'downtime_minutes' => $dayDownMinutes,
                    'incidents' => $dayIncidents,
                    'checks' => $dayTotal,
                ];

                $cursor->modify('+1 day');
            }
        }

        // Determine status
        $status = $this->determineStatus($actualUptime, $targetUptime, $warningThreshold);

        // Clamp values to valid range (0-100%)
        $actualUptime = round(min(100, max(0, $actualUptime)), 3);
        $targetUptime = round(min(100, max(0, $targetUptime)), 3);

        return [
            'target_uptime' => $targetUptime,
            'actual_uptime' => $actualUptime,
            'total_minutes' => $totalMinutes,
            'downtime_minutes' => $downtimeMinutes,
            'allowed_downtime_minutes' => $allowedDowntimeMinutes,
            'remaining_downtime_minutes' => $remainingDowntimeMinutes,
            'status' => $status,
            'incidents_count' => $incidentsCount,
            'longest_incident_minutes' => round($longestIncidentMinutes, 1),
            'total_checks' => $totalChecks,
            'successful_checks' => $successChecks,
            'failed_checks' => $failedChecks,
            'mtbf_minutes' => round($mtbfMinutes, 1),
            'mttr_minutes' => round($mttrMinutes, 1),
            'maintenance_minutes' => 0,
            'avg_response_ms' => $avgResponse,
            'p95_response_ms' => $p95Response,
            'max_response_ms' => $maxResponse,
            'incidents' => $incidentsList,
            'daily_breakdown' => $dailyBreakdown,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
        ];
    }

    /**
     * Generate or update an SLA report for the current period.
     *
     * @param int $slaDefinitionId SLA definition ID
     * @return \App\Model\Entity\SlaReport|null The generated report, or null on failure
     */
    public function generateReport(int $slaDefinitionId): ?SlaReport
    {
        $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');
        $slaReportsTable = $this->fetchTable('SlaReports');

        try {
            /** @var \App\Model\Entity\SlaDefinition $slaDef */
            $slaDef = $slaDefinitionsTable->get($slaDefinitionId, contain: ['Monitors']);
        } catch (\Exception $e) {
            $this->log("SLA definition {$slaDefinitionId} not found: " . $e->getMessage(), 'error');

            return null;
        }

        $slaData = $this->calculateCurrentSla(
            $slaDef->monitor_id,
            $slaDef->measurement_period,
            (float)$slaDef->target_uptime,
            $slaDef->warning_threshold !== null ? (float)$slaDef->warning_threshold : null,
            false
        );

        // Check if a report already exists for this period
        $existing = $slaReportsTable->find()
            ->where([
                'SlaReports.sla_definition_id' => $slaDefinitionId,
                'SlaReports.period_start' => $slaData['period_start'],
                'SlaReports.period_end' => $slaData['period_end'],
            ])
            ->first();

        if ($existing) {
            // Update existing report
            $report = $slaReportsTable->patchEntity($existing, [
                'actual_uptime' => $slaData['actual_uptime'],
                'total_minutes' => $slaData['total_minutes'],
                'downtime_minutes' => $slaData['downtime_minutes'],
                'allowed_downtime_minutes' => $slaData['allowed_downtime_minutes'],
                'remaining_downtime_minutes' => $slaData['remaining_downtime_minutes'],
                'status' => $slaData['status'],
                'incidents_count' => $slaData['incidents_count'],
            ]);
        } else {
            // Create new report
            $report = $slaReportsTable->newEntity([
                'organization_id' => $slaDef->organization_id,
                'sla_definition_id' => $slaDefinitionId,
                'monitor_id' => $slaDef->monitor_id,
                'period_start' => $slaData['period_start'],
                'period_end' => $slaData['period_end'],
                'period_type' => $slaDef->measurement_period,
                'target_uptime' => $slaData['target_uptime'],
                'actual_uptime' => $slaData['actual_uptime'],
                'total_minutes' => $slaData['total_minutes'],
                'downtime_minutes' => $slaData['downtime_minutes'],
                'allowed_downtime_minutes' => $slaData['allowed_downtime_minutes'],
                'remaining_downtime_minutes' => $slaData['remaining_downtime_minutes'],
                'status' => $slaData['status'],
                'incidents_count' => $slaData['incidents_count'],
            ]);
        }

        $saved = $slaReportsTable->save($report);
        if (!$saved) {
            $this->log(
                "Failed to save SLA report for definition {$slaDefinitionId}: " .
                json_encode($report->getErrors()),
                'error'
            );

            return null;
        }

        $this->log(
            "SLA report generated for definition {$slaDefinitionId}: " .
            "uptime={$slaData['actual_uptime']}%, status={$slaData['status']}",
            'info'
        );

        return $saved;
    }

    /**
     * Check all active SLAs for breach/warning status.
     *
     * Generates reports for all active SLA definitions and returns
     * a summary of the results.
     *
     * @return array Summary with counts by status
     */
    public function checkAllSlas(): array
    {
        $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');

        $activeDefinitions = $slaDefinitionsTable->find()
            ->where(['SlaDefinitions.active' => true])
            ->contain(['Monitors'])
            ->all();

        $results = [
            'total' => 0,
            'compliant' => 0,
            'at_risk' => 0,
            'breached' => 0,
            'errors' => 0,
            'details' => [],
        ];

        foreach ($activeDefinitions as $slaDef) {
            $results['total']++;

            $report = $this->generateReport($slaDef->id);
            if ($report === null) {
                $results['errors']++;
                continue;
            }

            $status = $report->status;
            if (isset($results[$status])) {
                $results[$status]++;
            }

            $results['details'][] = [
                'sla_id' => $slaDef->id,
                'monitor_name' => $slaDef->monitor->name ?? 'Unknown',
                'target' => (float)$slaDef->target_uptime,
                'actual' => (float)$report->actual_uptime,
                'status' => $status,
                'remaining_minutes' => (float)$report->remaining_downtime_minutes,
            ];
        }

        return $results;
    }

    /**
     * Get SLA summary data for the dashboard.
     *
     * @param int $orgId Organization ID
     * @return array Dashboard summary data
     */
    public function getDashboardSummary(int $orgId): array
    {
        $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');

        $query = $slaDefinitionsTable->find()
            ->where([
                'SlaDefinitions.active' => true,
            ])
            ->contain(['Monitors']);

        // Only filter by org if org ID is provided (non-zero)
        if ($orgId > 0) {
            $query->where(['SlaDefinitions.organization_id' => $orgId]);
        }

        $definitions = $query->all();

        $summary = [
            'total' => 0,
            'compliant' => 0,
            'at_risk' => 0,
            'breached' => 0,
            'most_at_risk' => null,
        ];

        $lowestRemaining = PHP_FLOAT_MAX;

        foreach ($definitions as $slaDef) {
            $summary['total']++;

            $slaData = $this->calculateCurrentSla(
                $slaDef->monitor_id,
                $slaDef->measurement_period,
                (float)$slaDef->target_uptime,
                $slaDef->warning_threshold !== null ? (float)$slaDef->warning_threshold : null,
                false
            );

            $status = $slaData['status'];
            if (isset($summary[$status])) {
                $summary[$status]++;
            }

            // Track the most at-risk SLA (lowest remaining downtime budget)
            if ($slaData['remaining_downtime_minutes'] < $lowestRemaining) {
                $lowestRemaining = $slaData['remaining_downtime_minutes'];
                $summary['most_at_risk'] = [
                    'sla_name' => $slaDef->name,
                    'monitor_name' => $slaDef->monitor->name ?? 'Unknown',
                    'remaining_minutes' => $slaData['remaining_downtime_minutes'],
                    'status' => $status,
                    'sla_id' => $slaDef->id,
                ];
            }
        }

        return $summary;
    }

    /**
     * Get historical reports for an SLA definition (last 12 periods).
     *
     * @param int $slaDefinitionId SLA definition ID
     * @param int $limit Number of historical periods to return
     * @return array List of SLA report data
     */
    public function getHistory(int $slaDefinitionId, int $limit = 12): array
    {
        $slaReportsTable = $this->fetchTable('SlaReports');

        return $slaReportsTable->find()
            ->where(['SlaReports.sla_definition_id' => $slaDefinitionId])
            ->orderBy(['SlaReports.period_start' => 'DESC'])
            ->limit($limit)
            ->all()
            ->toArray();
    }

    /**
     * Determine the SLA status based on actual vs target uptime.
     *
     * @param float $actualUptime Actual uptime percentage
     * @param float $targetUptime Target uptime percentage
     * @param float|null $warningThreshold Warning threshold (if null, uses target + half the margin)
     * @return string Status: compliant, at_risk, or breached
     */
    public function determineStatus(float $actualUptime, float $targetUptime, ?float $warningThreshold = null): string
    {
        if ($actualUptime < $targetUptime) {
            return SlaReport::STATUS_BREACHED;
        }

        // Default warning threshold: halfway between target and 100%
        if ($warningThreshold === null) {
            $warningThreshold = $targetUptime + ((100 - $targetUptime) / 2);
        }

        if ($actualUptime < $warningThreshold) {
            return SlaReport::STATUS_AT_RISK;
        }

        return SlaReport::STATUS_COMPLIANT;
    }

    /**
     * Get the start and end dates for the current measurement period.
     *
     * @param string $period Period type (monthly, quarterly, yearly)
     * @return array{start: \Cake\I18n\Date, end: \Cake\I18n\Date}
     */
    public function getPeriodDates(string $period): array
    {
        $now = new Date();

        return match ($period) {
            'quarterly' => [
                'start' => $this->getQuarterStart($now),
                'end' => $this->getQuarterEnd($now),
            ],
            'yearly' => [
                'start' => new Date($now->format('Y') . '-01-01'),
                'end' => new Date($now->format('Y') . '-12-31'),
            ],
            default => [ // monthly
                'start' => new Date($now->format('Y-m') . '-01'),
                'end' => new Date($now->format('Y-m-t')),
            ],
        };
    }

    /**
     * Get the start of the current quarter.
     *
     * @param \Cake\I18n\Date $date Reference date
     * @return \Cake\I18n\Date
     */
    private function getQuarterStart(Date $date): Date
    {
        $month = (int)$date->format('m');
        $quarterStartMonth = (int)(floor(($month - 1) / 3) * 3 + 1);

        return new Date($date->format('Y') . '-' . str_pad((string)$quarterStartMonth, 2, '0', STR_PAD_LEFT) . '-01');
    }

    /**
     * Get the end of the current quarter.
     *
     * @param \Cake\I18n\Date $date Reference date
     * @return \Cake\I18n\Date
     */
    private function getQuarterEnd(Date $date): Date
    {
        $month = (int)$date->format('m');
        $quarterEndMonth = (int)(floor(($month - 1) / 3) * 3 + 3);

        $endDate = new Date($date->format('Y') . '-' . str_pad((string)$quarterEndMonth, 2, '0', STR_PAD_LEFT) . '-01');

        return new Date($endDate->format('Y-m-t'));
    }
}
