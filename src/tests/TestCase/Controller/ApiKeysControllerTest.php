<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ApiKeysController Test Case
 *
 * @uses \App\Controller\ApiKeysController
 */
class ApiKeysControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.ApiKeys',
        'app.Plans',
        'app.Monitors',
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

        $this->get('/api-keys');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexLoadsForAuthenticatedUser(): void
    {
        $this->get('/api-keys');
        $this->assertResponseOk();
    }

    public function testIndexListsApiKeys(): void
    {
        $this->get('/api-keys');
        $this->assertResponseOk();

        $apiKeys = $this->viewVariable('apiKeys');
        $this->assertNotNull($apiKeys);
    }

    public function testAddFormLoads(): void
    {
        $this->get('/api-keys/add');
        $this->assertResponseOk();
    }

    public function testAddPostCreatesApiKey(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'name' => 'Test API Key',
            'perm_read' => '1',
            'perm_write' => '1',
        ];

        $this->post('/api-keys/add', $data);

        // Should stay on the add page showing the new key
        $this->assertResponseOk();

        $plainKey = $this->viewVariable('plainKey');
        $this->assertNotNull($plainKey, 'A plain key should be displayed after creation');
    }

    public function testDeleteRevokesApiKey(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/api-keys/delete/1');
        $this->assertRedirect(['action' => 'index']);

        $apiKeysTable = $this->getTableLocator()->get('ApiKeys');
        $apiKey = $apiKeysTable->get(1);
        $this->assertFalse($apiKey->active, 'API key should be revoked (inactive)');
    }

    public function testDeleteRejectsGet(): void
    {
        $this->disableErrorHandlerMiddleware();
        $this->expectException(\Cake\Http\Exception\MethodNotAllowedException::class);
        $this->get('/api-keys/delete/1');
    }
}
