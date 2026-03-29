<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Fix SLA decimal precision — DECIMAL(5,3) can't hold 100.000
 */
class FixSlaDecimalPrecision extends AbstractMigration
{
    public function up(): void
    {
        // Fix sla_definitions
        $table = $this->table('sla_definitions');
        $table->changeColumn('target_uptime', 'decimal', [
            'precision' => 6,
            'scale' => 3,
            'null' => false,
            'default' => '99.900',
        ]);
        $table->changeColumn('warning_threshold', 'decimal', [
            'precision' => 6,
            'scale' => 3,
            'null' => true,
            'default' => '99.950',
        ]);
        $table->update();

        // Fix sla_reports
        $table2 = $this->table('sla_reports');
        $table2->changeColumn('target_uptime', 'decimal', [
            'precision' => 6,
            'scale' => 3,
            'null' => false,
        ]);
        $table2->changeColumn('actual_uptime', 'decimal', [
            'precision' => 6,
            'scale' => 3,
            'null' => false,
        ]);
        $table2->update();
    }

    public function down(): void
    {
        // Revert to original precision
        $table = $this->table('sla_definitions');
        $table->changeColumn('target_uptime', 'decimal', ['precision' => 5, 'scale' => 3, 'null' => false]);
        $table->changeColumn('warning_threshold', 'decimal', ['precision' => 5, 'scale' => 3, 'null' => true]);
        $table->update();

        $table2 = $this->table('sla_reports');
        $table2->changeColumn('target_uptime', 'decimal', ['precision' => 5, 'scale' => 3, 'null' => false]);
        $table2->changeColumn('actual_uptime', 'decimal', ['precision' => 5, 'scale' => 3, 'null' => false]);
        $table2->update();
    }
}
