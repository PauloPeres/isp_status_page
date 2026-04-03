<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * StatusPagesFixture
 */
class StatusPagesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array<array>
     */
    public array $records = [
        [
            'id' => 1,
            'public_id' => 'b4c5d6e7-f809-4102-1324-354657687980',
            'organization_id' => 1,
            'name' => 'Acme Status',
            'slug' => 'acme-status',
            'description' => 'Status page for Acme ISP',
            'monitors' => '[1,2]',
            'theme' => '{"primary_color":"#1E88E5"}',
            'custom_domain' => null,
            'password' => null,
            'show_incident_history' => true,
            'active' => true,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
        [
            'id' => 2,
            'public_id' => 'c5d6e7f8-0910-4213-2435-465768798091',
            'organization_id' => 1,
            'name' => 'Inactive Page',
            'slug' => 'inactive-page',
            'description' => 'Inactive status page',
            'monitors' => '[1]',
            'theme' => '{}',
            'custom_domain' => null,
            'password' => null,
            'show_incident_history' => false,
            'active' => false,
            'created' => '2024-01-01 00:00:00',
            'modified' => '2024-01-01 00:00:00',
        ],
    ];
}
