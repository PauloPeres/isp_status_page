<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * NotificationCreditTransactionsFixture
 */
class NotificationCreditTransactionsFixture extends TestFixture
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
            'type' => 'purchase',
            'amount' => 100,
            'balance_after' => 100,
            'channel' => null,
            'description' => 'Initial credit purchase',
            'reference_id' => null,
            'created' => '2024-01-01 00:00:00',
        ],
    ];
}
