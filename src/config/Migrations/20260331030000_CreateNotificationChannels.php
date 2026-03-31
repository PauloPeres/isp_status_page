<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateNotificationChannels Migration
 *
 * Creates the notification_channels table for reusable notification connections.
 * Each channel belongs to an organization and defines a configured connection
 * to a notification service (email, Slack, Telegram, etc.).
 */
class CreateNotificationChannels extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('notification_channels');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization that owns this notification channel',
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Human-readable channel name',
            ])
            ->addColumn('type', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Channel type: email, slack, discord, telegram, sms, whatsapp, pagerduty, opsgenie, webhook',
            ])
            ->addColumn('configuration', 'text', [
                'null' => false,
                'comment' => 'JSON configuration specific to channel type',
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether this channel is active',
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
                'name' => 'idx_notification_channels_org',
            ])
            ->addIndex(['organization_id', 'active'], [
                'name' => 'idx_notification_channels_org_active',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_notification_channels_organization',
            ])
            ->create();
    }
}
