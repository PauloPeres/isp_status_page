<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Add recurring maintenance window support.
 */
class AddRecurringToMaintenanceWindows extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('maintenance_windows');

        if (!$table->hasColumn('is_recurring')) {
            $table
                ->addColumn('is_recurring', 'boolean', ['default' => false, 'null' => false, 'after' => 'status'])
                ->addColumn('recurrence_pattern', 'string', ['limit' => 20, 'null' => true, 'default' => null, 'after' => 'is_recurring', 'comment' => 'daily, weekly, biweekly, monthly'])
                ->addColumn('recurrence_end_date', 'date', ['null' => true, 'default' => null, 'after' => 'recurrence_pattern'])
                ->addColumn('parent_window_id', 'integer', ['null' => true, 'default' => null, 'after' => 'recurrence_end_date', 'comment' => 'FK to parent recurring window'])
                ->update();
        }
    }
}
