<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreatePlans Migration
 *
 * Creates the plans table for subscription plan configuration.
 */
class CreatePlans extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('plans');

        $table
            ->addColumn('name', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Plan display name',
            ])
            ->addColumn('slug', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => 'Unique plan identifier slug',
            ])
            ->addColumn('stripe_price_id_monthly', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Stripe price ID for monthly billing',
            ])
            ->addColumn('stripe_price_id_yearly', 'string', [
                'limit' => 255,
                'null' => true,
                'default' => null,
                'comment' => 'Stripe price ID for yearly billing',
            ])
            ->addColumn('price_monthly', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Monthly price in cents',
            ])
            ->addColumn('price_yearly', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Yearly price in cents',
            ])
            ->addColumn('monitor_limit', 'integer', [
                'null' => false,
                'default' => 1,
                'comment' => 'Maximum number of monitors (-1 = unlimited)',
            ])
            ->addColumn('check_interval_min', 'integer', [
                'null' => false,
                'default' => 300,
                'comment' => 'Minimum check interval in seconds',
            ])
            ->addColumn('team_member_limit', 'integer', [
                'null' => false,
                'default' => 1,
                'comment' => 'Maximum team members (-1 = unlimited)',
            ])
            ->addColumn('status_page_limit', 'integer', [
                'null' => false,
                'default' => 1,
                'comment' => 'Maximum status pages',
            ])
            ->addColumn('api_rate_limit', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'API requests per hour (0 = no API access)',
            ])
            ->addColumn('data_retention_days', 'integer', [
                'null' => false,
                'default' => 7,
                'comment' => 'Number of days to retain monitoring data',
            ])
            ->addColumn('features', 'text', [
                'null' => true,
                'default' => null,
                'comment' => 'JSON feature flags',
            ])
            ->addColumn('display_order', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => 'Display order on pricing page',
            ])
            ->addColumn('active', 'boolean', [
                'null' => false,
                'default' => true,
                'comment' => 'Is plan available for new subscriptions',
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
            ->addIndex(['slug'], [
                'name' => 'idx_plans_slug',
                'unique' => true,
            ])
            ->addIndex(['active'], [
                'name' => 'idx_plans_active',
            ])
            ->addIndex(['display_order'], [
                'name' => 'idx_plans_display_order',
            ])
            ->create();
    }
}
