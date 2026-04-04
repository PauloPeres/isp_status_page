<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\I18n\DateTime;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * AlertRulesFixture
 */
class AlertRulesFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $yesterday = new DateTime('-1 day');

        $this->records = [
            [
                'id' => 1,
                'public_id' => 'a1e2f3b4-c5d6-4e7f-8a9b-0c1d2e3f4a5b',
                'organization_id' => 1,
                'monitor_id' => 1,
                'channel' => 'email',
                'trigger_on' => 'on_down',
                'throttle_minutes' => 5,
                'recipients' => '["admin@example.com","ops@example.com"]',
                'template' => null,
                'active' => true,
                'created' => $yesterday->format('Y-m-d H:i:s'),
                'modified' => $yesterday->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 2,
                'public_id' => 'b2f3a4c5-d6e7-4f8a-9b0c-1d2e3f4a5b6c',
                'organization_id' => 1,
                'monitor_id' => 1,
                'channel' => 'email',
                'trigger_on' => 'on_up',
                'throttle_minutes' => 0,
                'recipients' => '["admin@example.com"]',
                'template' => null,
                'active' => true,
                'created' => $yesterday->format('Y-m-d H:i:s'),
                'modified' => $yesterday->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 3,
                'public_id' => 'c3a4b5d6-e7f8-4a9b-0c1d-2e3f4a5b6c7d',
                'organization_id' => 1,
                'monitor_id' => 2,
                'channel' => 'email',
                'trigger_on' => 'on_change',
                'throttle_minutes' => 10,
                'recipients' => '["api-team@example.com"]',
                'template' => 'Custom template for API alerts',
                'active' => true,
                'created' => $yesterday->format('Y-m-d H:i:s'),
                'modified' => $yesterday->format('Y-m-d H:i:s'),
            ],
            [
                'id' => 4,
                'public_id' => 'd4b5c6e7-f8a9-4b0c-1d2e-3f4a5b6c7d8e',
                'organization_id' => 1,
                'monitor_id' => 1,
                'channel' => 'whatsapp',
                'trigger_on' => 'on_down',
                'throttle_minutes' => 15,
                'recipients' => '["+5511999999999"]',
                'template' => null,
                'active' => false,
                'created' => $yesterday->format('Y-m-d H:i:s'),
                'modified' => $yesterday->format('Y-m-d H:i:s'),
            ],
        ];

        parent::init();
    }
}
