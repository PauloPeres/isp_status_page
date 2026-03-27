<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateHeartbeats Migration
 *
 * Creates the heartbeats table for heartbeat/cron monitoring.
 */
class CreateHeartbeats extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('heartbeats');

        $table
            ->addColumn('monitor_id', 'integer', [
                'null' => false,
                'comment' => 'Associated monitor',
            ])
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization this heartbeat belongs to',
            ])
            ->addColumn('token', 'string', [
                'limit' => 64,
                'null' => false,
                'comment' => 'Unique token for ping endpoint',
            ])
            ->addColumn('last_ping_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Last time a ping was received',
            ])
            ->addColumn('expected_interval', 'integer', [
                'null' => false,
                'default' => 300,
                'comment' => 'Expected interval between pings in seconds',
            ])
            ->addColumn('grace_period', 'integer', [
                'null' => true,
                'default' => 60,
                'comment' => 'Grace period in seconds before marking as down',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp',
            ])
            ->addForeignKey('monitor_id', 'monitors', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_heartbeats_monitor_id',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'NO_ACTION',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_heartbeats_organization_id',
            ])
            ->addIndex(['token'], [
                'unique' => true,
                'name' => 'idx_heartbeats_token_unique',
            ])
            ->addIndex(['monitor_id'], [
                'name' => 'idx_heartbeats_monitor_id',
            ])
            ->addIndex(['organization_id'], [
                'name' => 'idx_heartbeats_organization_id',
            ])
            ->create();
    }
}
