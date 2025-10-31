<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateAlertRules Migration
 *
 * Creates the alert_rules table for notification rules per monitor.
 */
class CreateAlertRules extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('alert_rules');

        $table
            ->addColumn('monitor_id', 'integer', [
                'null' => false,
                'comment' => 'Foreign key to monitors table'
            ])
            ->addColumn('channel', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Alert channel: email, whatsapp, telegram, sms, phone'
            ])
            ->addColumn('trigger_on', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Trigger: on_down, on_up, on_degraded, on_change'
            ])
            ->addColumn('throttle_minutes', 'integer', [
                'null' => false,
                'default' => 5,
                'comment' => 'Minimum minutes between alerts'
            ])
            ->addColumn('recipients', 'text', [
                'null' => false,
                'comment' => 'JSON array of recipients'
            ])
            ->addColumn('template', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Custom message template (optional)'
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Is alert rule active'
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
            ->addIndex(['monitor_id'], ['name' => 'idx_alert_rules_monitor'])
            ->addIndex(['active'], ['name' => 'idx_alert_rules_active'])
            ->addForeignKey('monitor_id', 'monitors', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->create();
    }
}
