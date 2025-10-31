<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

/**
 * Users seed.
 *
 * Creates default admin user for first login.
 *
 * Default Credentials:
 * - Username: admin
 * - Password: admin123
 *
 * IMPORTANT: Change password after first login!
 */
class UsersSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * @return void
     */
    public function run(): void
    {
        // Hash password using password_hash (bcrypt)
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);

        $data = [
            [
                'username' => 'admin',
                'password' => $hashedPassword,
                'email' => 'admin@localhost',
                'role' => 'admin',
                'active' => 1,
                'last_login' => null,
                'created' => date('Y-m-d H:i:s'),
                'modified' => date('Y-m-d H:i:s'),
            ],
        ];

        $table = $this->table('users');
        $table->insert($data)->save();
    }
}
