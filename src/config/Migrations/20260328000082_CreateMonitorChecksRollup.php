<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateMonitorChecksRollup Migration
 *
 * Creates the monitor_checks_rollup table for aggregated check data.
 * Supports 5min, 1hour, and 1day period types.
 */
class CreateMonitorChecksRollup extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('monitor_checks_rollup');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization that owns this rollup',
            ])
            ->addColumn('monitor_id', 'integer', [
                'null' => false,
                'comment' => 'Monitor this rollup belongs to',
            ])
            ->addColumn('period_start', 'timestamp', [
                'null' => false,
                'comment' => 'Start of the aggregation window',
            ])
            ->addColumn('period_end', 'timestamp', [
                'null' => false,
                'comment' => 'End of the aggregation window',
            ])
            ->addColumn('period_type', 'string', [
                'limit' => 10,
                'null' => false,
                'comment' => 'Aggregation period: 5min, 1hour, 1day',
            ])
            ->addColumn('check_count', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Total number of checks in this window',
            ])
            ->addColumn('success_count', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Number of successful checks',
            ])
            ->addColumn('failure_count', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Number of failed checks',
            ])
            ->addColumn('timeout_count', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Number of timed-out checks',
            ])
            ->addColumn('error_count', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Number of errored checks',
            ])
            ->addColumn('avg_response_time', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => true,
                'default' => null,
                'comment' => 'Average response time in ms',
            ])
            ->addColumn('min_response_time', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'Minimum response time in ms',
            ])
            ->addColumn('max_response_time', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'Maximum response time in ms',
            ])
            ->addColumn('uptime_percentage', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => true,
                'default' => null,
                'comment' => 'Uptime percentage for this window',
            ])
            ->addColumn('created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'When this rollup row was created',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->addForeignKey('monitor_id', 'monitors', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->addIndex(['monitor_id', 'period_start', 'period_type'], [
                'name' => 'idx_rollup_monitor_period_unique',
                'unique' => true,
            ])
            ->addIndex(['organization_id', 'period_type', 'period_start'], [
                'name' => 'idx_rollup_org_type_period',
            ])
            ->addIndex(['monitor_id', 'period_type', 'period_start'], [
                'name' => 'idx_rollup_monitor_type_period',
            ])
            ->create();
    }
}
