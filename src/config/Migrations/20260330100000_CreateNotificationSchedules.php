<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Create notification_schedules table (C-05)
 *
 * Allows per-channel, per-severity notification scheduling.
 * Each row defines a time window when notifications of a given
 * channel+severity combination should be suppressed or allowed.
 */
class CreateNotificationSchedules extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('notification_schedules');
        $table
            ->addColumn('organization_id', 'integer', ['null' => false])
            ->addColumn('name', 'string', ['limit' => 200, 'null' => false])
            ->addColumn('channels', 'text', [
                'null' => true,
                'comment' => 'JSON array of channel types (null = all channels). e.g. ["email","slack"]',
            ])
            ->addColumn('severities', 'text', [
                'null' => true,
                'comment' => 'JSON array of severities (null = all). e.g. ["critical","major"]',
            ])
            ->addColumn('action', 'string', [
                'limit' => 20,
                'default' => 'suppress',
                'null' => false,
                'comment' => 'suppress = block during window; allow = only send during window',
            ])
            ->addColumn('days_of_week', 'text', [
                'null' => true,
                'comment' => 'JSON array of days 0-6 (0=Sun). null = every day. e.g. [1,2,3,4,5] for weekdays',
            ])
            ->addColumn('start_time', 'string', [
                'limit' => 5,
                'default' => '22:00',
                'null' => false,
                'comment' => 'HH:MM in org timezone',
            ])
            ->addColumn('end_time', 'string', [
                'limit' => 5,
                'default' => '08:00',
                'null' => false,
                'comment' => 'HH:MM in org timezone',
            ])
            ->addColumn('timezone', 'string', [
                'limit' => 50,
                'default' => 'UTC',
                'null' => false,
            ])
            ->addColumn('active', 'boolean', ['default' => true, 'null' => false])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['organization_id'])
            ->addIndex(['organization_id', 'active'])
            ->addForeignKey('organization_id', 'organizations', 'id', ['delete' => 'CASCADE'])
            ->create();
    }
}
