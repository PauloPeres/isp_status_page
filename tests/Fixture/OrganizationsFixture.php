<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * OrganizationsFixture
 */
class OrganizationsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'name' => 'Acme ISP',
            'slug' => 'acme-isp',
            'plan' => 'free',
            'stripe_customer_id' => null,
            'stripe_subscription_id' => null,
            'trial_ends_at' => null,
            'timezone' => 'UTC',
            'language' => 'en',
            'custom_domain' => null,
            'logo_url' => null,
            'settings' => null,
            'active' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'name' => 'Pro Networks',
            'slug' => 'pro-networks',
            'plan' => 'pro',
            'stripe_customer_id' => 'cus_abc123',
            'stripe_subscription_id' => 'sub_xyz789',
            'trial_ends_at' => '2026-12-31 23:59:59',
            'timezone' => 'America/Sao_Paulo',
            'language' => 'pt_BR',
            'custom_domain' => 'status.pronetworks.com',
            'logo_url' => 'https://pronetworks.com/logo.png',
            'settings' => '{"notifications_enabled":true,"theme":"dark"}',
            'active' => true,
            'created' => '2024-01-15 10:00:00',
            'modified' => '2024-06-01 14:30:00',
        ],
    ];
}
