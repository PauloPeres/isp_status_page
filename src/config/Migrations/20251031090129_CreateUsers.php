<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateUsers Migration
 *
 * Creates the users table for authentication and authorization.
 * This table stores admin and user accounts for the admin panel.
 */
class CreateUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');

        $table
            ->addColumn('username', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Username for login'
            ])
            ->addColumn('password', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Bcrypt hashed password'
            ])
            ->addColumn('email', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'User email address'
            ])
            ->addColumn('role', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'user',
                'comment' => 'User role: admin, user, viewer'
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Is user account active'
            ])
            ->addColumn('last_login', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Last login timestamp'
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp'
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Last modification timestamp'
            ])
            ->addIndex(['username'], [
                'unique' => true,
                'name' => 'idx_users_username'
            ])
            ->addIndex(['email'], [
                'unique' => true,
                'name' => 'idx_users_email'
            ])
            ->addIndex(['active'], [
                'name' => 'idx_users_active'
            ])
            ->create();
    }
}
