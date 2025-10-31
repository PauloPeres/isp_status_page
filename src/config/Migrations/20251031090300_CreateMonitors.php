<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateMonitors Migration
 *
 * Creates the monitors table which stores all monitoring configurations.
 */
class CreateMonitors extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('monitors');

        $table
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Monitor name/title'
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Monitor description'
            ])
            ->addColumn('type', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Monitor type: http, ping, port, api, ixc, zabbix'
            ])
            ->addColumn('configuration', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON configuration specific to monitor type'
            ])
            ->addColumn('check_interval', 'integer', [
                'null' => false,
                'default' => 60,
                'comment' => 'Check interval in seconds'
            ])
            ->addColumn('timeout', 'integer', [
                'null' => false,
                'default' => 30,
                'comment' => 'Timeout in seconds'
            ])
            ->addColumn('retry_count', 'integer', [
                'null' => false,
                'default' => 3,
                'comment' => 'Number of retries before marking as down'
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'unknown',
                'comment' => 'Current status: up, down, degraded, unknown'
            ])
            ->addColumn('last_check_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Last check timestamp'
            ])
            ->addColumn('next_check_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Next scheduled check timestamp'
            ])
            ->addColumn('uptime_percentage', 'decimal', [
                'precision' => 5,
                'scale' => 2,
                'null' => true,
                'default' => null,
                'comment' => 'Uptime percentage (calculated)'
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Is monitor active'
            ])
            ->addColumn('visible_on_status_page', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Show on public status page'
            ])
            ->addColumn('display_order', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Display order on status page'
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp'
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Last modification timestamp'
            ])
            ->addIndex(['status'], ['name' => 'idx_monitors_status'])
            ->addIndex(['next_check_at'], ['name' => 'idx_monitors_next_check'])
            ->addIndex(['active'], ['name' => 'idx_monitors_active'])
            ->create();
    }
}
