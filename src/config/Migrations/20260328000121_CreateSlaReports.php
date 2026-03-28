<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateSlaReports Migration
 *
 * Creates the sla_reports table for storing historical SLA compliance data.
 */
class CreateSlaReports extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('sla_reports');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization that owns this report',
            ])
            ->addColumn('sla_definition_id', 'integer', [
                'null' => false,
                'comment' => 'SLA definition this report is for',
            ])
            ->addColumn('monitor_id', 'integer', [
                'null' => false,
                'comment' => 'Monitor this report covers',
            ])
            ->addColumn('period_start', 'date', [
                'null' => false,
                'comment' => 'Start date of the reporting period',
            ])
            ->addColumn('period_end', 'date', [
                'null' => false,
                'comment' => 'End date of the reporting period',
            ])
            ->addColumn('period_type', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'Period type: monthly, quarterly, yearly',
            ])
            ->addColumn('target_uptime', 'decimal', [
                'precision' => 5,
                'scale' => 3,
                'null' => false,
                'comment' => 'Target uptime for this period (snapshot)',
            ])
            ->addColumn('actual_uptime', 'decimal', [
                'precision' => 5,
                'scale' => 3,
                'null' => false,
                'comment' => 'Actual measured uptime percentage',
            ])
            ->addColumn('total_minutes', 'integer', [
                'null' => false,
                'comment' => 'Total minutes in the period',
            ])
            ->addColumn('downtime_minutes', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'comment' => 'Actual downtime in minutes',
            ])
            ->addColumn('allowed_downtime_minutes', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'comment' => 'Maximum allowed downtime in minutes per SLA',
            ])
            ->addColumn('remaining_downtime_minutes', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
                'comment' => 'Remaining downtime budget in minutes',
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'compliant',
                'comment' => 'SLA status: compliant, at_risk, breached',
            ])
            ->addColumn('incidents_count', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Number of incidents in this period',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp',
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Last modification timestamp',
            ])
            ->addIndex(['organization_id'], [
                'name' => 'idx_sla_reports_org',
            ])
            ->addIndex(['sla_definition_id'], [
                'name' => 'idx_sla_reports_definition',
            ])
            ->addIndex(['monitor_id'], [
                'name' => 'idx_sla_reports_monitor',
            ])
            ->addIndex(['period_start', 'period_end'], [
                'name' => 'idx_sla_reports_period',
            ])
            ->addIndex(['status'], [
                'name' => 'idx_sla_reports_status',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_sla_reports_organization',
            ])
            ->addForeignKey('sla_definition_id', 'sla_definitions', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_sla_reports_definition',
            ])
            ->addForeignKey('monitor_id', 'monitors', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_sla_reports_monitor',
            ])
            ->create();
    }
}
