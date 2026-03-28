<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateMonitorCheckDetails Migration (TASK-DB-011)
 *
 * Creates a companion table for monitor_checks to store error_message
 * and details TEXT columns separately. This reduces the main table's
 * heap size by ~30% since most checks succeed and have no error data.
 *
 * Note: error_message and details are NOT removed from monitor_checks
 * yet -- that would be a separate migration after all code is updated.
 */
class CreateMonitorCheckDetails extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('monitor_check_details', [
            'id' => false,
            'primary_key' => ['check_id'],
        ]);

        $table
            ->addColumn('check_id', 'biginteger', [
                'null' => false,
                'signed' => false,
                'comment' => 'FK to monitor_checks.id -- also serves as PK',
            ])
            ->addColumn('error_message', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Error message from the check, if any',
            ])
            ->addColumn('details', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON-encoded details/metadata from the check',
            ])
            ->addColumn('created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'When this row was created',
            ])
            ->create();
    }
}
