<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * SlaController (TASK-NG-008)
 *
 * CRUD for SLA definitions plus report and CSV export.
 */
class SlaController extends AppController
{
    protected \App\Service\SlaService $slaService;

    public function initialize(): void
    {
        parent::initialize();
        $this->slaService = new \App\Service\SlaService();
    }

    /**
     * GET /api/v2/sla
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('SlaDefinitions');
        $slas = $table->find()
            ->contain(['Monitors' => ['fields' => ['id', 'name']]])
            ->where(['SlaDefinitions.organization_id' => $this->currentOrgId])
            ->orderBy(['SlaDefinitions.name' => 'ASC'])
            ->all()
            ->toArray();

        // Enrich each SLA with current compliance data
        $checksTable = $this->fetchTable('MonitorChecks');

        foreach ($slas as &$sla) {
            try {
                $sla['monitor_name'] = $sla['monitor']['name'] ?? '';

                // Get period dates for this SLA
                $periodDates = $this->slaService->getPeriodDates($sla['measurement_period'] ?? 'monthly');
                $now = new \DateTime();
                $startStr = $periodDates['start']->format('Y-m-d 00:00:00');
                $effectiveEnd = ($periodDates['end'] > $now) ? $now : $periodDates['end'];
                $endStr = $effectiveEnd->format('Y-m-d H:i:s');
                $totalMinutes = max(1, (int)round(($effectiveEnd->getTimestamp() - $periodDates['start']->getTimestamp()) / 60));

                // Quick check count for uptime
                $stats = $checksTable->find()
                    ->where([
                        'monitor_id' => $sla['monitor_id'],
                        'created >=' => $startStr,
                        'created <=' => $endStr,
                    ])
                    ->select([
                        'total' => $checksTable->find()->func()->count('*'),
                        'success_count' => $checksTable->find()->func()->sum(
                            'CASE WHEN status = \'success\' THEN 1 ELSE 0 END'
                        ),
                    ])
                    ->first();

                $total = (int)($stats->total ?? 0);
                $success = (int)($stats->success_count ?? 0);
                $actualUptime = $total > 0 ? round(($success / $total) * 100, 3) : null;
                $targetUptime = (float)$sla['target_uptime'];

                $sla['actual_uptime'] = $actualUptime;
                $sla['total_checks'] = $total;

                if ($actualUptime !== null) {
                    $sla['status'] = $this->slaService->determineStatus($actualUptime, $targetUptime, isset($sla['warning_threshold']) ? (float)$sla['warning_threshold'] : null);
                    $downtimeMin = round($totalMinutes * (100 - $actualUptime) / 100, 2);
                    $allowedMin = round($totalMinutes * (100 - $targetUptime) / 100, 2);
                    $sla['budget_used_pct'] = $allowedMin > 0 ? min(100, round(($downtimeMin / $allowedMin) * 100, 1)) : 0;
                } else {
                    $sla['status'] = 'unknown';
                    $sla['budget_used_pct'] = 0;
                }
            } catch (\Throwable $e) {
                $sla['actual_uptime'] = null;
                $sla['status'] = 'unknown';
                $sla['budget_used_pct'] = 0;
                $sla['monitor_name'] = '';
            }
        }
        unset($sla);

        $this->success(['slas' => $slas]);
    }

    /**
     * GET /api/v2/sla/{id}
     *
     * @param string $id SLA ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $sla = $this->resolveOrgEntity('SlaDefinitions', $id);

        if (!$sla) {
            $this->error('SLA not found', 404);

            return;
        }

        $this->success(['sla' => $sla]);
    }

    /**
     * POST /api/v2/sla
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('SlaDefinitions');
        $sla = $table->newEntity($this->request->getData());
        $sla->set('organization_id', $this->currentOrgId);

        if (!$table->save($sla)) {
            $this->error('Validation failed', 422, $sla->getErrors());

            return;
        }

        $this->success(['sla' => $sla], 201);
    }

    /**
     * PUT /api/v2/sla/{id}
     *
     * @param string $id SLA ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('SlaDefinitions');
        $sla = $this->resolveOrgEntity('SlaDefinitions', $id);

        if (!$sla) {
            $this->error('SLA not found', 404);

            return;
        }

        $sla = $table->patchEntity($sla, $this->request->getData());
        if (!$table->save($sla)) {
            $this->error('Validation failed', 422, $sla->getErrors());

            return;
        }

        $this->success(['sla' => $sla]);
    }

    /**
     * DELETE /api/v2/sla/{id}
     *
     * @param string $id SLA ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('SlaDefinitions');
        $sla = $this->resolveOrgEntity('SlaDefinitions', $id);

        if (!$sla) {
            $this->error('SLA not found', 404);

            return;
        }

        if (!$table->delete($sla)) {
            $this->error('Failed to delete SLA', 500);

            return;
        }

        $this->success(['message' => 'SLA deleted']);
    }

    /**
     * GET /api/v2/sla/{id}/report
     *
     * Return SLA compliance report data for the given SLA.
     *
     * @param string $id SLA ID.
     * @return void
     */
    public function report(string $id): void
    {
        $this->request->allowMethod(['get']);

        $sla = $this->resolveOrgEntity('SlaDefinitions', $id, [
            'contain' => ['Monitors'],
        ]);

        if (!$sla) {
            $this->error('SLA not found', 404);

            return;
        }

        try {
            // Use from/to query params if provided, otherwise use the SLA's measurement period
            $from = $this->request->getQuery('from');
            $to = $this->request->getQuery('to');

            if ($from) {
                $startDate = new \DateTime($from);
                $endDate = $to ? new \DateTime($to) : new \DateTime();
            } else {
                $periodDates = $this->slaService->getPeriodDates($sla->measurement_period);
                $startDate = $periodDates['start'];
                $endDate = $periodDates['end'];
            }

            $now = new \DateTime();
            $effectiveEnd = ($endDate > $now) ? $now : $endDate;
            $startStr = $startDate->format('Y-m-d 00:00:00');
            $endStr = $effectiveEnd->format('Y-m-d H:i:s');

            $totalMinutes = max(1, (int)round(($effectiveEnd->getTimestamp() - $startDate->getTimestamp()) / 60));

            // Get check stats for the period
            $checksTable = $this->fetchTable('MonitorChecks');
            $checkStats = $checksTable->find()
                ->where([
                    'monitor_id' => $sla->monitor_id,
                    'created >=' => $startStr,
                    'created <=' => $endStr,
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
                    'min_response' => $checksTable->find()->func()->min('response_time'),
                ])
                ->first();

            $totalChecks = (int)($checkStats->total ?? 0);
            $successChecks = (int)($checkStats->success_count ?? 0);
            $failedChecks = (int)($checkStats->failure_count ?? 0);
            $avgResponse = $checkStats->avg_response ? round((float)$checkStats->avg_response, 1) : null;
            $maxResponse = $checkStats->max_response ? (int)$checkStats->max_response : null;

            // Calculate P95 response time
            $p95Response = null;
            if ($totalChecks > 0) {
                $p95Row = $checksTable->find()
                    ->where([
                        'monitor_id' => $sla->monitor_id,
                        'created >=' => $startStr,
                        'created <=' => $endStr,
                        'response_time IS NOT' => null,
                    ])
                    ->orderBy(['response_time' => 'ASC'])
                    ->offset((int)floor($totalChecks * 0.95))
                    ->limit(1)
                    ->first();
                $p95Response = $p95Row ? (int)$p95Row->response_time : null;
            }

            // Calculate actual uptime from checks
            $actualUptime = $totalChecks > 0 ? round(($successChecks / $totalChecks) * 100, 3) : 0;
            $targetUptime = (float)$sla->target_uptime;

            $downtimeMinutes = round($totalMinutes * (100 - $actualUptime) / 100, 2);
            $allowedDowntimeMinutes = round($totalMinutes * (100 - $targetUptime) / 100, 2);
            $remainingDowntimeMinutes = max(0, round($allowedDowntimeMinutes - $downtimeMinutes, 2));

            // Count incidents
            $incidentsTable = $this->fetchTable('Incidents');
            $incidents = $incidentsTable->find()
                ->where([
                    'Incidents.monitor_id' => $sla->monitor_id,
                    'Incidents.started_at >=' => $startStr,
                    'Incidents.started_at <=' => $endStr,
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
                    'duration_minutes' => round(max(0, ($incEnd->getTimestamp() - $incident->started_at->getTimestamp()) / 60), 1),
                ];
            }

            // MTBF / MTTR
            $mtbfMinutes = $incidentsCount > 0 ? ($totalMinutes - $totalIncidentMinutes) / $incidentsCount : $totalMinutes;
            $mttrMinutes = $incidentsCount > 0 ? $totalIncidentMinutes / $incidentsCount : 0;

            // Daily breakdown
            $dailyBreakdown = [];
            $cursor = clone $startDate;
            while ($cursor <= $effectiveEnd) {
                $dayStr = $cursor->format('Y-m-d');
                $dayStart = $dayStr . ' 00:00:00';
                $dayEnd = $dayStr . ' 23:59:59';

                $dayStats = $checksTable->find()
                    ->where([
                        'monitor_id' => $sla->monitor_id,
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

                // Count incidents for the day
                $dayIncidents = $incidentsTable->find()
                    ->where([
                        'Incidents.monitor_id' => $sla->monitor_id,
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

            $status = $this->slaService->determineStatus($actualUptime, $targetUptime, $sla->warning_threshold ? (float)$sla->warning_threshold : null);

            $report = [
                'current_uptime' => $actualUptime,
                'actual_uptime' => $actualUptime,
                'target_uptime' => $targetUptime,
                'total_minutes' => $totalMinutes,
                'downtime_minutes' => $downtimeMinutes,
                'total_downtime_minutes' => $downtimeMinutes,
                'allowed_downtime_minutes' => $allowedDowntimeMinutes,
                'downtime_budget_minutes' => $allowedDowntimeMinutes,
                'remaining_downtime_minutes' => $remainingDowntimeMinutes,
                'status' => $status,
                'incidents_count' => $incidentsCount,
                'incident_count' => $incidentsCount,
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
                'monitor_name' => $sla->monitor->name ?? '',
                'period_start' => $startDate->format('Y-m-d'),
                'period_end' => $endDate->format('Y-m-d'),
                'daily_breakdown' => $dailyBreakdown,
                'incidents' => $incidentsList,
            ];

            $this->success(['report' => $report]);
        } catch (\Throwable $e) {
            $this->log('SLA report failed: ' . $e->getMessage(), 'error');
            $this->error('Failed to generate report. Please try again.', 500);
        }
    }

    /**
     * GET /api/v2/sla/{id}/export
     *
     * Export SLA report as PDF or CSV.
     * Query param: ?format=pdf (default) or ?format=csv
     *
     * @param string $id SLA ID.
     * @return void
     */
    public function export(string $id): void
    {
        $this->request->allowMethod(['get']);

        $sla = $this->resolveOrgEntity('SlaDefinitions', $id, [
            'contain' => ['Monitors'],
        ]);

        if (!$sla) {
            $this->error('SLA not found', 404);

            return;
        }

        $format = $this->request->getQuery('format', 'pdf');

        try {
            $reportData = $this->slaService->calculateCurrentSla(
                $sla->monitor_id,
                $sla->measurement_period,
                (float)$sla->target_uptime,
                $sla->warning_threshold ? (float)$sla->warning_threshold : null,
                false
            );

            $slaArray = $sla->toArray();

            if ($format === 'pdf') {
                $pdfService = new \App\Service\PdfReportService();
                $pdfContent = $pdfService->generateSlaPdf($slaArray, $reportData);

                $this->autoRender = false;
                $this->response = $this->response
                    ->withType('application/pdf')
                    ->withHeader('Content-Disposition', 'attachment; filename="sla-report-' . $this->slugName($sla->name) . '.pdf"')
                    ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                    ->withStringBody($pdfContent);
            } else {
                $csv = $this->slaService->exportReportCsv($sla);

                $this->autoRender = false;
                $this->response = $this->response
                    ->withType('text/csv; charset=utf-8')
                    ->withHeader('Content-Disposition', 'attachment; filename="sla-report-' . $id . '.csv"')
                    ->withStringBody($csv);
            }
        } catch (\Throwable $e) {
            $this->log('SLA export failed: ' . $e->getMessage(), 'error');
            $this->error('Failed to export report. Please try again.', 500);
        }
    }

    /**
     * Helper to generate a filename-safe slug.
     */
    private function slugName(string $name): string
    {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($name)));
    }
}
