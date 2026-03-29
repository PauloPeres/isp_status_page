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
