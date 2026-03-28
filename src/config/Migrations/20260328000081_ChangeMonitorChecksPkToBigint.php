<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * ChangeMonitorChecksPkToBigint Migration
 *
 * TASK-DB-010: Change monitor_checks.id from INTEGER (32-bit, max 2.1B) to BIGINT (64-bit).
 * At target scale (14.4M rows/day), the 32-bit PK would exhaust in ~149 days.
 * Uses raw SQL for PostgreSQL compatibility since Phinx does not easily support PK type changes.
 */
class ChangeMonitorChecksPkToBigint extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute("ALTER TABLE monitor_checks ALTER COLUMN id TYPE BIGINT");
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        // Reverse to INTEGER — this may fail if any id values exceed 2^31-1
        $this->execute("ALTER TABLE monitor_checks ALTER COLUMN id TYPE INTEGER");
    }
}
