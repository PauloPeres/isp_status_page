<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * IntegrationsFixture
 */
class IntegrationsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'public_id' => 'e5c6d7f8-a9b0-4c1d-2e3f-4a5b6c7d8e9f',
            'organization_id' => 1,
            'name' => 'Test REST API',
            'type' => 'rest_api',
            'configuration' => '{"base_url":"https://api.example.com","method":"GET","timeout":10}',
            'active' => true,
            'last_sync_at' => null,
            'last_sync_status' => null,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'public_id' => 'f6d7e8a9-b0c1-4d2e-3f4a-5b6c7d8e9f0a',
            'organization_id' => 1,
            'name' => 'Test IXC',
            'type' => 'ixc',
            'configuration' => '{"base_url":"https://ixc.example.com","username":"admin","password":"secret"}',
            'active' => false,
            'last_sync_at' => null,
            'last_sync_status' => null,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
