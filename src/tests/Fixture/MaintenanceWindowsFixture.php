<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MaintenanceWindowsFixture
 */
class MaintenanceWindowsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'public_id' => 'd6e7f809-1021-4324-3546-576879809102',
            'organization_id' => 1,
            'title' => 'Scheduled Maintenance',
            'description' => 'Routine server maintenance',
            'status' => 'scheduled',
            'starts_at' => '2026-12-01 02:00:00',
            'ends_at' => '2026-12-01 04:00:00',
            'auto_resolve_incidents' => false,
            'suppress_alerts' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
