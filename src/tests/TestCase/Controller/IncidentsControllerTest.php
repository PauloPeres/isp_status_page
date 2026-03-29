<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class IncidentsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
        'app.AlertRules',
        'app.AlertLogs',
        'app.IncidentUpdates',
        'app.Settings',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true, 'organization_id' => 1],
            'current_organization_id' => 1,
        ]);
    }

    public function testIndexRedirectsToAngular(): void
    {
        $this->get('/incidents');
        $this->assertRedirectContains('/app/incidents');
    }

    public function testViewRedirectsToAngular(): void
    {
        $this->get('/incidents/view/1');
        $this->assertRedirectContains('/app/incidents/1');
    }

    public function testAddRedirectsToAngular(): void
    {
        $this->get('/incidents/add');
        $this->assertRedirectContains('/app/incidents/new');
    }

    public function testAcknowledgePublicWithInvalidToken(): void
    {
        $this->get('/incidents/acknowledge/1/0000000000000000000000000000000000000000000000000000000000000000');
        $this->assertResponseOk();
    }
}
