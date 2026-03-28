<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Add2faToUsers Migration
 *
 * Adds two-factor authentication columns to the users table.
 * Part of TASK-AUTH-MFA.
 */
class Add2faToUsers extends AbstractMigration
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
            ->addColumn('two_factor_secret', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'TOTP secret key (base32 encoded)',
            ])
            ->addColumn('two_factor_enabled', 'boolean', [
                'null' => false,
                'default' => false,
                'comment' => 'Whether 2FA is enabled for this user',
            ])
            ->addColumn('two_factor_recovery_codes', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON array of hashed recovery codes',
            ])
            ->update();
    }
}
