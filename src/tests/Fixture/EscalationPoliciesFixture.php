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
            'public_id' => '5e6f7a8b-9c0d-4e1f-2a3b-4c5d6e7f8a9b',
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
            'public_id' => '6f7a8b9c-0d1e-4f2a-3b4c-5d6e7f8a9b0c',
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
