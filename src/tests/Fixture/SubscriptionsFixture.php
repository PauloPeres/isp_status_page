<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SubscriptionsFixture
 */
class SubscriptionsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'subscriber_id' => 1,
            'monitor_id' => 1,
            'notify_on_down' => true,
            'notify_on_up' => true,
            'notify_on_degraded' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'subscriber_id' => 1,
            'monitor_id' => 2,
            'notify_on_down' => true,
            'notify_on_up' => false,
            'notify_on_degraded' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 3,
            'subscriber_id' => 2,
            'monitor_id' => null, // Global subscription - all monitors
            'notify_on_down' => true,
            'notify_on_up' => true,
            'notify_on_degraded' => false,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 4,
            'subscriber_id' => 3,
            'monitor_id' => 1,
            'notify_on_down' => true,
            'notify_on_up' => true,
            'notify_on_degraded' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
