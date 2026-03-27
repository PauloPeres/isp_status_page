<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateInvitations Migration
 *
 * Creates the invitations table for team invitation system (TASK-702).
 */
class CreateInvitations extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('invitations');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization this invitation belongs to',
            ])
            ->addColumn('email', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Email address of the invitee',
            ])
            ->addColumn('role', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'member',
                'comment' => 'Role to assign on acceptance (owner, admin, member, viewer)',
            ])
            ->addColumn('token', 'string', [
                'limit' => 64,
                'null' => false,
                'comment' => 'Unique invitation token for accept link',
            ])
            ->addColumn('invited_by', 'integer', [
                'null' => false,
                'comment' => 'User ID who sent the invitation',
            ])
            ->addColumn('accepted_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'When the invitation was accepted',
            ])
            ->addColumn('expires_at', 'datetime', [
                'null' => false,
                'comment' => 'When the invitation expires',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'comment' => 'When the invitation was created',
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
                'comment' => 'When the invitation was last modified',
            ])
            ->addIndex(['token'], [
                'name' => 'idx_invitations_token',
                'unique' => true,
            ])
            ->addIndex(['organization_id'], [
                'name' => 'idx_invitations_organization_id',
            ])
            ->addIndex(['email'], [
                'name' => 'idx_invitations_email',
            ])
            ->addIndex(['expires_at'], [
                'name' => 'idx_invitations_expires_at',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_invitations_organization_id',
            ])
            ->addForeignKey('invited_by', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_invitations_invited_by',
            ])
            ->create();
    }
}
