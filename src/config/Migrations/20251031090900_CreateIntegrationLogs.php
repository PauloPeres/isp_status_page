<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateIntegrationLogs Migration
 *
 * Creates the integration_logs table for logging integration activities.
 */
class CreateIntegrationLogs extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('integration_logs');

        $table
            ->addColumn('integration_id', 'integer', [
                'null' => false,
                'comment' => 'Foreign key to integrations table'
            ])
            ->addColumn('action', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Action performed'
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'Status: success, error, warning'
            ])
            ->addColumn('message', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Log message'
            ])
            ->addColumn('details', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON details'
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp'
            ])
            ->addIndex(['integration_id'], ['name' => 'idx_integration_logs_integration'])
            ->addIndex(['created'], ['name' => 'idx_integration_logs_created'])
            ->addForeignKey('integration_id', 'integrations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->create();
    }
}
