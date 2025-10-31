<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateMonitorChecks Migration
 *
 * Creates the monitor_checks table which stores the history of all monitor checks.
 */
class CreateMonitorChecks extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('monitor_checks');

        $table
            ->addColumn('monitor_id', 'integer', [
                'null' => false,
                'comment' => 'Foreign key to monitors table'
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'Check result: success, failure, timeout, error'
            ])
            ->addColumn('response_time', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'Response time in milliseconds'
            ])
            ->addColumn('status_code', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'HTTP status code or similar'
            ])
            ->addColumn('error_message', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Error message if check failed'
            ])
            ->addColumn('details', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON details of the check'
            ])
            ->addColumn('checked_at', 'datetime', [
                'null' => false,
                'comment' => 'When the check was performed'
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp'
            ])
            ->addIndex(['monitor_id'], ['name' => 'idx_monitor_checks_monitor'])
            ->addIndex(['checked_at'], ['name' => 'idx_monitor_checks_date'])
            ->addForeignKey('monitor_id', 'monitors', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->create();
    }
}
