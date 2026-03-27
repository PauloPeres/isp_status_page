<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddRegionToMonitorChecks Migration
 *
 * Adds region_id column to monitor_checks table for multi-region check tracking.
 */
class AddRegionToMonitorChecks extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('monitor_checks');

        $table
            ->addColumn('region_id', 'integer', [
                'null' => true,
                'default' => null,
                'after' => 'monitor_id',
                'comment' => 'Check region (NULL = default/local region)',
            ])
            ->addIndex(['region_id'])
            ->addForeignKey('region_id', 'check_regions', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->update();
    }
}
