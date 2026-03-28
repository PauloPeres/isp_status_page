<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * IncidentUpdatesFixture
 */
class IncidentUpdatesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'incident_id' => 1,
            'organization_id' => 1,
            'user_id' => 1,
            'status' => 'investigating',
            'message' => 'We are investigating the issue.',
            'is_public' => true,
            'source' => 'web',
            'created' => '2024-01-01 10:05:00',
            'modified' => '2024-01-01 10:05:00',
        ],
        [
            'id' => 2,
            'incident_id' => 1,
            'organization_id' => 1,
            'user_id' => 1,
            'status' => 'resolved',
            'message' => 'The issue has been resolved.',
            'is_public' => true,
            'source' => 'web',
            'created' => '2024-01-01 11:00:00',
            'modified' => '2024-01-01 11:00:00',
        ],
    ];
}
