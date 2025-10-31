<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateSubscribers Migration
 *
 * Creates the subscribers table for users who want to receive status notifications.
 */
class CreateSubscribers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('subscribers');

        $table
            ->addColumn('email', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Subscriber email address'
            ])
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Subscriber name'
            ])
            ->addColumn('verification_token', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Email verification token'
            ])
            ->addColumn('verified', 'boolean', [
                'null' => false,
                'default' => false,
                'comment' => 'Is email verified'
            ])
            ->addColumn('verified_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Email verification timestamp'
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Is subscription active'
            ])
            ->addColumn('unsubscribe_token', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Unsubscribe token'
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
            ->addIndex(['email'], [
                'unique' => true,
                'name' => 'idx_subscribers_email'
            ])
            ->addIndex(['active', 'verified'], [
                'name' => 'idx_subscribers_active'
            ])
            ->create();
    }
}
