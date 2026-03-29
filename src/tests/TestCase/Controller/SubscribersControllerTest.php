<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SubscribersController Test Case
 *
 * @uses \App\Controller\SubscribersController
 */
class SubscribersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Subscribers',
        'app.Subscriptions',
        'app.Monitors',
        'app.Settings',
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

        $this->get('/subscribers');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexLoadsForAuthenticatedUser(): void
    {
        $this->get('/subscribers');
        $this->assertResponseOk();
    }

    public function testIndexSetsStatsVariable(): void
    {
        $this->get('/subscribers');
        $this->assertResponseOk();

        $stats = $this->viewVariable('stats');
        $this->assertNotNull($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('verified', $stats);
        $this->assertArrayHasKey('active', $stats);
    }

    public function testIndexWithSearchFilter(): void
    {
        $this->get('/subscribers?search=subscriber1');
        $this->assertResponseOk();
    }

    public function testViewLoadsSubscriber(): void
    {
        $this->get('/subscribers/view/1');
        $this->assertResponseOk();

        $subscriber = $this->viewVariable('subscriber');
        $this->assertNotNull($subscriber);
        $this->assertEquals('subscriber1@example.com', $subscriber->email);
    }

    public function testViewInvalidIdThrows(): void
    {
        $this->disableErrorHandlerMiddleware();
        $this->expectException(\Cake\Datasource\Exception\RecordNotFoundException::class);
        $this->get('/subscribers/view/999');
    }

    public function testDeleteRemovesSubscriber(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/subscribers/delete/1');
        $this->assertRedirect(['action' => 'index']);

        $subscribersTable = $this->getTableLocator()->get('Subscribers');
        $count = $subscribersTable->find()->where(['id' => 1])->count();
        $this->assertEquals(0, $count);
    }

    public function testToggleChangesActiveStatus(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Subscriber 1 is active, toggle should deactivate
        $this->post('/subscribers/toggle/1');
        $this->assertRedirect();

        $subscribersTable = $this->getTableLocator()->get('Subscribers');
        $subscriber = $subscribersTable->get(1);
        $this->assertFalse($subscriber->active);
    }
}
