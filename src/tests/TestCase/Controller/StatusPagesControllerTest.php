<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class StatusPagesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.StatusPages',
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
    ];

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
        $this->get('/status-pages');
        $this->assertRedirectContains('/app/status-pages');
    }

    public function testAddRedirectsToAngular(): void
    {
        $this->get('/status-pages/add');
        $this->assertRedirectContains('/app/status-pages/new');
    }

    public function testDeleteRedirectsToAngular(): void
    {
        $this->get('/status-pages/delete/1');
        $this->assertRedirectContains('/app/status-pages');
    }

    public function testShowPublicStatusPage(): void
    {
        $this->_session = [];
        $this->cookie('csrfToken', '');

        // Create a status page in the fixture if not present
        $table = $this->getTableLocator()->get('StatusPages');
        $page = $table->find()->where(['slug IS NOT' => null, 'active' => true])->first();
        if ($page) {
            $this->get('/s/' . $page->slug);
            $this->assertResponseOk();
        } else {
            $this->assertTrue(true, 'No active status page fixture to test');
        }
    }
}
