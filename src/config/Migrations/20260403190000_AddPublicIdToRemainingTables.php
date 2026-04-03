<?php
declare(strict_types=1);

use Cake\Utility\Text;
use Migrations\AbstractMigration;

/**
 * AddPublicIdToRemainingTables Migration
 *
 * Adds a UUID-based public_id column to remaining tables that were not
 * covered by the initial AddPublicIdToKeyTables migration.
 * Internal integer IDs are kept for joins and performance;
 * API responses and URLs will use the UUID public_id instead.
 */
class AddPublicIdToRemainingTables extends AbstractMigration
{
    /**
     * Tables that need a public_id column.
     *
     * @var list<string>
     */
    private const TABLES = [
        'integrations',
        'sla_definitions',
        'scheduled_reports',
        'escalation_policies',
        'webhook_endpoints',
        'check_regions',
        'notification_schedules',
        'alert_rules',
    ];

    /**
     * Up Method.
     *
     * For each table:
     *  1. Add public_id as NULLABLE VARCHAR(36)
     *  2. Backfill existing rows with UUID v4 values
     *  3. Alter the column to NOT NULL and add a UNIQUE index
     *
     * @return void
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            // 1. Add column as NULLABLE first (so existing rows don't fail)
            $table = $this->table($tableName);
            $table
                ->addColumn('public_id', 'string', [
                    'limit' => 36,
                    'null' => true,
                    'default' => null,
                    'after' => 'id',
                    'comment' => 'UUID v4 for public-facing API responses and URLs',
                ])
                ->update();

            // 2. Backfill existing rows with UUIDs
            $rows = $this->fetchAll("SELECT id FROM {$tableName} WHERE public_id IS NULL");
            foreach ($rows as $row) {
                $uuid = Text::uuid();
                $this->execute(
                    "UPDATE {$tableName} SET public_id = '{$uuid}' WHERE id = {$row['id']}"
                );
            }

            // 3. Make NOT NULL and add UNIQUE index
            $table = $this->table($tableName);
            $table
                ->changeColumn('public_id', 'string', [
                    'limit' => 36,
                    'null' => false,
                ])
                ->addIndex(['public_id'], [
                    'unique' => true,
                    'name' => "idx_{$tableName}_public_id",
                ])
                ->update();
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            $table = $this->table($tableName);

            if ($table->hasIndex("idx_{$tableName}_public_id")) {
                $table->removeIndexByName("idx_{$tableName}_public_id");
            }

            $table->removeColumn('public_id');
            $table->update();
        }
    }
}
