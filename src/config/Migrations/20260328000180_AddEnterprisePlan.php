<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddEnterprisePlan Migration
 *
 * Inserts the Enterprise plan into the plans table.
 */
class AddEnterprisePlan extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        $this->table('plans')->insert([
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'stripe_price_id_monthly' => null,
                'stripe_price_id_yearly' => null,
                'price_monthly' => 0,
                'price_yearly' => 0,
                'monitor_limit' => -1,
                'check_interval_min' => 15,
                'team_member_limit' => -1,
                'status_page_limit' => -1,
                'api_rate_limit' => 50000,
                'data_retention_days' => 365,
                'features' => json_encode([
                    'email_alerts' => true,
                    'slack_alerts' => true,
                    'discord_alerts' => true,
                    'telegram_alerts' => true,
                    'webhook_alerts' => true,
                    'sms_alerts' => true,
                    'phone_alerts' => true,
                    'ssl_monitoring' => true,
                    'api_access' => true,
                    'custom_status_page' => true,
                    'custom_domain' => true,
                    'multi_region' => true,
                    'priority_support' => true,
                    'sla_tracking' => true,
                    'dedicated_support' => true,
                    'sso_saml' => true,
                ]),
                'display_order' => 4,
                'active' => true,
                'created' => $now,
                'modified' => $now,
            ],
        ])->save();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute("DELETE FROM plans WHERE slug = 'enterprise'");
    }
}
