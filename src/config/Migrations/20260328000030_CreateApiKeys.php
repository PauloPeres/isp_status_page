<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateApiKeys Migration
 *
 * Creates the api_keys table for API key management.
 */
class CreateApiKeys extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('api_keys');

        $table
            ->addColumn('organization_id', 'integer', [
                'null' => false,
                'comment' => 'Organization this key belongs to',
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'comment' => 'User who created the key',
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Human-readable key name',
            ])
            ->addColumn('key_hash', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Bcrypt hash of the API key',
            ])
            ->addColumn('key_prefix', 'string', [
                'limit' => 10,
                'null' => false,
                'comment' => 'First 12 chars of key for lookup',
            ])
            ->addColumn('permissions', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON array of permissions: read, write, admin',
            ])
            ->addColumn('rate_limit', 'integer', [
                'null' => false,
                'default' => 1000,
                'comment' => 'Rate limit per hour',
            ])
            ->addColumn('last_used_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Last time the key was used',
            ])
            ->addColumn('expires_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Key expiration date',
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Whether the key is active',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp',
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Last modification timestamp',
            ])
            ->addForeignKey('organization_id', 'organizations', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_api_keys_organization_id',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'NO_ACTION',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_api_keys_user_id',
            ])
            ->addIndex(['key_prefix'], [
                'name' => 'idx_api_keys_key_prefix',
            ])
            ->addIndex(['organization_id'], [
                'name' => 'idx_api_keys_organization_id',
            ])
            ->addIndex(['active'], [
                'name' => 'idx_api_keys_active',
            ])
            ->create();
    }
}
