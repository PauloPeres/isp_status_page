<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateSubscriptions Migration
 *
 * Creates the subscriptions table linking subscribers to specific monitors.
 */
class CreateSubscriptions extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('subscriptions');

        $table
            ->addColumn('subscriber_id', 'integer', [
                'null' => false,
                'comment' => 'Foreign key to subscribers table'
            ])
            ->addColumn('monitor_id', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'Foreign key to monitors table (NULL = all monitors)'
            ])
            ->addColumn('notify_on_down', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Notify when service goes down'
            ])
            ->addColumn('notify_on_up', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Notify when service comes back up'
            ])
            ->addColumn('notify_on_degraded', 'boolean', [
                'null' => false,
                'default' => false,
                'comment' => 'Notify when service is degraded'
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
            ->addIndex(['subscriber_id'], ['name' => 'idx_subscriptions_subscriber'])
            ->addIndex(['monitor_id'], ['name' => 'idx_subscriptions_monitor'])
            ->addForeignKey('subscriber_id', 'subscribers', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->addForeignKey('monitor_id', 'monitors', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->create();
    }
}
