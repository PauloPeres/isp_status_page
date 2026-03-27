<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * PlansFixture
 */
class PlansFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'name' => 'Free',
            'slug' => 'free',
            'stripe_price_id_monthly' => null,
            'stripe_price_id_yearly' => null,
            'price_monthly' => 0,
            'price_yearly' => 0,
            'monitor_limit' => 1,
            'check_interval_min' => 300,
            'team_member_limit' => 1,
            'status_page_limit' => 1,
            'api_rate_limit' => 0,
            'data_retention_days' => 7,
            'features' => '{"email_alerts":true}',
            'display_order' => 1,
            'active' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'name' => 'Pro',
            'slug' => 'pro',
            'stripe_price_id_monthly' => 'price_pro_monthly_123',
            'stripe_price_id_yearly' => 'price_pro_yearly_456',
            'price_monthly' => 1500,
            'price_yearly' => 14400,
            'monitor_limit' => 50,
            'check_interval_min' => 60,
            'team_member_limit' => 5,
            'status_page_limit' => 1,
            'api_rate_limit' => 1000,
            'data_retention_days' => 30,
            'features' => '{"email_alerts":true,"slack_alerts":true,"webhook_alerts":true,"ssl_monitoring":true,"api_access":true,"custom_status_page":true}',
            'display_order' => 2,
            'active' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 3,
            'name' => 'Business',
            'slug' => 'business',
            'stripe_price_id_monthly' => 'price_biz_monthly_789',
            'stripe_price_id_yearly' => 'price_biz_yearly_012',
            'price_monthly' => 4500,
            'price_yearly' => 43200,
            'monitor_limit' => -1,
            'check_interval_min' => 30,
            'team_member_limit' => -1,
            'status_page_limit' => 5,
            'api_rate_limit' => 10000,
            'data_retention_days' => 90,
            'features' => '{"email_alerts":true,"slack_alerts":true,"discord_alerts":true,"telegram_alerts":true,"webhook_alerts":true,"sms_alerts":true,"phone_alerts":true,"ssl_monitoring":true,"api_access":true,"custom_status_page":true,"custom_domain":true,"multi_region":true,"priority_support":true}',
            'display_order' => 3,
            'active' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
