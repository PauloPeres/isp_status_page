<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateOrganizationUsers Migration
 *
 * Creates the organization_users join table linking users to organizations with roles.
 */
class CreateOrganizationUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('organization_users');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Foreign key to organizations table'
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'comment' => 'Foreign key to users table'
            ])
            ->addColumn('role', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'member',
                'comment' => 'User role: owner, admin, member, viewer'
            ])
            ->addColumn('invited_by', 'integer', [
                'null' => true,
                'default' => null,
                'comment' => 'User ID of the person who sent the invite'
            ])
            ->addColumn('invited_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'When the invitation was sent'
            ])
            ->addColumn('accepted_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'When the invitation was accepted'
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
            ->addIndex(['organization_id'], ['name' => 'idx_org_users_organization'])
            ->addIndex(['user_id'], ['name' => 'idx_org_users_user'])
            ->addIndex(['role'], ['name' => 'idx_org_users_role'])
            ->addIndex(['organization_id', 'user_id'], [
                'name' => 'idx_org_users_unique',
                'unique' => true,
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->create();
    }
}
