<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * SettingsFixture
 */
class SettingsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'organization_id' => null,
            'key' => 'site_name',
            'value' => 'ISP Status',
            'type' => 'string',
            'description' => 'Site name',
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'organization_id' => null,
            'key' => 'site_language',
            'value' => 'en',
            'type' => 'string',
            'description' => 'Site language',
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 3,
            'organization_id' => null,
            'key' => 'enable_email_alerts',
            'value' => '1',
            'type' => 'boolean',
            'description' => 'Enable email alerts',
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
