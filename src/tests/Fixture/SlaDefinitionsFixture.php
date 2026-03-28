<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SlaDefinitionsFixture
 */
class SlaDefinitionsFixture extends TestFixture
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
            'monitor_id' => 1,
            'name' => 'Website SLA',
            'target_uptime' => 99.9,
            'measurement_period' => 'monthly',
            'breach_notification' => true,
            'warning_threshold' => 99.95,
            'active' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'organization_id' => 1,
            'monitor_id' => 2,
            'name' => 'API SLA',
            'target_uptime' => 99.5,
            'measurement_period' => 'quarterly',
            'breach_notification' => false,
            'warning_threshold' => null,
            'active' => true,
            'created' => '2024-01-15 00:00:00',
            'modified' => '2024-01-15 00:00:00',
        ],
    ];
}
