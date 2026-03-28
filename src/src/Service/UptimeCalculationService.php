<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * UptimeCalculationService
 *
 * Centralizes all uptime/response time calculations and knows when to query
 * raw data (last 24h) vs rollup data (historical periods).
 *
 * Strategy:
 * - <= 1 day: query raw monitor_checks table
 * - > 1 day: hybrid — last 24h from raw, rest from rollup
 *
 * Rollup period selection:
 * - 2–7 days: use '5min' rollups (highest resolution available)
 * - 8–30 days: use '1hour' rollups
 * - 31+ days: use '1day' rollups
 */
class UptimeCalculationService
{
    use LocatorAwareTrait;

    /**
     * Get uptime percentage for a monitor over a time range.
     * Uses raw data for last 24h, rollups for older periods.
     *
     * @param int $monitorId Monitor ID
     * @param int $days Number of days to calculate uptime over
     * @return float Uptime percentage (0-100)
     */
    public function getUptime(int $monitorId, int $days = 30): float
    {
        if ($days <= 1) {
            return $this->getUptimeFromRaw($monitorId, $days);
        }

        // Hybrid: last 24h from raw, rest from rollup
        $rawResult = $this->getRawCounts($monitorId, 1);
        $rollupResult = $this->getRollupCounts($monitorId, $days - 1, $this->pickPeriodType($days));

        $totalChecks = $rawResult['total'] + $rollupResult['total'];
        $totalSuccess = $rawResult['success'] + $rollupResult['success'];

        if ($totalChecks === 0) {
            return 100.0;
        }

        return round(($totalSuccess / $totalChecks) * 100, 2);
    }

    /**
     * Get uptime from raw checks table.
     *
     * @param int $monitorId Monitor ID
     * @param int $days Number of days
     * @return float Uptime percentage
     */
    private function getUptimeFromRaw(int $monitorId, int $days): float
    {
        $result = $this->getRawCounts($monitorId, $days);

        if ($result['total'] === 0) {
            return 100.0;
        }

        return round(($result['success'] / $result['total']) * 100, 2);
    }

    /**
     * Get total/success counts from raw checks table.
     *
     * @param int $monitorId Monitor ID
     * @param int $days Number of days
     * @return array{total: int, success: int}
     */
    private function getRawCounts(int $monitorId, int $days): array
    {
        $checksTable = $this->fetchTable('MonitorChecks');
        $since = DateTime::now()->subDays($days);

        $result = $checksTable->find()
            ->select([
                'total' => $checksTable->find()->func()->count('*'),
                'success' => $checksTable->find()->func()->sum(
                    "CASE WHEN status = 'success' THEN 1 ELSE 0 END"
                ),
            ])
            ->where([
                'monitor_id' => $monitorId,
                'checked_at >=' => $since,
            ])
            ->disableAutoFields()
            ->first();

        return [
            'total' => (int)($result->total ?? 0),
            'success' => (int)($result->success ?? 0),
        ];
    }

    /**
     * Get total/success counts from rollup table.
     *
     * @param int $monitorId Monitor ID
     * @param int $days Number of days (offset from 1 day ago)
     * @param string $periodType Rollup period type (5min, 1hour, 1day)
     * @return array{total: int, success: int}
     */
    private function getRollupCounts(int $monitorId, int $days, string $periodType): array
    {
        $rollupTable = $this->fetchTable('MonitorChecksRollup');
        $since = DateTime::now()->subDays($days + 1);
        $until = DateTime::now()->subDays(1);

        $result = $rollupTable->find()
            ->select([
                'total' => $rollupTable->find()->func()->sum('check_count'),
                'success' => $rollupTable->find()->func()->sum('success_count'),
            ])
            ->where([
                'monitor_id' => $monitorId,
                'period_type' => $periodType,
                'period_start >=' => $since,
                'period_start <' => $until,
            ])
            ->disableAutoFields()
            ->first();

        return [
            'total' => (int)($result->total ?? 0),
            'success' => (int)($result->success ?? 0),
        ];
    }

    /**
     * Pick the appropriate rollup period type based on the number of days.
     *
     * @param int $days Number of days
     * @return string Period type
     */
    private function pickPeriodType(int $days): string
    {
        if ($days <= 7) {
            return '5min';
        }
        if ($days <= 30) {
            return '1hour';
        }

        return '1day';
    }

