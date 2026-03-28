<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SlaReportsFixture
 */
class SlaReportsFixture extends TestFixture
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
            'sla_definition_id' => 1,
            'monitor_id' => 1,
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'period_type' => 'monthly',
            'target_uptime' => 99.9,
            'actual_uptime' => 99.95,
            'total_minutes' => 44640,
            'downtime_minutes' => 22.32,
            'allowed_downtime_minutes' => 44.64,
            'remaining_downtime_minutes' => 22.32,
            'status' => 'compliant',
            'incidents_count' => 1,
            'created' => '2024-01-31 23:59:59',
            'modified' => '2024-01-31 23:59:59',
        ],
    ];
}
