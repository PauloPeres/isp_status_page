<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SecurityAuditLogsFixture
 */
class SecurityAuditLogsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'user_id' => 1,
            'event_type' => 'login',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'details' => '{}',
            'created' => '2024-01-01 00:00:00',
        ],
    ];
}
