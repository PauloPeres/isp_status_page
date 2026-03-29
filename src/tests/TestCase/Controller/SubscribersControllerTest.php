<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

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
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true, 'organization_id' => 1],
            'current_organization_id' => 1,
        ]);
    }

    public function testIndexRedirectsToAngular(): void
    {
        $this->get('/subscribers');
        $this->assertRedirectContains('/app/subscribers');
    }

    public function testViewRedirectsToAngular(): void
    {
        $this->get('/subscribers/view/1');
        $this->assertRedirectContains('/app/subscribers/1');
    }

    public function testDeleteRedirectsToAngular(): void
    {
        $this->get('/subscribers/delete/1');
        $this->assertRedirectContains('/app/subscribers');
    }

    public function testToggleRedirectsToAngular(): void
    {
        $this->get('/subscribers/toggle/1');
        $this->assertRedirectContains('/app/subscribers');
    }

    public function testVerifyWithInvalidToken(): void
    {
        $this->_session = [];
        $this->cookie('csrfToken', '');
        $this->get('/subscribers/verify/invalid-token');
        $this->assertRedirectContains('/status');
    }

    public function testUnsubscribeWithInvalidToken(): void
    {
        $this->_session = [];
        $this->cookie('csrfToken', '');
        $this->get('/subscribers/unsubscribe/invalid-token');
        $this->assertRedirectContains('/status');
    }
}