    /**
     * Get average response time for a monitor.
     *
     * @param int $monitorId Monitor ID
     * @param int $days Number of days (default 1 = last 24h)
     * @return float|null Average response time in ms, or null if no data
     */
    public function getAvgResponseTime(int $monitorId, int $days = 1): ?float
    {
        if ($days <= 1) {
            return $this->getAvgResponseTimeFromRaw($monitorId, $days);
        }

        // For longer periods, use rollup weighted average
        $periodType = $this->pickPeriodType($days);
        $rollupTable = $this->fetchTable('MonitorChecksRollup');
        $since = DateTime::now()->subDays($days);

        $result = $rollupTable->find()
            ->select([
                'weighted_sum' => $rollupTable->find()->func()->sum('avg_response_time * check_count'),
                'total_checks' => $rollupTable->find()->func()->sum('check_count'),
            ])
            ->where([
                'monitor_id' => $monitorId,
                'period_type' => $periodType,
                'period_start >=' => $since,
                'avg_response_time IS NOT' => null,
            ])
            ->disableAutoFields()
            ->first();

        $totalChecks = (int)($result->total_checks ?? 0);
        if ($totalChecks === 0) {
            return null;
        }

        return round((float)$result->weighted_sum / $totalChecks, 2);
    }

    /**
     * Get average response time from raw checks.
     *
     * @param int $monitorId Monitor ID
     * @param int $days Number of days
     * @return float|null Average response time or null
     */
    private function getAvgResponseTimeFromRaw(int $monitorId, int $days): ?float
    {
        $checksTable = $this->fetchTable('MonitorChecks');
        $since = DateTime::now()->subDays($days);

        $result = $checksTable->find()
            ->select([
                'avg_time' => $checksTable->find()->func()->avg('response_time'),
            ])
            ->where([
                'monitor_id' => $monitorId,
                'checked_at >=' => $since,
                'response_time IS NOT' => null,
            ])
            ->disableAutoFields()
            ->first();

        $avg = $result->avg_time ?? null;
        if ($avg === null) {
            return null;
        }

        return round((float)$avg, 2);
    }

    /**
     * Get uptime data for multiple monitors at once (for dashboard).
     * Uses a single aggregate query instead of N+1.
     *
     * @param array<int> $monitorIds Monitor IDs
     * @param int $days Number of days
     * @return array<int, float> Map of monitor_id => uptime percentage
     */
    public function getBulkUptime(array $monitorIds, int $days = 1): array
    {
        if (empty($monitorIds)) {
            return [];
        }

        if ($days <= 1) {
            return $this->getBulkUptimeFromRaw($monitorIds, $days);
        }

        // Hybrid: raw for last 24h + rollup for historical
        $rawResults = $this->getBulkUptimeFromRaw($monitorIds, 1);
        $rollupResults = $this->getBulkRollupCounts($monitorIds, $days - 1, $this->pickPeriodType($days));

        $result = [];
        foreach ($monitorIds as $monitorId) {
            $rawTotal = 0;
            $rawSuccess = 0;
            $rollupTotal = $rollupResults[$monitorId]['total'] ?? 0;
            $rollupSuccess = $rollupResults[$monitorId]['success'] ?? 0;

            // We need raw counts, not percentages — re-fetch
            $rawCounts = $this->getRawCounts($monitorId, 1);
            $rawTotal = $rawCounts['total'];
            $rawSuccess = $rawCounts['success'];

            $totalChecks = $rawTotal + $rollupTotal;
            $totalSuccess = $rawSuccess + $rollupSuccess;

            $result[$monitorId] = $totalChecks > 0
                ? round(($totalSuccess / $totalChecks) * 100, 2)
                : 100.0;
        }

        return $result;
    }

    /**
     * Get bulk uptime from raw checks using a single GROUP BY query.
     *
     * @param array<int> $monitorIds Monitor IDs
     * @param int $days Number of days
     * @return array<int, float> Map of monitor_id => uptime percentage
     */
    private function getBulkUptimeFromRaw(array $monitorIds, int $days): array
    {
        $checksTable = $this->fetchTable('MonitorChecks');
        $since = DateTime::now()->subDays($days);

        $rows = $checksTable->find()
            ->select([
                'monitor_id',
                'total' => $checksTable->find()->func()->count('*'),
                'success' => $checksTable->find()->func()->sum(
                    "CASE WHEN status = 'success' THEN 1 ELSE 0 END"
                ),
            ])
            ->where([
                'monitor_id IN' => $monitorIds,
                'checked_at >=' => $since,
            ])
            ->groupBy(['monitor_id'])
            ->disableAutoFields()
            ->all();

        $result = [];
        foreach ($monitorIds as $id) {
            $result[$id] = 100.0; // default if no data
        }
        foreach ($rows as $row) {
            $total = (int)$row->total;
            $success = (int)$row->success;
            $result[$row->monitor_id] = $total > 0
                ? round(($success / $total) * 100, 2)
                : 100.0;
        }

        return $result;
    }

