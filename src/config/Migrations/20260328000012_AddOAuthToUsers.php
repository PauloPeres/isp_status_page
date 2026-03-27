<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddOAuthToUsers Migration
 *
 * Adds OAuth/social login fields to the users table (TASK-704).
 */
class AddOAuthToUsers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('users');

        $table
            ->addColumn('oauth_provider', 'string', [
                'limit' => 50,
                'null' => true,
                'default' => null,
                'comment' => 'OAuth provider name (google, github)',
            ])
            ->addColumn('oauth_id', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'OAuth provider user ID',
            ])
            ->addIndex(['oauth_provider', 'oauth_id'], [
                'name' => 'idx_users_oauth_provider_id',
                'unique' => true,
            ])
            ->update();
    }
}
