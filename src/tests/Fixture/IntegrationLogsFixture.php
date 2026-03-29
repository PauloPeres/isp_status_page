<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * IntegrationLogsFixture
 */
class IntegrationLogsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'integration_id' => 1,
            'action' => 'test_connection',
            'status' => 'success',
            'message' => 'Connection successful',
            'details' => '{"success":true}',
            'created' => '2024-01-01 00:00:00',
        ],
    ];
}
