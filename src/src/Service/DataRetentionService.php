<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * DataRetentionService
 *
 * Plan-aware data retention for monitor checks and rollup data.
 * Deletes raw checks and old rollups based on each organization's plan tier.
 * Uses batched deletes (10,000 rows at a time) to avoid locking.
 */
class DataRetentionService
{
    use LocatorAwareTrait;

    /**
     * Batch size for delete operations
     */
    private const BATCH_SIZE = 10000;

    /**
     * Microseconds to sleep between batches (100ms)
     */
    private const BATCH_SLEEP_US = 100000;

    /**
     * Rollup retention periods (in days) by period type.
     * These are fixed regardless of plan.
     */
    private const ROLLUP_RETENTION = [
        '5min' => 30,    // Keep 5-min rollups for 30 days
        '1hour' => 180,  // Keep 1-hour rollups for 6 months
        '1day' => 730,   // Keep 1-day rollups for 2 years
    ];

    /**
     * Clean up raw monitor checks based on each organization's plan retention.
     *
     * @return array<string, int> Deleted counts keyed by organization slug
     */
    public function cleanup(): array
    {
        $orgsTable = $this->fetchTable('Organizations');
        $plansTable = $this->fetchTable('Plans');
        $checksTable = $this->fetchTable('MonitorChecks');

        $plans = $plansTable->find()->all()->indexBy('slug')->toArray();
        $orgs = $orgsTable->find()->where(['active' => true])->all();

        $stats = [];
        foreach ($orgs as $org) {
            $plan = $plans[$org->plan] ?? $plans['free'] ?? null;
            if ($plan === null) {
                Log::warning("DataRetention: No plan found for org '{$org->slug}' (plan: {$org->plan}), skipping");
                continue;
            }

            $retentionDays = $plan->data_retention_days;
            $cutoff = DateTime::now()->subDays($retentionDays);

            $deleted = 0;
            do {
                try {
                    $batch = $checksTable->getConnection()->execute(
                        "DELETE FROM monitor_checks WHERE id IN (
                            SELECT id FROM monitor_checks
                            WHERE organization_id = ? AND checked_at < ?
                            LIMIT ?
                        )",
                        [$org->id, $cutoff->format('Y-m-d H:i:s'), self::BATCH_SIZE]
                    )->rowCount();

                    $deleted += $batch;

                    if ($batch > 0) {
                        usleep(self::BATCH_SLEEP_US);
                    }
                } catch (\Exception $e) {
                    Log::error("DataRetention: Error cleaning checks for org '{$org->slug}': {$e->getMessage()}");
                    break;
                }
            } while ($batch >= self::BATCH_SIZE);

            if ($deleted > 0) {
                $stats[$org->slug] = $deleted;
                Log::info("DataRetention: Deleted {$deleted} checks for org '{$org->slug}' (retention: {$retentionDays} days)");
            }
        }

        return $stats;
    }

    /**
     * Clean up old rollup data based on fixed retention periods per period type.
     *
     * @return array<string, int> Deleted counts keyed by period type
     */
    public function cleanupRollups(): array
    {
        $rollupTable = $this->fetchTable('MonitorChecksRollup');
        $stats = [];

        foreach (self::ROLLUP_RETENTION as $periodType => $retentionDays) {
            $cutoff = DateTime::now()->subDays($retentionDays);
            $deleted = 0;

            do {
                try {
                    $batch = $rollupTable->getConnection()->execute(
                        "DELETE FROM monitor_checks_rollup WHERE id IN (
                            SELECT id FROM monitor_checks_rollup
                            WHERE period_type = ? AND period_start < ?
                            LIMIT ?
                        )",
                        [$periodType, $cutoff->format('Y-m-d H:i:s'), self::BATCH_SIZE]
                    )->rowCount();

                    $deleted += $batch;

                    if ($batch > 0) {
                        usleep(self::BATCH_SLEEP_US);
                    }
                } catch (\Exception $e) {
                    Log::error("DataRetention: Error cleaning '{$periodType}' rollups: {$e->getMessage()}");
                    break;
                }
            } while ($batch >= self::BATCH_SIZE);

            $stats[$periodType] = $deleted;

            if ($deleted > 0) {
                Log::info("DataRetention: Deleted {$deleted} '{$periodType}' rollup rows (retention: {$retentionDays} days)");
            }
        }

        return $stats;
    }
}
