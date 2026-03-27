<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
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
            'username' => 'admin',
            'password' => '$2y$10$oYBfA/7TTlJsi6aURb.UFuUJVBcM78buScLArOQNbI/dGjweiic6W', // admin123
            'email' => 'admin@example.com',
            'role' => 'admin',
            'active' => true,
            'last_login' => null,
            'force_password_change' => false,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'organization_id' => 1,
            'username' => 'user',
            'password' => '$2y$10$oYBfA/7TTlJsi6aURb.UFuUJVBcM78buScLArOQNbI/dGjweiic6W', // admin123
            'email' => 'user@example.com',
            'role' => 'user',
            'active' => true,
            'last_login' => null,
            'force_password_change' => false,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 3,
            'organization_id' => 1,
            'username' => 'inactive',
            'password' => '$2y$10$oYBfA/7TTlJsi6aURb.UFuUJVBcM78buScLArOQNbI/dGjweiic6W', // admin123
            'email' => 'inactive@example.com',
            'role' => 'user',
            'active' => false,
            'last_login' => null,
            'force_password_change' => false,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
