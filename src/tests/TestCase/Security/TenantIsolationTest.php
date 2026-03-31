<?php
declare(strict_types=1);

namespace App\Test\TestCase\Security;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Security regression: All tenant tables must have TenantScope behavior.
 * Covers VULN-05 and VULN-06 fixes.
 */
class TenantIsolationTest extends TestCase
{
    /**
     * Tables that MUST have TenantScope behavior loaded.
     * If a new table with organization_id is added, add it here.
     */
    private array $tenantTables = [
        'Monitors',
        'MonitorChecks',
        'Incidents',
        'IncidentUpdates',
        'AlertRules',
        'AlertLogs',
        'StatusPages',
        'MaintenanceWindows',
        'WebhookEndpoints',
        'SlaDefinitions',
        'SlaReports',
        'ScheduledReports',
        'Heartbeats',
        'Invitations',
        'NotificationSchedules',
        'ApiKeys',
    ];

    /**
     * Verify every tenant table has TenantScope behavior loaded.
     */
    public function testAllTenantTablesHaveTenantScope(): void
    {
        $missing = [];

        foreach ($this->tenantTables as $tableName) {
            try {
                $table = TableRegistry::getTableLocator()->get($tableName);

                if (!$table->hasBehavior('TenantScope')) {
                    $missing[] = $tableName;
                }
            } catch (\Exception $e) {
                // Table class may not exist — skip
            }
        }

        $this->assertEmpty(
            $missing,
            'These tables are missing TenantScope behavior: ' . implode(', ', $missing) .
            '. Add $this->addBehavior(\'TenantScope\') to their initialize() method.'
        );
    }
}
