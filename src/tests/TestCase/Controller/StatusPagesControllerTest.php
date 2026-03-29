<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\StatusPagesController Test Case
 *
 * @uses \App\Controller\StatusPagesController
 */
class StatusPagesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.Incidents',
        'app.StatusPages',
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

        $this->get('/status-pages');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexLoadsForAuthenticatedUser(): void
    {
        $this->get('/status-pages');
        $this->assertResponseOk();
    }

    public function testAddFormLoads(): void
    {
        $this->get('/status-pages/add');
        $this->assertResponseOk();
    }

    public function testAddPostCreatesStatusPage(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'name' => 'New Status Page',
            'slug' => 'new-status',
            'description' => 'A new status page',
            'active' => true,
            'show_incident_history' => true,
        ];

        $this->post('/status-pages/add', $data);
        $this->assertRedirect(['action' => 'index']);

        $table = $this->getTableLocator()->get('StatusPages');
        $record = $table->find()->where(['name' => 'New Status Page'])->first();
        $this->assertNotNull($record);
    }

    public function testShowPublicPageIsAccessibleWithoutAuth(): void
    {
        $this->_session = [];

        $this->get('/s/acme-status');
        // Should not redirect to login - the show action is public
        $statusCode = $this->_response->getStatusCode();
        $this->assertNotEquals(302, $statusCode, 'Public status page should not redirect to login');
    }

    public function testShowNonexistentPageReturns404(): void
    {
        $this->_session = [];

        $this->get('/s/does-not-exist');
        $this->assertResponseCode(404);
    }

    public function testDeleteRemovesStatusPage(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/status-pages/delete/1');
        $this->assertRedirect(['action' => 'index']);

        $table = $this->getTableLocator()->get('StatusPages');
        $count = $table->find()->where(['id' => 1])->count();
        $this->assertEquals(0, $count);
    }
}
