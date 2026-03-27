<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InvitationsFixture
 */
class InvitationsFixture extends TestFixture
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
            'email' => 'newmember@example.com',
            'role' => 'member',
            'token' => 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2',
            'invited_by' => 1,
            'accepted_at' => null,
            'expires_at' => '2027-12-31 23:59:59',
            'created' => '2026-03-01 10:00:00',
            'modified' => '2026-03-01 10:00:00',
        ],
        [
            'id' => 2,
            'organization_id' => 1,
            'email' => 'accepted@example.com',
            'role' => 'admin',
            'token' => 'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3',
            'invited_by' => 1,
            'accepted_at' => '2026-03-02 14:00:00',
            'expires_at' => '2027-12-31 23:59:59',
            'created' => '2026-03-01 10:00:00',
            'modified' => '2026-03-02 14:00:00',
        ],
        [
            'id' => 3,
            'organization_id' => 1,
            'email' => 'expired@example.com',
            'role' => 'viewer',
            'token' => 'c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4',
            'invited_by' => 1,
            'accepted_at' => null,
            'expires_at' => '2020-01-01 00:00:00',
            'created' => '2019-12-25 10:00:00',
            'modified' => '2019-12-25 10:00:00',
        ],
        [
            'id' => 4,
            'organization_id' => 2,
            'email' => 'otherorg@example.com',
            'role' => 'member',
            'token' => 'd4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5',
            'invited_by' => 1,
            'accepted_at' => null,
            'expires_at' => '2027-12-31 23:59:59',
            'created' => '2026-03-01 10:00:00',
            'modified' => '2026-03-01 10:00:00',
        ],
    ];
}
