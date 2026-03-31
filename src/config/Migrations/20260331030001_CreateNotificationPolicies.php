<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateNotificationPolicies Migration
 *
 * Creates the notification_policies and notification_policy_steps tables.
 * A policy is a reusable notification chain that defines WHEN and HOW to notify.
 * Steps reference channels and define escalation delays.
 */
class CreateNotificationPolicies extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        // Create notification_policies table
        $policiesTable = $this->table('notification_policies');

        $policiesTable
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization that owns this notification policy',
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Human-readable policy name',
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Optional description of the notification policy',
            ])
            ->addColumn('trigger_type', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'down',
                'comment' => 'When to trigger: down, up, degraded, any',
            ])
            ->addColumn('repeat_interval_minutes', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Minutes between repeat notifications (0 = notify once)',
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether this policy is active',
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
            ->addIndex(['organization_id'], [
                'name' => 'idx_notification_policies_org',
            ])
            ->addIndex(['organization_id', 'active'], [
                'name' => 'idx_notification_policies_org_active',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_notification_policies_organization',
            ])
            ->create();

        // Create notification_policy_steps table
        $stepsTable = $this->table('notification_policy_steps');

        $stepsTable
            ->addColumn('notification_policy_id', 'integer', [
                'null' => false,
                'comment' => 'Parent notification policy',
            ])
            ->addColumn('step_order', 'integer', [
                'null' => false,
                'default' => 1,
                'comment' => 'Step order number (1 = first step)',
            ])
            ->addColumn('delay_minutes', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Minutes to wait before executing this step (0 = immediate)',
            ])
            ->addColumn('notification_channel_id', 'integer', [
                'null' => false,
                'comment' => 'Notification channel to use for this step',
            ])
            ->addColumn('notify_on_resolve', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether to notify when incident resolves',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp',
            ])
            ->addIndex(['notification_policy_id'], [
                'name' => 'idx_notification_policy_steps_policy',
            ])
            ->addForeignKey('notification_policy_id', 'notification_policies', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_notification_policy_steps_policy',
            ])
            ->addForeignKey('notification_channel_id', 'notification_channels', 'id', [
                'delete' => 'NO_ACTION',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_notification_policy_steps_channel',
            ])
            ->create();
    }
}
