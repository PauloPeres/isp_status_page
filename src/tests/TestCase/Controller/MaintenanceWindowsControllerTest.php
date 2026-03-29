<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class MaintenanceWindowsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Organizations', 'app.OrganizationUsers', 'app.Users', 'app.MaintenanceWindows', 'app.Monitors'];

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
        $this->get('/maintenance-windows');
        $this->assertRedirectContains('/app/maintenance');
    }

    public function testAddRedirectsToAngular(): void
    {
        $this->get('/maintenance-windows/add');
        $this->assertRedirectContains('/app/maintenance/new');
    }
}
