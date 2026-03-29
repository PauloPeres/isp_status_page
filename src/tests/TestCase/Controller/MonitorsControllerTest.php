<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\MonitorsController Test Case
 *
 * Tests that legacy admin routes redirect to the Angular SPA.
 */
class MonitorsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
        'app.Plans',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true],
            'current_organization_id' => 1,
        ]);
    }

    public function testIndexRequiresAuthentication(): void
    {
        $this->_session = [];
        $this->get('/monitors');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexRedirectsToAngular(): void
    {
        $this->get('/monitors');
        $this->assertRedirectContains('/app/monitors');
    }

    public function testViewRedirectsToAngular(): void
    {
        $this->get('/monitors/view/1');
        $this->assertRedirectContains('/app/monitors/1');
    }

    public function testAddRedirectsToAngular(): void
    {
        $this->get('/monitors/add');
        $this->assertRedirectContains('/app/monitors/new');
    }

    public function testEditRedirectsToAngular(): void
    {
        $this->get('/monitors/edit/1');
        $this->assertRedirectContains('/app/monitors/1/edit');
    }

    public function testDeleteRedirectsToAngular(): void
    {
        $this->get('/monitors/delete/1');
        $this->assertRedirectContains('/app/monitors');
    }

    public function testToggleRedirectsToAngular(): void
    {
        $this->get('/monitors/toggle/1');
        $this->assertRedirectContains('/app/monitors');
    }
}
