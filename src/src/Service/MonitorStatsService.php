<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Monitor Stats Service
 *
 * Computes uptime, response-time, 30-day history, SLA, and per-region
 * breakdowns for a single monitor.  Extracted from MonitorsController::view().
 */
class MonitorStatsService
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * Get full monitor detail with stats.
     *
     * Returns null when the monitor does not exist.
     *
     * @param int $monitorId  Monitor ID.
     * @param int $organizationId  Organization ID (used for scoped raw queries).
     * @return array|null
     */
    public function getMonitorDetail(int $monitorId, int $organizationId): ?array
    {
        $monitorsTable = $this->fetchTable('Monitors');

        try {
            $monitor = $monitorsTable->get($monitorId, contain: [
                'MonitorChecks' => function ($q) {
                    return $q->orderBy(['created' => 'DESC'])->limit(50);
                },
                'Incidents' => function ($q) {
                    return $q->orderBy(['created' => 'DESC'])->limit(10);
                },
                'NotificationPolicies' => [
                    'NotificationPolicySteps' => [
                        'sort' => ['NotificationPolicySteps.step_order' => 'ASC'],
                        'NotificationChannels',
                    ],
                ],
            ]);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            return null;
        }

        $checksTable = $this->fetchTable('MonitorChecks');
        $since24h = DateTime::now()->subHours(24)->format('Y-m-d H:i:s');

        // 24-hour uptime
        $uptimeResult = $checksTable->find()
            ->select([
                'total' => $checksTable->find()->func()->count('*'),
                'success' => $checksTable->find()->func()->sum(
                    "CASE WHEN status = 'success' THEN 1 ELSE 0 END"
                ),
            ])
            ->where([
                'monitor_id' => $monitorId,
                'checked_at >=' => $since24h,
            ])
            ->disableAutoFields()
            ->first();

        $totalChecks = (int)($uptimeResult->total ?? 0);
        $successfulChecks = (int)($uptimeResult->success ?? 0);
        $uptime = $totalChecks > 0 ? round(($successfulChecks / $totalChecks) * 100, 2) : 0;

        // Average response time (24h)
        $avgQuery = $checksTable->find();
        $avgResult = $avgQuery
            ->select(['avg' => $avgQuery->func()->avg('response_time')])
            ->where([
                'monitor_id' => $monitorId,
                'checked_at >=' => $since24h,
                'response_time IS NOT' => null,
            ])
            ->disableAutoFields()
            ->first();
        $avgResponseTime = $avgResult && $avgResult->avg ? round((float)$avgResult->avg, 2) : null;

        // 30-day uptime history
        $uptimeHistory = $this->buildUptimeHistory($checksTable, $monitorId, $organizationId);

        // SLA data
        $slaData = $this->buildSlaData($monitorId);

        // Per-region breakdown
        $regionBreakdown = $this->buildRegionBreakdown($checksTable, $monitorId, $since24h, $organizationId);

        return [
            'monitor' => $monitor,
            'uptime_24h' => $uptime,
            'avg_response_time' => $avgResponseTime,
            'total_checks_24h' => $totalChecks,
            'uptime_history' => $uptimeHistory,
            'sla' => $slaData,
            'region_breakdown' => $regionBreakdown,
        ];
    }

    /**
     * Build 30-day uptime history from raw SQL.
     *
     * @param \Cake\ORM\Table $checksTable MonitorChecks table.
     * @param int $monitorId Monitor ID.
     * @param int $organizationId Organization ID.
     * @return array
     */
    private function buildUptimeHistory($checksTable, int $monitorId, int $organizationId): array
    {
        $conn = $checksTable->getConnection();
        $stmt = $conn->execute(
            "SELECT DATE(checked_at) as check_date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count
             FROM monitor_checks mc
             WHERE mc.monitor_id = ? AND mc.checked_at >= ? AND mc.organization_id = ?
             GROUP BY DATE(checked_at)
             ORDER BY check_date ASC",
            [$monitorId, DateTime::now()->subDays(29)->startOfDay()->format('Y-m-d H:i:s'), $organizationId]
        );

        $dailyStats = [];
        foreach ($stmt->fetchAll('assoc') as $row) {
            $dailyStats[$row['check_date']] = $row;
        }

        $uptimeHistory = [];
        for ($i = 29; $i >= 0; $i--) {
            $dayStr = DateTime::now()->subDays($i)->format('Y-m-d');
            $total = (int)($dailyStats[$dayStr]['total'] ?? 0);
            $success = (int)($dailyStats[$dayStr]['success_count'] ?? 0);
            $uptimeHistory[] = [
                'date' => $dayStr,
                'uptime' => $total > 0 ? round(($success / $total) * 100, 2) : 0,
                'checks' => $total,
            ];
        }

        return $uptimeHistory;
    }

    /**
     * Build SLA data for a monitor.
     *
     * @param int $monitorId Monitor ID.
     * @return array|null
     */
    private function buildSlaData(int $monitorId): ?array
    {
        $slaData = null;

        try {
            $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');
            $slaDef = $slaDefinitionsTable->find()
                ->where(['SlaDefinitions.monitor_id' => $monitorId, 'SlaDefinitions.active' => true])
                ->first();

            if ($slaDef) {
                $slaService = new SlaService();
                $slaData = $slaService->calculateCurrentSla(
                    $monitorId,
                    $slaDef->measurement_period,
                    (float)$slaDef->target_uptime,
                    $slaDef->warning_threshold !== null ? (float)$slaDef->warning_threshold : null,
                    false
                );
                $slaData['sla_id'] = $slaDef->id;
                $slaData['sla_name'] = $slaDef->name;
            }
        } catch (\Exception $e) {
            // SLA tables may not exist yet
        }

        return $slaData;
    }

    /**
     * Build per-region breakdown (C-01: Multi-region).
     *
     * @param \Cake\ORM\Table $checksTable MonitorChecks table.
     * @param int $monitorId Monitor ID.
     * @param string $since24h 24-hour cutoff timestamp.
     * @param int $organizationId Organization ID.
     * @return array
     */
    private function buildRegionBreakdown($checksTable, int $monitorId, string $since24h, int $organizationId): array
    {
        $regionBreakdown = [];

        try {
            $conn = $checksTable->getConnection();
            $regionStmt = $conn->execute(
                "SELECT cr.id, cr.name, cr.code,
                        COUNT(*) as total_checks,
                        SUM(CASE WHEN mc.status = 'success' THEN 1 ELSE 0 END) as success_checks,
                        ROUND(AVG(mc.response_time)::numeric, 2) as avg_response_time
                 FROM monitor_checks mc
                 JOIN check_regions cr ON cr.id = mc.region_id
                 WHERE mc.monitor_id = ? AND mc.checked_at >= ? AND mc.organization_id = ?
                 GROUP BY cr.id, cr.name, cr.code
                 ORDER BY cr.name",
                [$monitorId, $since24h, $organizationId]
            );

            foreach ($regionStmt->fetchAll('assoc') as $row) {
                $rTotal = (int)$row['total_checks'];
                $rSuccess = (int)$row['success_checks'];
                $regionBreakdown[] = [
                    'region_id' => (int)$row['id'],
                    'region_name' => $row['name'],
                    'region_code' => $row['code'],
                    'uptime' => $rTotal > 0 ? round(($rSuccess / $rTotal) * 100, 2) : 0,
                    'avg_response_time' => $row['avg_response_time'] !== null ? (float)$row['avg_response_time'] : null,
                    'total_checks' => $rTotal,
                ];
            }
        } catch (\Exception $e) {
            // check_regions table may not exist or no regional data
        }

        return $regionBreakdown;
    }
}
