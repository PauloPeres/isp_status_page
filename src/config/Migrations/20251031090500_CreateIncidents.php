<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateIncidents Migration
 *
 * Creates the incidents table which tracks service outages and incidents.
 */
class CreateIncidents extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('incidents');

        $table
            ->addColumn('monitor_id', 'integer', [
                'null' => false,
                'comment' => 'Foreign key to monitors table'
            ])
            ->addColumn('title', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Incident title'
            ])
            ->addColumn('description', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'Incident description'
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'Status: investigating, identified, monitoring, resolved'
            ])
            ->addColumn('severity', 'string', [
                'limit' => 20,
                'null' => false,
                'comment' => 'Severity: critical, major, minor, maintenance'
            ])
            ->addColumn('started_at', 'datetime', [
                'null' => false,
                'comment' => 'When the incident started'
            ])
            ->addColumn('identified_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'When the incident was identified'
            ])
            ->addColumn('resolved_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'When the incident was resolved'
            ])
            ->addColumn('duration', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'Incident duration in seconds (calculated)'
            ])
            ->addColumn('auto_created', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Was this incident auto-created by the system'
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp'
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Last modification timestamp'
            ])
            ->addIndex(['monitor_id'], ['name' => 'idx_incidents_monitor'])
            ->addIndex(['status'], ['name' => 'idx_incidents_status'])
            ->addIndex(['started_at'], ['name' => 'idx_incidents_started'])
            ->addForeignKey('monitor_id', 'monitors', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->create();
    }
}
