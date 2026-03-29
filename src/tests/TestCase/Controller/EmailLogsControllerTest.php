<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\EmailLogsController Test Case
 *
 * @uses \App\Controller\EmailLogsController
 */
class EmailLogsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
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

        $this->get('/email-logs');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexLoadsForAuthenticatedUser(): void
    {
        $this->get('/email-logs');
        $this->assertResponseOk();
    }

    public function testIndexSetsStatsVariable(): void
    {
        $this->get('/email-logs');
        $this->assertResponseOk();

        $stats = $this->viewVariable('stats');
        $this->assertNotNull($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('sent', $stats);
        $this->assertArrayHasKey('failed', $stats);
        $this->assertArrayHasKey('successRate', $stats);
        $this->assertArrayHasKey('today', $stats);
    }

    public function testIndexWithPeriodFilter(): void
    {
        $this->get('/email-logs?period=30d');
        $this->assertResponseOk();
    }

    public function testViewEmailLog(): void
    {
        // AlertLog ID 1 from AlertLogsFixture has channel='email'
        $alertLogsTable = $this->getTableLocator()->get('AlertLogs');
        $log = $alertLogsTable->find()->where(['channel' => 'email'])->first();

        if ($log) {
            $this->get('/email-logs/view/' . $log->id);
            $this->assertResponseOk();
        } else {
            $this->markTestSkipped('No email log fixture with channel=email available');
        }
    }
}
