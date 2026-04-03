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
            'public_id' => 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d',
            'organization_id' => 1,
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
            'public_id' => 'b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e',
            'organization_id' => 1,
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
            'public_id' => 'c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f',
            'organization_id' => 1,
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
