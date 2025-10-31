<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MonitorsFixture
 */
class MonitorsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'name' => 'Website',
            'description' => 'Main website monitoring',
            'type' => 'http',
            'target' => 'https://example.com',
            'interval' => 30,
            'timeout' => 10,
            'expected_status_code' => 200,
            'status' => 'up',
            'active' => true,
            'last_check' => '2024-01-01 12:00:00',
            'response_time' => 150,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 12:00:00',
        ],
        [
            'id' => 2,
            'name' => 'API Server',
            'description' => 'API endpoint monitoring',
            'type' => 'http',
            'target' => 'https://api.example.com',
            'interval' => 30,
            'timeout' => 10,
            'expected_status_code' => 200,
            'status' => 'up',
            'active' => true,
            'last_check' => '2024-01-01 12:00:00',
            'response_time' => 200,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 12:00:00',
        ],
        [
            'id' => 3,
            'name' => 'Inactive Monitor',
            'description' => 'This monitor is inactive',
            'type' => 'http',
            'target' => 'https://inactive.example.com',
            'interval' => 30,
            'timeout' => 10,
            'expected_status_code' => 200,
            'status' => 'unknown',
            'active' => false,
            'last_check' => null,
            'response_time' => null,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
