<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateScheduledReports Migration (P4-010)
 *
 * Creates the scheduled_reports table for automated email report delivery.
 */
class CreateScheduledReports extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('scheduled_reports');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'FK to organizations',
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Report name',
            ])
            ->addColumn('frequency', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'weekly',
                'comment' => 'Report frequency: weekly or monthly',
            ])
            ->addColumn('recipients', 'text', [
                'null' => false,
                'comment' => 'JSON array of recipient email addresses',
            ])
            ->addColumn('include_uptime', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Include uptime percentage in report',
            ])
            ->addColumn('include_response_time', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Include response time stats in report',
            ])
            ->addColumn('include_incidents', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Include incident summary in report',
            ])
            ->addColumn('include_sla', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Include SLA status in report',
            ])
            ->addColumn('last_sent_at', 'timestamp', [
                'null' => true,
                'default' => null,
                'comment' => 'When the report was last sent',
            ])
            ->addColumn('next_send_at', 'timestamp', [
                'null' => true,
                'default' => null,
                'comment' => 'When the report is next due to be sent',
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether this scheduled report is active',
            ])
            ->addColumn('created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Created timestamp',
            ])
            ->addColumn('modified', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Modified timestamp',
            ])
            ->addIndex(['organization_id'])
            ->addIndex(['active'])
            ->addIndex(['next_send_at'])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->create();
    }
}
