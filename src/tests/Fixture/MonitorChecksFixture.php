<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MonitorChecksFixture
 */
class MonitorChecksFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'organization_id' => 1,
            'monitor_id' => 1,
            'status' => 'success',
            'response_time' => 150,
            'status_code' => 200,
            'error_message' => null,
            'checked_at' => '2024-01-01 12:00:00',
            'created' => '2024-01-01 12:00:00',
        ],
        [
            'id' => 2,
            'organization_id' => 1,
            'monitor_id' => 1,
            'status' => 'success',
            'response_time' => 145,
            'status_code' => 200,
            'error_message' => null,
            'checked_at' => '2024-01-01 12:00:30',
            'created' => '2024-01-01 12:00:30',
        ],
        [
            'id' => 3,
            'organization_id' => 1,
            'monitor_id' => 2,
            'status' => 'success',
            'response_time' => 200,
            'status_code' => 200,
            'error_message' => null,
            'checked_at' => '2024-01-01 12:00:00',
            'created' => '2024-01-01 12:00:00',
        ],
    ];
}
