<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

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
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true, 'organization_id' => 1],
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

    public function testIndexRedirectsToAngular(): void
    {
        $this->get('/api-keys');
        $this->assertRedirectContains('/app/api-keys');
    }

    public function testAddRedirectsToAngular(): void
    {
        $this->get('/api-keys/add');
        $this->assertRedirectContains('/app/api-keys/new');
    }
}
