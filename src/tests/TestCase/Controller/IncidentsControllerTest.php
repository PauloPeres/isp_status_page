<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\IncidentsController Test Case
 *
 * @uses \App\Controller\IncidentsController
 */
class IncidentsControllerTest extends TestCase
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
        'app.IncidentUpdates',
        'app.AlertRules',
        'app.AlertLogs',
    ];

    /**
     * Set up authentication for tests
     */
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

    /**
     * Test index requires authentication
     */
    public function testIndexRequiresAuth(): void
    {
        $this->_session = [];
        $this->cookie('csrfToken', '');

        $this->get('/incidents');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test index method returns 200 for authenticated users
     */
    public function testIndexAuthenticated(): void
    {
        $this->get('/incidents');
        $this->assertResponseOk();
    }

    /**
     * Test view method returns 200 for authenticated users
     */
    public function testViewAuthenticated(): void
    {
        $this->get('/incidents/view/1');
        $this->assertResponseOk();
    }

    /**
     * Test add form loads for authenticated users
     */
    public function testAddFormLoads(): void
    {
        $this->get('/incidents/add');
        $this->assertResponseOk();
    }

    /**
     * Test add POST creates an incident and redirects
     */
    public function testAddPostCreatesIncident(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'organization_id' => 1,
            'monitor_id' => 1,
            'title' => 'New Test Incident',
            'description' => 'Something went wrong',
            'status' => 'investigating',
            'severity' => 'major',
            'started_at' => '2026-03-27 10:00:00',
            'auto_created' => false,
        ];

        $this->post('/incidents/add', $data);
        $this->assertRedirect(['action' => 'index']);

        $incidentsTable = $this->getTableLocator()->get('Incidents');
        $incident = $incidentsTable->find()
            ->where(['title' => 'New Test Incident'])
            ->first();
        $this->assertNotNull($incident);
    }

    /**
     * Test addUpdate POST creates an incident update and redirects
     */
    public function testAddUpdatePostsUpdate(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'status' => 'identified',
            'message' => 'Root cause identified: database connection pool exhausted',
            'is_public' => true,
        ];

        $this->post('/incidents/1/update', $data);
        $this->assertRedirect(['action' => 'view', 1]);
        $this->assertFlashMessage('Update posted');
    }
}
