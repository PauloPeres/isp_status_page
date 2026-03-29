<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ActivityLogController Test Case
 *
 * @uses \App\Controller\ActivityLogController
 */
class ActivityLogControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.SecurityAuditLogs',
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

        $this->get('/activity-log');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexLoadsForAuthenticatedUser(): void
    {
        $this->get('/activity-log');
        $this->assertResponseOk();
    }

    public function testIndexSetsLogsVariable(): void
    {
        $this->get('/activity-log');
        $this->assertResponseOk();

        $logs = $this->viewVariable('logs');
        $this->assertNotNull($logs);
    }

    public function testIndexWithEventTypeFilter(): void
    {
        $this->get('/activity-log?event_type=login');
        $this->assertResponseOk();
    }
}
