<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Add recurring schedule fields for day-of-week + time window support.
 *
 * Recurring maintenance windows need:
 * - recurrence_days: JSON array of day abbreviations (e.g. ["mon","sun"])
 * - recurrence_time_start: Time-only start (e.g. "02:00")
 * - recurrence_time_end: Time-only end (e.g. "06:00")
 * - effective_from: Date when the recurrence begins
 */
class AddRecurringScheduleFields extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('maintenance_windows');

        if (!$table->hasColumn('recurrence_days')) {
            $table
                ->addColumn('recurrence_days', 'text', [
                    'null' => true,
                    'default' => null,
                    'after' => 'recurrence_pattern',
                    'comment' => 'JSON array of day abbreviations: mon,tue,wed,thu,fri,sat,sun',
                ])
                ->addColumn('recurrence_time_start', 'string', [
                    'limit' => 5,
                    'null' => true,
                    'default' => null,
                    'after' => 'recurrence_days',
                    'comment' => 'Time-only start for recurring windows (HH:MM)',
                ])
                ->addColumn('recurrence_time_end', 'string', [
                    'limit' => 5,
                    'null' => true,
                    'default' => null,
                    'after' => 'recurrence_time_start',
                    'comment' => 'Time-only end for recurring windows (HH:MM)',
                ])
                ->addColumn('effective_from', 'date', [
                    'null' => true,
                    'default' => null,
                    'after' => 'recurrence_time_end',
                    'comment' => 'Date when recurring schedule becomes active',
                ])
                ->update();
        }
    }
}
