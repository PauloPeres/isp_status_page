<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * EscalationPoliciesFixture
 */
class EscalationPoliciesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'organization_id' => 1,
            'name' => 'Critical Alert Escalation',
            'description' => 'Escalation for critical service outages',
            'repeat_enabled' => false,
            'repeat_after_minutes' => 30,
            'active' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'organization_id' => 1,
            'name' => 'Inactive Policy',
            'description' => 'This policy is inactive',
            'repeat_enabled' => false,
            'repeat_after_minutes' => 60,
            'active' => false,
            'created' => '2024-01-15 00:00:00',
            'modified' => '2024-01-15 00:00:00',
        ],
    ];
}
