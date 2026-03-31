<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddNotificationPolicyIdToMonitors Migration
 *
 * Adds notification_policy_id FK to monitors table so each monitor
 * can be assigned a notification policy.
 */
class AddNotificationPolicyIdToMonitors extends AbstractMigration
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
            ->addColumn('notification_policy_id', 'integer', [
                'null' => true,
                'default' => null,
                'after' => 'escalation_policy_id',
                'comment' => 'Notification policy assigned to this monitor',
            ])
            ->addForeignKey('notification_policy_id', 'notification_policies', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_monitors_notification_policy',
            ])
            ->update();
    }
}
