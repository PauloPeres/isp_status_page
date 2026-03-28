<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * EscalationStepsFixture
 */
class EscalationStepsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'escalation_policy_id' => 1,
            'step_number' => 1,
            'wait_minutes' => 0,
            'channel' => 'email',
            'recipients' => '["oncall@example.com"]',
            'message_template' => null,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'escalation_policy_id' => 1,
            'step_number' => 2,
            'wait_minutes' => 15,
            'channel' => 'sms',
            'recipients' => '["+5511999999999"]',
            'message_template' => null,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
