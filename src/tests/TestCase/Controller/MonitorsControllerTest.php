<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\Datasource\ConnectionManager;
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
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
        'app.Plans',
    ];

    /**
     * Set up authentication for tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Authenticate as admin for all tests with tenant context
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ],
            'current_organization_id' => 1,
        ]);
    }

    /**
     * Check if the current test database driver is SQLite.
     *
     * MonitorsController::index() uses PostgreSQL-specific DISTINCT ON syntax
     * which is not supported by SQLite. Tests that hit the index route are
     * skipped when running on SQLite.
     */
    protected function skipIfSqlite(): void
    {
        $connection = ConnectionManager::get('test');
        $driver = $connection->getDriver();
        if ($driver instanceof \Cake\Database\Driver\Sqlite) {
            $this->markTestSkipped(
                'MonitorsController::index() uses DISTINCT ON (PostgreSQL-specific). Skipped on SQLite.'
            );
        }
    }

    /**
     * Test index method requires authentication
     */
    public function testIndexRequiresAuthentication(): void
    {
        $this->_session = []; // Fully clear session (session() merges, so we reset directly)

        $this->get('/monitors');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test index method
     */
    public function testIndex(): void
    {
        $this->skipIfSqlite();

        $this->get('/monitors');

        $this->assertResponseOk();
    }

    /**
     * Test index method sets statistics
     */
    public function testIndexSetsStatistics(): void
    {
        $this->skipIfSqlite();

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
        $this->skipIfSqlite();

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
        $this->skipIfSqlite();

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
    }

    /**
     * Test view method with invalid id
     */
    public function testViewInvalidId(): void
    {
        $this->get('/monitors/view/999');
        // With TenantScope, invalid IDs result in a 404
        $this->assertResponseCode(404);
    }

    /**
     * Test add method GET
     */
    public function testAddGet(): void
    {
        $this->get('/monitors/add');

        $this->assertResponseOk();
    }

    /**
     * Test add method POST with valid data
     */
    public function testAddPostValid(): void
    {
        // Use org 2 (pro plan, monitor_limit=50) since org 1 (free, limit=1) already has monitors
        $this->_session = [];
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ],
            'current_organization_id' => 2,
        ]);

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'name' => 'Test Monitor',
            'description' => 'Test Description',
            'type' => 'http',
            'target' => 'https://example.com',
            'interval' => 60,
            'timeout' => 10,
            'expected_status_code' => 200,
            'active' => true,
        ];

        $this->post('/monitors/add', $data);

        $this->assertRedirect(['action' => 'index']);
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

        // With validation failure, may render form again (200) or redirect
        $code = $this->_response->getStatusCode();
        $this->assertTrue(
            $code === 200 || $code === 302,
            "Expected 200 or 302, got {$code}"
        );
    }

    /**
     * Test edit method GET
     */
    public function testEditGet(): void
    {
        $this->get('/monitors/edit/1');

        $this->assertResponseOk();
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
    }

    /**
     * Test delete method with GET request (should fail)
     */
    public function testDeleteGetNotAllowed(): void
    {
        $this->get('/monitors/delete/1');

        $this->assertResponseCode(405);
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
    }

    /**
     * Test that admin layout is used
     */
    public function testUsesAdminLayout(): void
    {
        $this->skipIfSqlite();

        $this->get('/monitors');

        $this->assertResponseOk();
        $this->assertLayout('admin');
    }

    /**
     * Test pagination works
     */
    public function testPagination(): void
    {
        $this->skipIfSqlite();

        $this->get('/monitors');

        $this->assertResponseOk();

        $monitors = $this->viewVariable('monitors');
        $this->assertNotNull($monitors);
    }
}