    /**
     * Get bulk rollup counts using a single GROUP BY query.
     *
     * @param array<int> $monitorIds Monitor IDs
     * @param int $days Number of days
     * @param string $periodType Rollup period type
     * @return array<int, array{total: int, success: int}> Map of monitor_id => counts
     */
    private function getBulkRollupCounts(array $monitorIds, int $days, string $periodType): array
    {
        $rollupTable = $this->fetchTable('MonitorChecksRollup');
        $since = DateTime::now()->subDays($days + 1);
        $until = DateTime::now()->subDays(1);

        $rows = $rollupTable->find()
            ->select([
                'monitor_id',
                'total' => $rollupTable->find()->func()->sum('check_count'),
                'success' => $rollupTable->find()->func()->sum('success_count'),
            ])
            ->where([
                'monitor_id IN' => $monitorIds,
                'period_type' => $periodType,
                'period_start >=' => $since,
                'period_start <' => $until,
            ])
            ->groupBy(['monitor_id'])
            ->disableAutoFields()
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->monitor_id] = [
                'total' => (int)$row->total,
                'success' => (int)$row->success,
            ];
        }

        return $result;
    }

    /**
     * Get bulk response times for multiple monitors using a single query.
     *
     * @param array<int> $monitorIds Monitor IDs
     * @param int $days Number of days
     * @return array<int, float|null> Map of monitor_id => avg response time
     */
    public function getBulkResponseTimes(array $monitorIds, int $days = 1): array
    {
        if (empty($monitorIds)) {
            return [];
        }

        $checksTable = $this->fetchTable('MonitorChecks');
        $since = DateTime::now()->subDays($days);

        $rows = $checksTable->find()
            ->select([
                'monitor_id',
                'avg_response' => $checksTable->find()->func()->avg('response_time'),
            ])
            ->where([
                'monitor_id IN' => $monitorIds,
                'checked_at >=' => $since,
                'response_time IS NOT' => null,
            ])
            ->groupBy(['monitor_id'])
            ->disableAutoFields()
            ->all();

        $result = [];
        foreach ($monitorIds as $id) {
            $result[$id] = null;
        }
        foreach ($rows as $row) {
            $result[$row->monitor_id] = $row->avg_response !== null
                ? round((float)$row->avg_response, 2)
                : null;
        }

        return $result;
    }

    /**
     * Get check counts for a time period (for SuperAdmin metrics).
     * Uses rollup data for anything older than 24h.
     *
     * @param string $period Period identifier: 'today', 'week', 'month'
     * @return int Total check count
     */
    public function getCheckCounts(string $period = 'today'): int
    {
        switch ($period) {
            case 'today':
                return $this->getCheckCountsFromRaw(DateTime::now()->startOfDay());

            case 'week':
                // Today from raw + rest from rollup
                $rawCount = $this->getCheckCountsFromRaw(DateTime::now()->startOfDay());
                $rollupCount = $this->getCheckCountsFromRollup(
                    DateTime::now()->subDays(7),
                    DateTime::now()->startOfDay(),
                    '1hour'
                );

                return $rawCount + $rollupCount;

            case 'month':
                // Today from raw + rest from rollup
                $rawCount = $this->getCheckCountsFromRaw(DateTime::now()->startOfDay());
                $rollupCount = $this->getCheckCountsFromRollup(
                    DateTime::now()->subDays(30),
                    DateTime::now()->startOfDay(),
                    '1day'
                );

                return $rawCount + $rollupCount;

            default:
                return 0;
        }
    }

    /**
     * Get check counts from raw table.
     *
     * @param \Cake\I18n\DateTime $since Start time
     * @return int Check count
     */
    private function getCheckCountsFromRaw(DateTime $since): int
    {
        $checksTable = $this->fetchTable('MonitorChecks');

        return $checksTable->find()
            ->applyOptions(['skipTenantScope' => true])
            ->where(['checked_at >=' => $since])
            ->count();
    }

    /**
     * Get check counts from rollup table.
     *
     * @param \Cake\I18n\DateTime $since Start time
     * @param \Cake\I18n\DateTime $until End time
     * @param string $periodType Rollup period type
     * @return int Check count
     */
    private function getCheckCountsFromRollup(DateTime $since, DateTime $until, string $periodType): int
    {
        $rollupTable = $this->fetchTable('MonitorChecksRollup');

        $result = $rollupTable->find()
            ->select([
                'total' => $rollupTable->find()->func()->sum('check_count'),
            ])
            ->applyOptions(['skipTenantScope' => true])
            ->where([
                'period_type' => $periodType,
                'period_start >=' => $since,
                'period_start <' => $until,
            ])
            ->disableAutoFields()
            ->first();

        return (int)($result->total ?? 0);
    }
}
