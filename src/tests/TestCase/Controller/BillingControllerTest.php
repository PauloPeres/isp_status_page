<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class BillingControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Plans',
        'app.Monitors',
        'app.Incidents',
        'app.MonitorChecks',
        'app.NotificationCredits',
        'app.NotificationCreditTransactions',
    ];

    public function testPlansRedirectsToAngular(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true, 'organization_id' => 1],
            'current_organization_id' => 1,
        ]);
        $this->get('/billing/plans');
        $this->assertRedirectContains('/app/billing');
    }

    public function testPlansRequiresAuth(): void
    {
        $this->get('/billing/plans');
        $this->assertRedirectContains('/users/login');
    }

    public function testPlansUnauthenticated(): void
    {
        $this->get('/billing/plans');
        $this->assertRedirectContains('/users/login');
    }

    public function testCheckoutRequiresAuth(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billing/checkout/pro');
        $this->assertRedirectContains('/users/login');
    }

    public function testCheckoutRequiresPost(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true],
            'current_organization_id' => 1,
        ]);
        $this->get('/billing/checkout/pro');
        $this->assertResponseCode(405);
    }

    public function testSuccessRedirectsToAngular(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true],
            'current_organization_id' => 1,
        ]);
        $this->get('/billing/success');
        $this->assertRedirectContains('/app/billing');
    }

    public function testCancelRedirectsToAngular(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true],
            'current_organization_id' => 1,
        ]);
        $this->get('/billing/cancel');
        $this->assertRedirectContains('/app/billing');
    }

    public function testPortalRequiresPost(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true],
            'current_organization_id' => 1,
        ]);
        $this->get('/billing/portal');
        $this->assertResponseCode(405);
    }
}
