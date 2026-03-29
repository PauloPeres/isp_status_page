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
