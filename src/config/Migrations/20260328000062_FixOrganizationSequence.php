<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * FixOrganizationSequence Migration
 *
 * BUG 6 fix: The Default Organization insert in migration 20260328000003
 * uses an explicit id=1, which can leave the PostgreSQL sequence behind.
 * This causes a PK conflict when the next organization is created via
 * registration (PostgreSQL auto-assigns the next sequence value, which
 * may collide with the explicitly-inserted id=1).
 *
 * Fix: Reset the organizations_id_seq to MAX(id) from the table.
 */
class FixOrganizationSequence extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        // Only run on PostgreSQL — SQLite doesn't use sequences
        $adapterType = $this->getAdapter()->getAdapterType();
        if ($adapterType === 'pgsql') {
            $this->execute("SELECT setval('organizations_id_seq', (SELECT COALESCE(MAX(id), 1) FROM organizations))");
        }
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        // No rollback needed — sequence reset is idempotent
    }
}
