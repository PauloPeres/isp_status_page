<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * IncidentsFixture
 */
class IncidentsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'public_id' => 'd4e5f6a7-b8c9-4d0e-1f2a-3b4c5d6e7f80',
            'organization_id' => 1,
            'monitor_id' => 1,
            'title' => 'Website Downtime',
            'description' => 'Main website is not responding',
            'status' => 'resolved',
            'severity' => 'major',
            'started_at' => '2024-01-01 10:00:00',
            'identified_at' => '2024-01-01 10:05:00',
            'resolved_at' => '2024-01-01 11:00:00',
            'duration' => 3600,
            'auto_created' => true,
            'created' => '2024-01-01 10:00:00',
            'modified' => '2024-01-01 11:00:00',
        ],
        [
            'id' => 2,
            'public_id' => 'e5f6a7b8-c9d0-4e1f-2a3b-4c5d6e7f8091',
            'organization_id' => 1,
            'monitor_id' => 2,
            'title' => 'API Slow Response',
            'description' => 'API is responding slowly',
            'status' => 'investigating',
            'severity' => 'minor',
            'started_at' => '2024-01-01 11:30:00',
            'identified_at' => '2024-01-01 11:35:00',
            'resolved_at' => null,
            'duration' => null,
            'auto_created' => true,
            'created' => '2024-01-01 11:30:00',
            'modified' => '2024-01-01 11:35:00',
        ],
    ];
}
