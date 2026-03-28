<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * ChecksAggregationService
 *
 * Aggregates raw monitor_checks data into rollup windows:
 * - Raw checks older than 24h -> 5-minute rollups
 * - 5-minute rollups older than 7 days -> 1-hour rollups
 * - 1-hour rollups older than 30 days -> 1-day rollups
 */
class ChecksAggregationService
{
    use LocatorAwareTrait;

    /**
     * How far back to process raw checks into 5-minute windows.
     * We process checks older than 24 hours but no older than 48 hours
     * to avoid reprocessing ancient data on every run.
     */
    private const RAW_CUTOFF_HOURS = 24;
    private const RAW_MAX_AGE_HOURS = 48;

    /**
     * 5-minute rollups older than 7 days get aggregated into 1-hour windows.
     */
    private const FIVEMIN_CUTOFF_DAYS = 7;
    private const FIVEMIN_MAX_AGE_DAYS = 14;

    /**
     * 1-hour rollups older than 30 days get aggregated into 1-day windows.
     */
    private const HOURLY_CUTOFF_DAYS = 30;
    private const HOURLY_MAX_AGE_DAYS = 60;

    /**
     * Aggregate raw checks older than 24 hours into 5-minute windows.
     *
     * @return int Number of rollup rows created/updated
     */
    public function aggregate5Min(): int
    {
        $connection = $this->fetchTable('MonitorChecks')->getConnection();

        $cutoff = DateTime::now()->subHours(self::RAW_CUTOFF_HOURS);
        $oldestToProcess = DateTime::now()->subHours(self::RAW_MAX_AGE_HOURS);

        $sql = "
            INSERT INTO monitor_checks_rollup
                (organization_id, monitor_id, period_start, period_end, period_type,
                 check_count, success_count, failure_count, timeout_count, error_count,
                 avg_response_time, min_response_time, max_response_time, uptime_percentage, created)
            SELECT
                organization_id,
                monitor_id,
                date_trunc('hour', checked_at) +
                    (EXTRACT(MINUTE FROM checked_at)::integer / 5 * interval '5 minutes') as period_start,
                date_trunc('hour', checked_at) +
                    (EXTRACT(MINUTE FROM checked_at)::integer / 5 * interval '5 minutes') + interval '5 minutes' as period_end,
                '5min' as period_type,
                COUNT(*) as check_count,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count,
                SUM(CASE WHEN status = 'failure' THEN 1 ELSE 0 END) as failure_count,
                SUM(CASE WHEN status = 'timeout' THEN 1 ELSE 0 END) as timeout_count,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_count,
                AVG(response_time)::decimal(10,2) as avg_response_time,
                MIN(response_time) as min_response_time,
                MAX(response_time) as max_response_time,
                (SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END)::decimal / NULLIF(COUNT(*), 0) * 100)::decimal(5,2) as uptime_percentage,
                NOW() as created
            FROM monitor_checks
            WHERE checked_at < :cutoff
              AND checked_at >= :oldest_to_process
            GROUP BY organization_id, monitor_id, period_start, period_end
            ON CONFLICT (monitor_id, period_start, period_type)
            DO UPDATE SET
                check_count = EXCLUDED.check_count,
                success_count = EXCLUDED.success_count,
                failure_count = EXCLUDED.failure_count,
                timeout_count = EXCLUDED.timeout_count,
                error_count = EXCLUDED.error_count,
                avg_response_time = EXCLUDED.avg_response_time,
                min_response_time = EXCLUDED.min_response_time,
                max_response_time = EXCLUDED.max_response_time,
                uptime_percentage = EXCLUDED.uptime_percentage
        ";

