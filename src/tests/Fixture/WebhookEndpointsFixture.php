<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * WebhookEndpointsFixture
 */
class WebhookEndpointsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'public_id' => '7a8b9c0d-1e2f-4a3b-4c5d-6e7f8a9b0c1d',
            'organization_id' => 1,
            'url' => 'https://hooks.example.com/webhook',
            'secret' => 'test-webhook-secret-123',
            'events' => '["monitor.down","monitor.up","incident.created"]',
            'active' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
