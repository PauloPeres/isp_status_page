<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ScheduledReportsFixture
 */
class ScheduledReportsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'public_id' => '3c4d5e6f-7a8b-4c9d-0e1f-2a3b4c5d6e7f',
            'organization_id' => 1,
            'name' => 'Weekly Status Report',
            'frequency' => 'weekly',
            'recipients' => '["admin@example.com","ops@example.com"]',
            'include_uptime' => true,
            'include_response_time' => true,
            'include_incidents' => true,
            'include_sla' => false,
            'active' => true,
            'next_send_at' => '2026-04-01 08:00:00',
            'last_sent_at' => null,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'public_id' => '4d5e6f7a-8b9c-4d0e-1f2a-3b4c5d6e7f8a',
            'organization_id' => 1,
            'name' => 'Monthly Summary',
            'frequency' => 'monthly',
            'recipients' => '["manager@example.com"]',
            'include_uptime' => true,
            'include_response_time' => false,
            'include_incidents' => true,
            'include_sla' => true,
            'active' => true,
            'next_send_at' => '2026-04-01 08:00:00',
            'last_sent_at' => null,
            'created' => '2024-01-15 00:00:00',
            'modified' => '2024-01-15 00:00:00',
        ],
    ];
}
