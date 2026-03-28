<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SlaController Test Case
 *
 * @uses \App\Controller\SlaController
 */
class SlaControllerTest extends TestCase
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
        'app.SlaDefinitions',
        'app.SlaReports',
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

        $this->get('/sla');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test index method returns 200 for authenticated users
     */
    public function testIndexAuthenticated(): void
    {
        $this->get('/sla');
        $this->assertResponseOk();
    }

    /**
     * Test add form loads for authenticated users
     */
    public function testAddFormLoads(): void
    {
        $this->get('/sla/add');
        $this->assertResponseOk();
    }

    /**
     * Test add POST creates an SLA definition
     */
    public function testAddPostCreatesSla(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Delete existing SLA for monitor 3 to avoid unique constraint
        $data = [
            'organization_id' => 1,
            'monitor_id' => 3,
            'name' => 'New SLA Definition',
            'target_uptime' => 99.5,
            'measurement_period' => 'monthly',
            'breach_notification' => true,
            'active' => true,
        ];

        $this->post('/sla/add', $data);
        $this->assertRedirect(['action' => 'index']);

        $slaTable = $this->getTableLocator()->get('SlaDefinitions');
        $sla = $slaTable->find()
            ->where(['name' => 'New SLA Definition'])
            ->first();
        $this->assertNotNull($sla);
        $this->assertEquals(3, $sla->monitor_id);
        $this->assertEquals(99.5, (float)$sla->target_uptime);
    }
}
