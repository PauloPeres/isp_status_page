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
            'active' => true,
            'verified' => true,
            'verification_token' => null,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'email' => 'subscriber2@example.com',
            'active' => true,
            'verified' => true,
            'verification_token' => null,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 3,
            'email' => 'inactive@example.com',
            'active' => false,
            'verified' => true,
            'verification_token' => null,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
