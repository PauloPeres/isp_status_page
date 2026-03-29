<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\MaintenanceWindowsController Test Case
 *
 * @uses \App\Controller\MaintenanceWindowsController
 */
class MaintenanceWindowsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.MaintenanceWindows',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
                'organization_id' => 1,
            ],
            'current_organization_id' => 1,
        ]);
    }

    public function testIndexRequiresAuth(): void
    {
        $this->_session = [];
        $this->cookie('csrfToken', '');

        $this->get('/maintenance-windows');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexLoadsForAuthenticatedUser(): void
    {
        $this->get('/maintenance-windows');
        $this->assertResponseOk();
    }

    public function testIndexSetsMaintenanceWindowsVariable(): void
    {
        $this->get('/maintenance-windows');
        $this->assertResponseOk();

        $maintenanceWindows = $this->viewVariable('maintenanceWindows');
        $this->assertNotNull($maintenanceWindows);
    }

    public function testAddFormLoads(): void
    {
        $this->get('/maintenance-windows/add');
        $this->assertResponseOk();
    }

    public function testAddPostCreatesMaintenanceWindow(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'title' => 'New Maintenance Window',
            'description' => 'Database upgrade',
            'status' => 'scheduled',
            'starts_at' => '2027-01-01 02:00:00',
            'ends_at' => '2027-01-01 04:00:00',
            'auto_resolve_incidents' => false,
            'suppress_alerts' => true,
        ];

        $this->post('/maintenance-windows/add', $data);
        $this->assertRedirect(['action' => 'index']);

        $table = $this->getTableLocator()->get('MaintenanceWindows');
        $record = $table->find()->where(['title' => 'New Maintenance Window'])->first();
        $this->assertNotNull($record);
    }

    public function testEditFormLoads(): void
    {
        $this->get('/maintenance-windows/edit/1');
        $this->assertResponseOk();
    }

    public function testEditPostUpdatesRecord(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'title' => 'Updated Maintenance',
            'description' => 'Changed',
            'status' => 'scheduled',
            'starts_at' => '2027-06-01 02:00:00',
            'ends_at' => '2027-06-01 06:00:00',
        ];

        $this->post('/maintenance-windows/edit/1', $data);
        $this->assertRedirect(['action' => 'index']);

        $table = $this->getTableLocator()->get('MaintenanceWindows');
        $record = $table->get(1);
        $this->assertEquals('Updated Maintenance', $record->title);
    }

    public function testDeleteRemovesRecord(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/maintenance-windows/delete/1');
        $this->assertRedirect(['action' => 'index']);

        $table = $this->getTableLocator()->get('MaintenanceWindows');
        $count = $table->find()->where(['id' => 1])->count();
        $this->assertEquals(0, $count);
    }

    public function testDeleteRejectsGet(): void
    {
        $this->disableErrorHandlerMiddleware();
        $this->expectException(\Cake\Http\Exception\MethodNotAllowedException::class);
        $this->get('/maintenance-windows/delete/1');
    }
}
