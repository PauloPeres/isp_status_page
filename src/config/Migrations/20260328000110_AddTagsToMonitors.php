<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddTagsToMonitors Migration (P2-012)
 *
 * Adds a `tags` TEXT column (JSON array) to the monitors table
 * for grouping/tagging monitors.
 */
class AddTagsToMonitors extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('monitors');
        $table->addColumn('tags', 'text', [
            'null' => true,
            'default' => null,
            'after' => 'display_order',
        ]);
        $table->update();
    }
}
