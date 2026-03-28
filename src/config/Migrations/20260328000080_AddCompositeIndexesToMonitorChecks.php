<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddCompositeIndexesToMonitorChecks Migration
 *
 * TASK-DB-001: Add composite indexes for common query patterns on monitor_checks.
 * Drop redundant single-column indexes that are covered by the new composite indexes.
 */
class AddCompositeIndexesToMonitorChecks extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $table = $this->table('monitor_checks');

        // Add composite indexes for common query patterns
        $table->addIndex(['monitor_id', 'checked_at'], [
            'name' => 'idx_mc_monitor_checked',
            'order' => ['checked_at' => 'DESC'],
        ]);

        $table->addIndex(['monitor_id', 'status', 'checked_at'], [
            'name' => 'idx_mc_monitor_status_checked',
            'order' => ['checked_at' => 'DESC'],
        ]);

        $table->addIndex(['organization_id', 'checked_at'], [
            'name' => 'idx_mc_org_checked',
            'order' => ['checked_at' => 'DESC'],
        ]);

        $table->update();

        // Drop old single-column indexes that are now redundant
        // Use try/catch since they may not exist in all environments
        try {
            $table->removeIndexByName('idx_monitor_checks_monitor');
            $table->update();
        } catch (\Exception $e) {
            // Index may not exist, safe to ignore
        }

        try {
            $table->removeIndexByName('idx_monitor_checks_date');
            $table->update();
        } catch (\Exception $e) {
            // Index may not exist, safe to ignore
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $table = $this->table('monitor_checks');

        // Restore the original single-column indexes
        $table->addIndex(['monitor_id'], ['name' => 'idx_monitor_checks_monitor']);
        $table->addIndex(['checked_at'], ['name' => 'idx_monitor_checks_date']);
        $table->update();

        // Drop the composite indexes
        try {
            $table->removeIndexByName('idx_mc_monitor_checked');
            $table->update();
        } catch (\Exception $e) {
            // Safe to ignore
        }

        try {
            $table->removeIndexByName('idx_mc_monitor_status_checked');
            $table->update();
        } catch (\Exception $e) {
            // Safe to ignore
        }

        try {
            $table->removeIndexByName('idx_mc_org_checked');
            $table->update();
        } catch (\Exception $e) {
            // Safe to ignore
        }
    }
}
