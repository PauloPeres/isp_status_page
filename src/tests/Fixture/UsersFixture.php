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
            'username' => 'admin',
            'password' => '$2y$10$u05j1qVkDruKvVaUxf6ruu.NpZKjJfPQqLvXNqj5pZKHNfKHjLqw2', // admin123
            'email' => 'admin@example.com',
            'name' => 'Administrator',
            'active' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'username' => 'user',
            'password' => '$2y$10$u05j1qVkDruKvVaUxf6ruu.NpZKjJfPQqLvXNqj5pZKHNfKHjLqw2', // admin123
            'email' => 'user@example.com',
            'name' => 'Regular User',
            'active' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 3,
            'username' => 'inactive',
            'password' => '$2y$10$u05j1qVkDruKvVaUxf6ruu.NpZKjJfPQqLvXNqj5pZKHNfKHjLqw2', // admin123
            'email' => 'inactive@example.com',
            'name' => 'Inactive User',
            'active' => false,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
