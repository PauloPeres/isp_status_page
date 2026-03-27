<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddOrganizationIdToAllTables Migration
 *
 * TASK-602: Add organization_id FK to all existing tenant-scoped tables.
 *
 * Steps:
 * 1. Insert "Default Organization" into organizations table (id=1)
 * 2. Add organization_id as NULLABLE integer column to each table
 * 3. UPDATE all existing rows to set organization_id = 1
 * 4. ALTER each column to NOT NULL
 * 5. Add foreign key constraints to organizations(id)
 * 6. Add indexes on organization_id
 */
class AddOrganizationIdToAllTables extends AbstractMigration
{
    /**
     * Tables that need organization_id added
     *
     * @var array<string>
     */
    private array $tenantTables = [
        'monitors',
        'incidents',
        'monitor_checks',
        'alert_rules',
        'alert_logs',
        'subscribers',
        'subscriptions',
        'integrations',
        'integration_logs',
    ];

    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        // Step 1: Create the Default Organization
        // Use PHP date and Phinx insert API for cross-database compatibility
        // (SQLite uses datetime('now'), PostgreSQL uses NOW() -- neither is portable)
        $now = date('Y-m-d H:i:s');
        $exists = $this->fetchRow("SELECT id FROM organizations WHERE id = 1");
        if (!$exists) {
            $this->table('organizations')->insert([
                'id' => 1,
                'name' => 'Default Organization',
                'slug' => 'default',
                'plan' => 'free',
                'timezone' => 'UTC',
                'language' => 'en',
                'active' => true,
                'created' => $now,
                'modified' => $now,
            ])->saveData();
        }

        // Steps 2-6 for each table
        foreach ($this->tenantTables as $tableName) {
            $table = $this->table($tableName);

            // Step 2: Add organization_id as NULLABLE
            $table->addColumn('organization_id', 'integer', [
                'null' => true,
                'default' => null,
                'after' => 'id',
                'comment' => 'Foreign key to organizations table',
            ]);
            $table->update();

            // Step 3: UPDATE all existing rows to set organization_id = 1
            $this->execute("UPDATE {$tableName} SET organization_id = 1 WHERE organization_id IS NULL");

            // Step 4: Change column to NOT NULL
            // Use Phinx API for cross-database compatibility (SQLite + PostgreSQL)
            $table->changeColumn('organization_id', 'integer', [
                'null' => false,
                'default' => 1,
                'comment' => 'Foreign key to organizations table',
            ]);
            $table->update();

            // Step 5: Add foreign key constraint
            $table->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => "fk_{$tableName}_organization_id",
            ]);

            // Step 6: Add index on organization_id
            $table->addIndex(['organization_id'], [
                'name' => "idx_{$tableName}_organization_id",
            ]);

            $table->update();
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        // Remove organization_id from each table (in reverse order)
        foreach (array_reverse($this->tenantTables) as $tableName) {
            $table = $this->table($tableName);

            // Remove foreign key first
            $table->dropForeignKey('organization_id');
            $table->update();

            // Remove the column (index is dropped automatically with the column)
            $table->removeColumn('organization_id');
            $table->update();
        }

        // Remove the Default Organization
        $this->execute("DELETE FROM organizations WHERE id = 1 AND slug = 'default'");
    }
}
