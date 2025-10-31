<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SubscribersFixture
 */
class SubscribersFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'email' => 'subscriber1@example.com',
            'name' => 'Test Subscriber 1',
            'active' => true,
            'verified' => true,
            'verified_at' => '2024-01-01 00:00:00',
            'verification_token' => null,
            'unsubscribe_token' => 'unsubscribe_token_1',
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'email' => 'subscriber2@example.com',
            'name' => 'Test Subscriber 2',
            'active' => true,
            'verified' => true,
            'verified_at' => '2024-01-01 00:00:00',
            'verification_token' => null,
            'unsubscribe_token' => 'unsubscribe_token_2',
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 3,
            'email' => 'inactive@example.com',
            'name' => null,
            'active' => false,
            'verified' => true,
            'verified_at' => '2024-01-01 00:00:00',
            'verification_token' => null,
            'unsubscribe_token' => 'unsubscribe_token_3',
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 4,
            'email' => 'unverified@example.com',
            'name' => 'Unverified User',
            'active' => true,
            'verified' => false,
            'verified_at' => null,
            'verification_token' => 'verification_token_abc123',
            'unsubscribe_token' => 'unsubscribe_token_4',
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
