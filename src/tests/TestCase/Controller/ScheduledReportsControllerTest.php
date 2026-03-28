<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ScheduledReportsController Test Case
 *
 * @uses \App\Controller\ScheduledReportsController
 */
class ScheduledReportsControllerTest extends TestCase
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
        'app.ScheduledReports',
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

        $this->get('/scheduled-reports');
        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test index method returns 200 for authenticated users
     */
    public function testIndexAuthenticated(): void
    {
        $this->get('/scheduled-reports');
        $this->assertResponseOk();
    }

    /**
     * Test add form loads for authenticated users
     */
    public function testAddFormLoads(): void
    {
        $this->get('/scheduled-reports/add');
        $this->assertResponseOk();
    }
}
