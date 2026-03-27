<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreateOrganizations Migration
 *
 * Creates the organizations table for multi-tenant support.
 */
class CreateOrganizations extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('organizations');

        $table
            ->addColumn('name', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => 'Organization name'
            ])
            ->addColumn('slug', 'string', [
                'limit' => 100,
                'null' => false,
                'comment' => 'Unique subdomain slug'
            ])
            ->addColumn('plan', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => 'free',
                'comment' => 'Subscription plan: free, pro, business'
            ])
            ->addColumn('stripe_customer_id', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Stripe customer ID'
            ])
            ->addColumn('stripe_subscription_id', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Stripe subscription ID'
            ])
            ->addColumn('trial_ends_at', 'datetime', [
                'null' => true,
                'default' => null,
                'comment' => 'Trial period end timestamp'
            ])
            ->addColumn('timezone', 'string', [
                'limit' => 50,
                'null' => false,
                'default' => 'UTC',
                'comment' => 'Organization timezone'
            ])
            ->addColumn('language', 'string', [
                'limit' => 10,
                'null' => false,
                'default' => 'en',
                'comment' => 'Default language'
            ])
            ->addColumn('custom_domain', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Custom domain for status page'
            ])
            ->addColumn('logo_url', 'string', [
                'limit' => 500,
                'null' => true,
                'default' => null,
                'comment' => 'Organization logo URL'
            ])
            ->addColumn('settings', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON settings for the organization'
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Is organization active'
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
            ->addIndex(['slug'], [
                'name' => 'idx_organizations_slug',
                'unique' => true,
            ])
            ->addIndex(['stripe_customer_id'], [
                'name' => 'idx_organizations_stripe_customer',
            ])
            ->addIndex(['custom_domain'], [
                'name' => 'idx_organizations_custom_domain',
            ])
            ->create();
    }
}
