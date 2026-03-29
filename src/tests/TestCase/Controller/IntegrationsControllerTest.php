<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\IntegrationsController Test Case
 *
 * @uses \App\Controller\IntegrationsController
 */
class IntegrationsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Integrations',
        'app.IntegrationLogs',
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

        $this->get('/integrations');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexLoadsForAuthenticatedUser(): void
    {
        $this->get('/integrations');
        $this->assertResponseOk();
    }

    public function testIndexSetsStatsAndIntegrations(): void
    {
        $this->get('/integrations');
        $this->assertResponseOk();

        $stats = $this->viewVariable('stats');
        $this->assertNotNull($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);

        $integrations = $this->viewVariable('integrations');
        $this->assertNotNull($integrations);
    }

    public function testAddFormLoads(): void
    {
        $this->get('/integrations/add');
        $this->assertResponseOk();
    }

    public function testAddPostCreatesIntegration(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'name' => 'New REST Integration',
            'type' => 'rest_api',
            'config_base_url' => 'https://new-api.example.com',
            'config_method' => 'GET',
            'config_timeout' => '30',
            'active' => true,
        ];

        $this->post('/integrations/add', $data);
        $this->assertRedirect(['action' => 'index']);

        $integrationsTable = $this->getTableLocator()->get('Integrations');
        $integration = $integrationsTable->find()
            ->where(['name' => 'New REST Integration'])
            ->first();
        $this->assertNotNull($integration);
        $this->assertEquals('rest_api', $integration->type);
    }

    public function testViewLoadsIntegration(): void
    {
        $this->get('/integrations/view/1');
        $this->assertResponseOk();

        $integration = $this->viewVariable('integration');
        $this->assertNotNull($integration);
        $this->assertEquals('Test REST API', $integration->name);
    }

    public function testEditFormLoads(): void
    {
        $this->get('/integrations/edit/1');
        $this->assertResponseOk();
    }

    public function testEditPostUpdatesIntegration(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'name' => 'Updated Integration',
            'type' => 'rest_api',
            'config_base_url' => 'https://updated-api.example.com',
            'active' => true,
        ];

        $this->post('/integrations/edit/1', $data);
        $this->assertRedirect(['action' => 'index']);

        $integrationsTable = $this->getTableLocator()->get('Integrations');
        $integration = $integrationsTable->get(1);
        $this->assertEquals('Updated Integration', $integration->name);
    }

    public function testDeleteRemovesIntegration(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/integrations/delete/1');
        $this->assertRedirect(['action' => 'index']);

        $integrationsTable = $this->getTableLocator()->get('Integrations');
        $count = $integrationsTable->find()->where(['id' => 1])->count();
        $this->assertEquals(0, $count);
    }

    public function testDeleteRejectsGet(): void
    {
        $this->disableErrorHandlerMiddleware();
        $this->expectException(\Cake\Http\Exception\MethodNotAllowedException::class);
        $this->get('/integrations/delete/1');
    }
}
