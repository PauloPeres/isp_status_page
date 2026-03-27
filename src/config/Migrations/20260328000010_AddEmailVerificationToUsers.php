<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddEmailVerificationToUsers Migration
 *
 * Adds email verification fields to the users table for the public registration flow.
 */
class AddEmailVerificationToUsers extends AbstractMigration
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
            ->addColumn('email_verified', 'boolean', [
                'default' => false,
                'null' => false,
                'comment' => 'Whether the user has verified their email address',
            ])
            ->addColumn('email_verification_token', 'string', [
                'limit' => 64,
                'null' => true,
                'default' => null,
                'comment' => 'Token for email verification',
            ])
            ->addColumn('email_verification_sent_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'When the verification email was last sent',
            ])
            ->addIndex(['email_verification_token'], [
                'name' => 'idx_users_email_verification_token',
                'unique' => false,
            ])
            ->update();

        // Update existing users to set email_verified = true (they pre-date verification)
        $this->execute("UPDATE users SET email_verified = true WHERE email_verified = false");
    }
}
