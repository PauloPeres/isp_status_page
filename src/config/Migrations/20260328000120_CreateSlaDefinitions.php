<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateSlaDefinitions Migration
 *
 * Creates the sla_definitions table for SLA tracking per monitor.
 * Each monitor can have at most one SLA definition.
 */
class CreateSlaDefinitions extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('sla_definitions');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization that owns this SLA',
            ])
            ->addColumn('monitor_id', 'integer', [
                'null' => false,
                'comment' => 'Monitor this SLA applies to',
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Human-readable SLA name',
            ])
            ->addColumn('target_uptime', 'decimal', [
                'precision' => 5,
                'scale' => 3,
                'null' => false,
                'default' => 99.900,
                'comment' => 'Target uptime percentage, e.g. 99.9',
            ])
            ->addColumn('measurement_period', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'monthly',
                'comment' => 'Measurement period: monthly, quarterly, yearly',
            ])
            ->addColumn('breach_notification', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Send notifications on SLA breach',
            ])
            ->addColumn('warning_threshold', 'decimal', [
                'precision' => 5,
                'scale' => 3,
                'null' => true,
                'default' => 99.950,
                'comment' => 'Warning threshold percentage before breach',
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether this SLA definition is active',
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
                'name' => 'idx_sla_definitions_org',
            ])
            ->addIndex(['monitor_id'], [
                'name' => 'idx_sla_definitions_monitor',
                'unique' => true,
            ])
            ->addIndex(['active'], [
                'name' => 'idx_sla_definitions_active',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_sla_definitions_organization',
            ])
            ->addForeignKey('monitor_id', 'monitors', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_sla_definitions_monitor',
            ])
            ->create();
    }
}
