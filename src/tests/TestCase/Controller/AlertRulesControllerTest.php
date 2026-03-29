<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AlertRulesController Test Case
 *
 * Tests that legacy admin routes redirect to Angular SPA.
 */
class AlertRulesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
        'app.AlertRules',
        'app.AlertLogs',
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
        $this->get('/alert-rules');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexRedirectsToAngular(): void
    {
        $this->get('/alert-rules');
        $this->assertRedirectContains('/app/alert-rules');
    }

    public function testAddRedirectsToAngular(): void
    {
        $this->get('/alert-rules/add');
        $this->assertRedirectContains('/app/alert-rules/new');
    }
}
