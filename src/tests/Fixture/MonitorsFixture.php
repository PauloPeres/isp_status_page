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
            'configuration' => '{"url":"https://example.com","expected_status_code":200}',
            'check_interval' => 30,
            'timeout' => 10,
            'retry_count' => 3,
            'status' => 'up',
            'last_check_at' => '2024-01-01 12:00:00',
            'next_check_at' => '2024-01-01 12:01:00',
            'uptime_percentage' => 99.9,
            'active' => true,
            'visible_on_status_page' => true,
            'display_order' => 1,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 12:00:00',
        ],
        [
            'id' => 2,
            'name' => 'API Server',
            'description' => 'API endpoint monitoring',
            'type' => 'http',
            'configuration' => '{"url":"https://api.example.com","expected_status_code":200}',
            'check_interval' => 30,
            'timeout' => 10,
            'retry_count' => 3,
            'status' => 'up',
            'last_check_at' => '2024-01-01 12:00:00',
            'next_check_at' => '2024-01-01 12:01:00',
            'uptime_percentage' => 99.5,
            'active' => true,
            'visible_on_status_page' => true,
            'display_order' => 2,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 12:00:00',
        ],
        [
            'id' => 3,
            'name' => 'Inactive Monitor',
            'description' => 'This monitor is inactive',
            'type' => 'http',
            'configuration' => '{"url":"https://inactive.example.com","expected_status_code":200}',
            'check_interval' => 30,
            'timeout' => 10,
            'retry_count' => 3,
            'status' => 'unknown',
            'last_check_at' => null,
            'next_check_at' => null,
            'uptime_percentage' => null,
            'active' => false,
            'visible_on_status_page' => true,
            'display_order' => 3,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
