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
    ];

    /**
     * Test plans page loads for authenticated user
     */
    public function testPlansAuthenticated(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ],
        ]);

        $this->get('/billing/plans');

        $this->assertResponseOk();
        $this->assertResponseContains('Plans');
    }

    /**
     * Test plans page requires authentication
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
        ]);

        $this->get('/billing/success');

        $this->assertResponseOk();
        $this->assertResponseContains('Thank You');
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
        ]);

        $this->get('/billing/cancel');

        $this->assertResponseOk();
        $this->assertResponseContains('Cancelled');
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
        ]);

        $this->get('/billing/portal');

        $this->assertResponseCode(405);
    }
}
