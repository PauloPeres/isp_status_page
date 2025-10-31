<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateIntegrations Migration
 *
 * Creates the integrations table for external system configurations (IXC, Zabbix, etc).
 */
class CreateIntegrations extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('integrations');

        $table
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Integration name'
            ])
            ->addColumn('type', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Integration type: ixc, zabbix, rest_api'
            ])
            ->addColumn('configuration', 'text', [
                'null' => false,
                'comment' => 'JSON encrypted configuration'
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Is integration active'
            ])
            ->addColumn('last_sync_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Last synchronization timestamp'
            ])
            ->addColumn('last_sync_status', 'string', [
                'limit' => 20,
                'null' => true,
                'default' => null,
                'comment' => 'Last sync status: success, error, warning'
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
            ->addIndex(['type'], ['name' => 'idx_integrations_type'])
            ->addIndex(['active'], ['name' => 'idx_integrations_active'])
            ->create();
    }
}
