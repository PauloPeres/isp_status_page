<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * PartitionMonitorChecks Migration
 *
 * TASK-DB-006: Partition monitor_checks by weekly range on checked_at.
 *
 * Uses raw SQL because Phinx does not support PostgreSQL declarative partitioning.
 * Creates a new partitioned table, copies data from the original, then swaps names.
 * The composite PK (id, checked_at) is required for PostgreSQL partitioned tables
 * since the partition key must be part of any unique/primary key constraint.
 */
class PartitionMonitorChecks extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        // Partitioning is PostgreSQL-only; skip on SQLite
        if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
            return;
        }

        // 1. Create the partitioned table with the same columns
        $this->execute("
            CREATE TABLE monitor_checks_partitioned (
                id BIGSERIAL,
                organization_id INTEGER NOT NULL,
                monitor_id INTEGER NOT NULL,
                region_id INTEGER,
                status VARCHAR(20) NOT NULL DEFAULT 'unknown',
                response_time INTEGER,
                status_code INTEGER,
                error_message TEXT,
                details TEXT,
                checked_at TIMESTAMP NOT NULL,
                created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id, checked_at)
            ) PARTITION BY RANGE (checked_at)
        ");

        // 2. Create initial partitions (1 week back + current week + 4 weeks ahead)
        $startOfWeek = new \DateTime('monday this week');
        for ($i = -1; $i <= 4; $i++) {
            $start = clone $startOfWeek;
            $start->modify("{$i} weeks");
            $end = clone $start;
            $end->modify('+1 week');
            $name = 'monitor_checks_' . $start->format('Y') . 'w' . $start->format('W');
            $this->execute(sprintf(
                "CREATE TABLE %s PARTITION OF monitor_checks_partitioned FOR VALUES FROM ('%s') TO ('%s')",
                $name,
                $start->format('Y-m-d'),
                $end->format('Y-m-d')
            ));
        }

        // 3. Add indexes to the partitioned table (automatically propagated to partitions)
        $this->execute("CREATE INDEX idx_mcp_monitor_checked ON monitor_checks_partitioned (monitor_id, checked_at DESC)");
        $this->execute("CREATE INDEX idx_mcp_monitor_status_checked ON monitor_checks_partitioned (monitor_id, status, checked_at DESC)");
        $this->execute("CREATE INDEX idx_mcp_org_checked ON monitor_checks_partitioned (organization_id, checked_at DESC)");

        // 4. Add foreign keys (requires PostgreSQL 12+)
        $this->execute("ALTER TABLE monitor_checks_partitioned ADD CONSTRAINT fk_mcp_org FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE");
        $this->execute("ALTER TABLE monitor_checks_partitioned ADD CONSTRAINT fk_mcp_monitor FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE");

        // 5. Copy data from old table to new
        $this->execute("
            INSERT INTO monitor_checks_partitioned
                (id, organization_id, monitor_id, region_id, status, response_time, status_code, error_message, details, checked_at, created)
            SELECT id, organization_id, monitor_id, region_id, status, response_time, status_code, error_message, details, checked_at, created
            FROM monitor_checks
        ");

        // 6. Update the sequence to continue after the highest existing id
        $this->execute("SELECT setval('monitor_checks_partitioned_id_seq', (SELECT COALESCE(MAX(id), 0) + 1 FROM monitor_checks_partitioned))");

        // 7. Swap tables: rename old table out, rename partitioned table in
        $this->execute("ALTER TABLE monitor_checks RENAME TO monitor_checks_old");
        // Drop the old sequence to avoid naming conflict when renaming
        $this->execute("DROP SEQUENCE IF EXISTS monitor_checks_id_seq");
        $this->execute("ALTER TABLE monitor_checks_partitioned RENAME TO monitor_checks");
        $this->execute("ALTER SEQUENCE IF EXISTS monitor_checks_partitioned_id_seq RENAME TO monitor_checks_id_seq");
    }

    /**
     * Down Method.
     *
     * Reverses the partitioning by swapping the old table back.
     *
     * @return void
     */
    public function down(): void
    {
        // Partitioning is PostgreSQL-only; skip on SQLite
        if ($this->getAdapter()->getAdapterType() !== 'pgsql') {
            return;
        }

        // Rename partitioned table away and restore the original
        $this->execute("ALTER SEQUENCE IF EXISTS monitor_checks_id_seq RENAME TO monitor_checks_partitioned_id_seq");
        $this->execute("ALTER TABLE monitor_checks RENAME TO monitor_checks_partitioned");
        $this->execute("ALTER TABLE monitor_checks_old RENAME TO monitor_checks");
        // Recreate the original sequence if needed
        $this->execute("DROP SEQUENCE IF EXISTS monitor_checks_id_seq");
        $this->execute("SELECT setval(pg_get_serial_sequence('monitor_checks', 'id'), (SELECT COALESCE(MAX(id), 0) + 1 FROM monitor_checks))");

        // Drop the partitioned table and all its partitions
        $this->execute("DROP TABLE IF EXISTS monitor_checks_partitioned CASCADE");
    }
}
