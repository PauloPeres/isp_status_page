<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateMaintenanceWindows Migration
 *
 * Creates the maintenance_windows table for scheduled maintenance periods.
 */
class CreateMaintenanceWindows extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('maintenance_windows');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization this maintenance window belongs to',
            ])
            ->addColumn('title', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Maintenance window title',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Detailed description of the maintenance',
            ])
            ->addColumn('monitor_ids', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON array of affected monitor IDs',
            ])
            ->addColumn('starts_at', 'datetime', [
                'null' => false,
                'comment' => 'Maintenance start time',
            ])
            ->addColumn('ends_at', 'datetime', [
                'null' => false,
                'comment' => 'Maintenance end time',
            ])
            ->addColumn('auto_suppress_alerts', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether to suppress alerts during maintenance',
            ])
            ->addColumn('notify_subscribers', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether to notify subscribers about maintenance',
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'scheduled',
                'comment' => 'Maintenance status: scheduled, in_progress, completed, cancelled',
            ])
            ->addColumn('created_by', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'User who created this maintenance window',
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
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_maintenance_windows_organization_id',
            ])
            ->addIndex(['organization_id'], [
                'name' => 'idx_maintenance_windows_organization_id',
            ])
            ->addIndex(['status'], [
                'name' => 'idx_maintenance_windows_status',
            ])
            ->addIndex(['starts_at'], [
                'name' => 'idx_maintenance_windows_starts_at',
            ])
            ->addIndex(['ends_at'], [
                'name' => 'idx_maintenance_windows_ends_at',
            ])
            ->create();
    }
}
