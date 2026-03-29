<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class IntegrationsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Organizations', 'app.OrganizationUsers', 'app.Users', 'app.Integrations'];

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
        $this->get('/integrations');
        $this->assertRedirectContains('/app/integrations');
    }

    public function testAddRedirectsToAngular(): void
    {
        $this->get('/integrations/add');
        $this->assertRedirectContains('/app/integrations/new');
    }

    public function testViewRedirectsToAngular(): void
    {
        $this->get('/integrations/view/1');
        $this->assertRedirectContains('/app/integrations/1');
    }

    public function testEditRedirectsToAngular(): void
    {
        $this->get('/integrations/edit/1');
        $this->assertRedirectContains('/app/integrations/1/edit');
    }
}
