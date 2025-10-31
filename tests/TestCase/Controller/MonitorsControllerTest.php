<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\MonitorsController Test Case
 *
 * @uses \App\Controller\MonitorsController
 */
class MonitorsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
    ];

    /**
     * Set up authentication for tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Authenticate as admin for all tests
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);
    }

    /**
     * Test index method requires authentication
     */
    public function testIndexRequiresAuthentication(): void
    {
        $this->session([]); // Clear session

        $this->get('/monitors');
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);
    }

    /**
     * Test index method
     */
    public function testIndex(): void
    {
        $this->get('/monitors');

        $this->assertResponseOk();
        $this->assertResponseContains('Monitores');
        $this->assertResponseContains('Novo Monitor');
    }

    /**
     * Test index method sets statistics
     */
    public function testIndexSetsStatistics(): void
    {
        $this->get('/monitors');

        $this->assertResponseOk();

        $stats = $this->viewVariable('stats');
        $this->assertNotNull($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('online', $stats);
        $this->assertArrayHasKey('offline', $stats);
    }

    /**
     * Test index with search filter
     */
    public function testIndexWithSearch(): void
    {
        $this->get('/monitors?search=Website');

        $this->assertResponseOk();
        $monitors = $this->viewVariable('monitors');
        $this->assertNotNull($monitors);
    }

    /**
     * Test index with type filter
     */
    public function testIndexWithTypeFilter(): void
    {
        $this->get('/monitors?type=http');

        $this->assertResponseOk();
        $monitors = $this->viewVariable('monitors');
        $this->assertNotNull($monitors);
    }

    /**
     * Test view method
     */
    public function testView(): void
    {
        $this->get('/monitors/view/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Detalhes do Monitor');
        $this->assertNotNull($this->viewVariable('monitor'));
        $this->assertNotNull($this->viewVariable('uptime'));
        $this->assertNotNull($this->viewVariable('avgResponseTime'));
        $this->assertNotNull($this->viewVariable('totalChecks'));
    }

    /**
     * Test view method with invalid id
     */
    public function testViewInvalidId(): void
    {
        $this->expectException(\Cake\Datasource\Exception\RecordNotFoundException::class);
        $this->get('/monitors/view/999');
    }

    /**
     * Test add method GET
     */
    public function testAddGet(): void
    {
        $this->get('/monitors/add');

        $this->assertResponseOk();
        $this->assertResponseContains('Novo Monitor');
        $this->assertResponseContains('Nome do Monitor');
    }

    /**
     * Test add method POST with valid data
     */
    public function testAddPostValid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'name' => 'Test Monitor',
            'description' => 'Test Description',
            'type' => 'http',
            'target' => 'https://example.com',
            'interval' => 30,
            'timeout' => 10,
            'expected_status_code' => 200,
            'active' => true,
        ];

        $this->post('/monitors/add', $data);

        $this->assertRedirect(['action' => 'index']);
        $this->assertFlashMessage('Monitor criado com sucesso.');
    }

    /**
     * Test add method POST with invalid data
     */
    public function testAddPostInvalid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'name' => '', // Invalid - required
            'type' => 'http',
        ];

        $this->post('/monitors/add', $data);

        $this->assertResponseOk();
        $this->assertFlashMessage('Não foi possível criar o monitor. Por favor, tente novamente.');
    }

    /**
     * Test edit method GET
     */
    public function testEditGet(): void
    {
        $this->get('/monitors/edit/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Editar Monitor');
    }

    /**
     * Test edit method POST with valid data
     */
    public function testEditPostValid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'name' => 'Updated Monitor',
            'description' => 'Updated Description',
            'type' => 'http',
            'target' => 'https://example.com',
            'interval' => 60,
            'timeout' => 15,
            'active' => true,
        ];

        $this->post('/monitors/edit/1', $data);

        $this->assertRedirect(['action' => 'index']);
        $this->assertFlashMessage('Monitor atualizado com sucesso.');
    }

    /**
     * Test delete method
     */
    public function testDelete(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/monitors/delete/1');

        $this->assertRedirect(['action' => 'index']);
        $this->assertFlashMessage('Monitor excluído com sucesso.');
    }

    /**
     * Test delete method with GET request (should fail)
     */
    public function testDeleteGetNotAllowed(): void
    {
        $this->expectException(\Cake\Http\Exception\MethodNotAllowedException::class);
        $this->get('/monitors/delete/1');
    }

    /**
     * Test toggle method activates monitor
     */
    public function testToggleActivate(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // First, make sure monitor exists and is inactive
        $MonitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $MonitorsTable->get(1);
        $monitor->active = false;
        $MonitorsTable->save($monitor);

        $this->post('/monitors/toggle/1');

        $this->assertRedirect(['action' => 'index']);
        $this->assertFlashMessage('Monitor ativado com sucesso.');

        // Verify it was activated
        $monitor = $MonitorsTable->get(1);
        $this->assertTrue($monitor->active);
    }

    /**
     * Test toggle method deactivates monitor
     */
    public function testToggleDeactivate(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Make sure monitor is active
        $MonitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $MonitorsTable->get(1);
        $monitor->active = true;
        $MonitorsTable->save($monitor);

        $this->post('/monitors/toggle/1');

        $this->assertRedirect(['action' => 'index']);
        $this->assertFlashMessage('Monitor desativado com sucesso.');

        // Verify it was deactivated
        $monitor = $MonitorsTable->get(1);
        $this->assertFalse($monitor->active);
    }

    /**
     * Test that admin layout is used
     */
    public function testUsesAdminLayout(): void
    {
        $this->get('/monitors');

        $this->assertResponseOk();
        $this->assertLayout('admin');
    }

    /**
     * Test pagination works
     */
    public function testPagination(): void
    {
        $this->get('/monitors');

        $this->assertResponseOk();

        $monitors = $this->viewVariable('monitors');
        $this->assertNotNull($monitors);
    }
}
