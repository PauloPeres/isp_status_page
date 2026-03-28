<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * NotificationCreditsFixture
 */
class NotificationCreditsFixture extends TestFixture
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
            'balance' => 50,
            'monthly_grant' => 50,
            'auto_recharge' => false,
            'auto_recharge_threshold' => 10,
            'auto_recharge_amount' => 100,
            'low_balance_notified_at' => null,
            'last_grant_at' => null,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
