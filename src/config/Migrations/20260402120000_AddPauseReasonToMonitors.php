<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Add pause_reason column to monitors table.
 *
 * Tracks why a monitor was paused (e.g., 'trial_expired', 'manual', 'plan_downgrade').
 * NULL means the monitor was not programmatically paused.
 */
class AddPauseReasonToMonitors extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('monitors');
        $table->addColumn('pause_reason', 'string', [
            'limit' => 50,
            'null' => true,
            'default' => null,
            'after' => 'active',
        ]);
        $table->update();
    }
}
