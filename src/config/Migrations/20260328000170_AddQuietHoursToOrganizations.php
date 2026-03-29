<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddQuietHoursToOrganizations Migration (P4-008)
 *
 * Adds quiet hours configuration columns to the organizations table.
 * Quiet hours allow organizations to suppress non-critical alerts during
 * specified time windows (e.g., overnight).
 */
class AddQuietHoursToOrganizations extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('organizations');

        $table
            ->addColumn('quiet_hours_enabled', 'boolean', [
                'null' => false,
                'default' => false,
                'comment' => 'Whether quiet hours are enabled for this organization',
            ])
            ->addColumn('quiet_hours_start', 'string', [
                'limit' => 5,
                'null' => false,
                'default' => '22:00',
                'comment' => 'Quiet hours start time in HH:MM format',
            ])
            ->addColumn('quiet_hours_end', 'string', [
                'limit' => 5,
                'null' => false,
                'default' => '08:00',
                'comment' => 'Quiet hours end time in HH:MM format',
            ])
            ->addColumn('quiet_hours_timezone', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => 'UTC',
                'comment' => 'Timezone for quiet hours calculation',
            ])
            ->addColumn('quiet_hours_suppress_level', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'non_critical',
                'comment' => 'Suppress level: non_critical, all, none',
            ])
            ->update();
    }
}
