<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class ReportsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Organizations', 'app.OrganizationUsers', 'app.Users', 'app.Monitors', 'app.MonitorChecks', 'app.Incidents'];

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
        $this->get('/reports');
        $this->assertRedirectContains('/app/reports');
    }
}