        try {
            $statement = $connection->execute($sql, [
                'cutoff' => $cutoff->format('Y-m-d H:i:s'),
                'oldest_to_process' => $oldestToProcess->format('Y-m-d H:i:s'),
            ]);

            $count = $statement->rowCount();
            Log::info("ChecksAggregation: aggregate5Min processed {$count} rollup rows");

            return $count;
        } catch (\Exception $e) {
            Log::error("ChecksAggregation: aggregate5Min failed - {$e->getMessage()}");

            return 0;
        }
    }

    /**
     * Aggregate 5-minute rollups older than 7 days into 1-hour windows.
     *
     * @return int Number of rollup rows created/updated
     */
    public function aggregate1Hour(): int
    {
        $connection = $this->fetchTable('MonitorChecksRollup')->getConnection();

        $cutoff = DateTime::now()->subDays(self::FIVEMIN_CUTOFF_DAYS);
        $oldestToProcess = DateTime::now()->subDays(self::FIVEMIN_MAX_AGE_DAYS);

        $sql = "
            INSERT INTO monitor_checks_rollup
                (organization_id, monitor_id, period_start, period_end, period_type,
                 check_count, success_count, failure_count, timeout_count, error_count,
                 avg_response_time, min_response_time, max_response_time, uptime_percentage, created)
            SELECT
                organization_id,
                monitor_id,
                date_trunc('hour', period_start) as period_start,
                date_trunc('hour', period_start) + interval '1 hour' as period_end,
                '1hour' as period_type,
                SUM(check_count) as check_count,
                SUM(success_count) as success_count,
                SUM(failure_count) as failure_count,
                SUM(timeout_count) as timeout_count,
                SUM(error_count) as error_count,
                (SUM(avg_response_time * check_count) / NULLIF(SUM(check_count), 0))::decimal(10,2) as avg_response_time,
                MIN(min_response_time) as min_response_time,
                MAX(max_response_time) as max_response_time,
                (SUM(success_count)::decimal / NULLIF(SUM(check_count), 0) * 100)::decimal(5,2) as uptime_percentage,
                NOW() as created
            FROM monitor_checks_rollup
            WHERE period_type = '5min'
              AND period_start < :cutoff
              AND period_start >= :oldest_to_process
            GROUP BY organization_id, monitor_id, date_trunc('hour', period_start)
            ON CONFLICT (monitor_id, period_start, period_type)
            DO UPDATE SET
                check_count = EXCLUDED.check_count,
                success_count = EXCLUDED.success_count,
                failure_count = EXCLUDED.failure_count,
                timeout_count = EXCLUDED.timeout_count,
                error_count = EXCLUDED.error_count,
                avg_response_time = EXCLUDED.avg_response_time,
                min_response_time = EXCLUDED.min_response_time,
                max_response_time = EXCLUDED.max_response_time,
                uptime_percentage = EXCLUDED.uptime_percentage
        ";

        try {
            $statement = $connection->execute($sql, [
                'cutoff' => $cutoff->format('Y-m-d H:i:s'),
                'oldest_to_process' => $oldestToProcess->format('Y-m-d H:i:s'),
            ]);

            $count = $statement->rowCount();
            Log::info("ChecksAggregation: aggregate1Hour processed {$count} rollup rows");

            return $count;
        } catch (\Exception $e) {
            Log::error("ChecksAggregation: aggregate1Hour failed - {$e->getMessage()}");

            return 0;
        }
    }

    /**
     * Aggregate 1-hour rollups older than 30 days into 1-day windows.
     *
     * @return int Number of rollup rows created/updated
     */
    public function aggregate1Day(): int
    {
        $connection = $this->fetchTable('MonitorChecksRollup')->getConnection();

        $cutoff = DateTime::now()->subDays(self::HOURLY_CUTOFF_DAYS);
        $oldestToProcess = DateTime::now()->subDays(self::HOURLY_MAX_AGE_DAYS);

        $sql = "
            INSERT INTO monitor_checks_rollup
                (organization_id, monitor_id, period_start, period_end, period_type,
                 check_count, success_count, failure_count, timeout_count, error_count,
                 avg_response_time, min_response_time, max_response_time, uptime_percentage, created)
            SELECT
                organization_id,
                monitor_id,
                date_trunc('day', period_start) as period_start,
                date_trunc('day', period_start) + interval '1 day' as period_end,
                '1day' as period_type,
                SUM(check_count) as check_count,
                SUM(success_count) as success_count,
                SUM(failure_count) as failure_count,
                SUM(timeout_count) as timeout_count,
                SUM(error_count) as error_count,
                (SUM(avg_response_time * check_count) / NULLIF(SUM(check_count), 0))::decimal(10,2) as avg_response_time,
                MIN(min_response_time) as min_response_time,
                MAX(max_response_time) as max_response_time,
                (SUM(success_count)::decimal / NULLIF(SUM(check_count), 0) * 100)::decimal(5,2) as uptime_percentage,
                NOW() as created
            FROM monitor_checks_rollup
            WHERE period_type = '1hour'
              AND period_start < :cutoff
              AND period_start >= :oldest_to_process
            GROUP BY organization_id, monitor_id, date_trunc('day', period_start)
            ON CONFLICT (monitor_id, period_start, period_type)
            DO UPDATE SET
                check_count = EXCLUDED.check_count,
                success_count = EXCLUDED.success_count,
                failure_count = EXCLUDED.failure_count,
                timeout_count = EXCLUDED.timeout_count,
                error_count = EXCLUDED.error_count,
                avg_response_time = EXCLUDED.avg_response_time,
                min_response_time = EXCLUDED.min_response_time,
                max_response_time = EXCLUDED.max_response_time,
                uptime_percentage = EXCLUDED.uptime_percentage
        ";

        try {
            $statement = $connection->execute($sql, [
                'cutoff' => $cutoff->format('Y-m-d H:i:s'),
                'oldest_to_process' => $oldestToProcess->format('Y-m-d H:i:s'),
            ]);

            $count = $statement->rowCount();
            Log::info("ChecksAggregation: aggregate1Day processed {$count} rollup rows");

            return $count;
        } catch (\Exception $e) {
            Log::error("ChecksAggregation: aggregate1Day failed - {$e->getMessage()}");

            return 0;
        }
    }

    /**
     * Run all aggregation levels.
     *
     * @return array<string, int> Results keyed by period type
     */
    public function runAll(): array
    {
        return [
            '5min' => $this->aggregate5Min(),
            '1hour' => $this->aggregate1Hour(),
            '1day' => $this->aggregate1Day(),
        ];
    }
}
