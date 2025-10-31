<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateAlertLogs Migration
 *
 * Creates the alert_logs table for logging all sent alerts.
 */
class CreateAlertLogs extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('alert_logs');

        $table
            ->addColumn('alert_rule_id', 'integer', [
                'null' => false,
                'comment' => 'Foreign key to alert_rules table'
            ])
            ->addColumn('incident_id', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'Foreign key to incidents table'
            ])
            ->addColumn('monitor_id', 'integer', [
                'null' => false,
                'comment' => 'Foreign key to monitors table'
            ])
            ->addColumn('channel', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Alert channel used'
            ])
            ->addColumn('recipient', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Recipient address/phone'
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'Status: sent, failed, queued'
            ])
            ->addColumn('sent_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'When alert was sent'
            ])
            ->addColumn('error_message', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Error message if failed'
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp'
            ])
            ->addIndex(['alert_rule_id'], ['name' => 'idx_alert_logs_rule'])
            ->addIndex(['incident_id'], ['name' => 'idx_alert_logs_incident'])
            ->addIndex(['monitor_id'], ['name' => 'idx_alert_logs_monitor'])
            ->addIndex(['created'], ['name' => 'idx_alert_logs_created'])
            ->addForeignKey('alert_rule_id', 'alert_rules', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->addForeignKey('incident_id', 'incidents', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION'
            ])
            ->addForeignKey('monitor_id', 'monitors', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->create();
    }
}
