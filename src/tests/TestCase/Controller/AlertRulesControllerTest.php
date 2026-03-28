<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AlertRulesController Test Case
 *
 * @uses \App\Controller\AlertRulesController
 */
class AlertRulesControllerTest extends TestCase
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

        $this->get('/alert-rules');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test index method returns 200 for authenticated users
     */
    public function testIndexAuthenticated(): void
    {
        $this->get('/alert-rules');
        $this->assertResponseOk();
    }

    /**
     * Test add form loads for authenticated users
     */
    public function testAddFormLoads(): void
    {
        $this->get('/alert-rules/add');
        $this->assertResponseOk();
    }

    /**
     * Test add POST creates an alert rule
     */
    public function testAddPostCreatesRule(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'organization_id' => 1,
            'monitor_id' => 1,
            'channel' => 'email',
            'trigger_on' => 'on_down',
            'throttle_minutes' => 5,
            'recipients_text' => "test@example.com\nops@example.com",
            'active' => true,
        ];

        $this->post('/alert-rules/add', $data);
        $this->assertRedirect(['action' => 'index']);

        $rulesTable = $this->getTableLocator()->get('AlertRules');
        $rule = $rulesTable->find()
            ->where(['AlertRules.monitor_id' => 1, 'AlertRules.trigger_on' => 'on_down'])
            ->orderBy(['AlertRules.id' => 'DESC'])
            ->first();
        $this->assertNotNull($rule);
        $this->assertSame('email', $rule->channel);
        $this->assertSame('on_down', $rule->trigger_on);
    }
}
