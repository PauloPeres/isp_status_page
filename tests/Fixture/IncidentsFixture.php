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
