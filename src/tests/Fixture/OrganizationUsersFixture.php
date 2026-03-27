<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OrganizationUsersFixture
 */
class OrganizationUsersFixture extends TestFixture
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
            'user_id' => 1,
            'role' => 'owner',
            'invited_by' => null,
            'invited_at' => null,
            'accepted_at' => '2024-01-01 00:00:00',
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'organization_id' => 2,
            'user_id' => 1,
            'role' => 'admin',
            'invited_by' => null,
            'invited_at' => '2024-01-15 10:00:00',
            'accepted_at' => '2024-01-15 12:00:00',
            'created' => '2024-01-15 10:00:00',
            'modified' => '2024-01-15 12:00:00',
        ],
        [
            'id' => 3,
            'organization_id' => 1,
            'user_id' => 2,
            'role' => 'member',
            'invited_by' => 1,
            'invited_at' => '2024-02-01 09:00:00',
            'accepted_at' => '2024-02-01 11:00:00',
            'created' => '2024-02-01 09:00:00',
            'modified' => '2024-02-01 11:00:00',
        ],
        [
            'id' => 4,
            'organization_id' => 1,
            'user_id' => 3,
            'role' => 'viewer',
            'invited_by' => 1,
            'invited_at' => '2024-03-01 09:00:00',
            'accepted_at' => '2024-03-01 11:00:00',
            'created' => '2024-03-01 09:00:00',
            'modified' => '2024-03-01 11:00:00',
        ],
    ];
}
