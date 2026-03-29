<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateRefreshTokens Migration
 *
 * Creates the refresh_tokens table for JWT refresh token storage.
 * Tokens are stored as SHA-256 hashes; plain tokens are never persisted.
 */
class CreateRefreshTokens extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('refresh_tokens');

        $table
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'comment' => 'User this refresh token belongs to',
            ])
            ->addColumn('token_hash', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'SHA-256 hash of the plain refresh token',
            ])
            ->addColumn('expires_at', 'datetime', [
                'null' => false,
                'comment' => 'Token expiration timestamp',
            ])
            ->addColumn('revoked_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'When the token was revoked (null = active)',
            ])
            ->addColumn('ip_address', 'string', [
                'limit' => 45,
                'null' => true,
                'default' => null,
                'comment' => 'IP address of the client that created this token',
            ])
            ->addColumn('user_agent', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'User-Agent header of the client',
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Creation timestamp',
            ])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
                'constraint' => 'fk_refresh_tokens_user_id',
            ])
            ->addIndex(['token_hash'], [
                'name' => 'idx_refresh_tokens_token_hash',
            ])
            ->addIndex(['user_id', 'created'], [
                'name' => 'idx_refresh_tokens_user_created',
                'order' => ['created' => 'DESC'],
            ])
            ->create();
    }
}
