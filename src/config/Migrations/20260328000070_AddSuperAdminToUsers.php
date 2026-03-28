<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddSuperAdminToUsers extends AbstractMigration
{
    /**
     * Up Method.
     *
     * Add is_super_admin boolean column to users table.
     */
    public function up(): void
    {
        $this->table('users')
            ->addColumn('is_super_admin', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'role',
            ])
            ->addIndex(['is_super_admin'], ['name' => 'idx_users_is_super_admin'])
            ->update();

        // Set the first admin user (id=1) as super admin
        $this->execute("UPDATE users SET is_super_admin = true WHERE id = 1");
    }

    /**
     * Down Method.
     *
     * Remove is_super_admin column from users table.
     */
    public function down(): void
    {
        $this->table('users')
            ->removeIndex(['is_super_admin'])
            ->removeColumn('is_super_admin')
            ->update();
    }
}
