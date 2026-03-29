<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\OnboardingController Test Case
 *
 * @uses \App\Controller\OnboardingController
 */
class OnboardingControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.Invitations',
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

    public function testStep1RequiresAuth(): void
    {
        $this->_session = [];
        $this->cookie('csrfToken', '');

        $this->get('/onboarding/step1');
        $this->assertRedirectContains('/users/login');
    }

    public function testStep1LoadsForAuthenticatedUser(): void
    {
        $this->get('/onboarding/step1');
        $this->assertResponseOk();

        $organization = $this->viewVariable('organization');
        $this->assertNotNull($organization);
    }

    public function testStep1PostUpdatesOrganization(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'name' => 'Updated Org Name',
            'slug' => 'updated-org',
        ];

        $this->post('/onboarding/step1', $data);
        $this->assertRedirect(['action' => 'step2']);

        $orgsTable = $this->getTableLocator()->get('Organizations');
        $org = $orgsTable->get(1);
        $this->assertEquals('Updated Org Name', $org->name);
    }

    public function testStep2LoadsForAuthenticatedUser(): void
    {
        $this->get('/onboarding/step2');
        $this->assertResponseOk();
    }

    public function testStep2PostCreatesMonitor(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'name' => 'My First Monitor',
            'url' => 'https://example.com',
            'check_interval' => 300,
        ];

        $this->post('/onboarding/step2', $data);
        $this->assertRedirect(['action' => 'step3']);

        $monitorsTable = $this->getTableLocator()->get('Monitors');
        $monitor = $monitorsTable->find()->where(['name' => 'My First Monitor'])->first();
        $this->assertNotNull($monitor);
        $this->assertEquals('http', $monitor->type);
    }

    public function testStep3LoadsForAuthenticatedUser(): void
    {
        $this->get('/onboarding/step3');
        $this->assertResponseOk();
    }

    public function testCompleteLoadsForAuthenticatedUser(): void
    {
        $this->get('/onboarding/complete');
        $this->assertResponseOk();
    }
}
