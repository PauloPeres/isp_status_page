<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\BillingController Test Case
 *
 * @uses \App\Controller\BillingController
 */
class BillingControllerTest extends TestCase
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
        'app.Plans',
        'app.Monitors',
        'app.Incidents',
        'app.MonitorChecks',
        'app.NotificationCredits',
        'app.NotificationCreditTransactions',
    ];

    /**
     * Test plans page loads for authenticated user and contains plan names
     */
    public function testPlansAuthenticated(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
                'organization_id' => 1,
            ],
            'current_organization_id' => 1,
        ]);

        $this->get('/billing/plans');

        $this->assertResponseOk();
        $this->assertResponseContains('Free');
        $this->assertResponseContains('Pro');
        $this->assertResponseContains('Business');
    }

    /**
     * Test plans page requires authentication
     */
    public function testPlansRequiresAuth(): void
    {
        $this->get('/billing/plans');

        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test plans page requires authentication (alias for backward compatibility)
     */
    public function testPlansUnauthenticated(): void
    {
        $this->get('/billing/plans');

        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test checkout requires authentication
     */
    public function testCheckoutRequiresAuth(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/billing/checkout/pro');

        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test checkout requires POST method
     */
    public function testCheckoutRequiresPost(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ],
            'current_organization_id' => 1,
        ]);

        $this->get('/billing/checkout/pro');

        $this->assertResponseCode(405);
    }

    /**
     * Test success page loads for authenticated user
     */
    public function testSuccessPageLoads(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ],
            'current_organization_id' => 1,
        ]);

        $this->get('/billing/success');

        $this->assertResponseOk();
    }

    /**
     * Test cancel page loads for authenticated user
     */
    public function testCancelPageLoads(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ],
            'current_organization_id' => 1,
        ]);

        $this->get('/billing/cancel');

        $this->assertResponseOk();
    }

    /**
     * Test portal requires POST method
     */
    public function testPortalRequiresPost(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ],
            'current_organization_id' => 1,
        ]);

        $this->get('/billing/portal');

        $this->assertResponseCode(405);
    }
}
