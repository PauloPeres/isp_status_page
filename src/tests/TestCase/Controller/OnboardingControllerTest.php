<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class OnboardingControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Organizations', 'app.OrganizationUsers', 'app.Users', 'app.Monitors'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true, 'organization_id' => 1],
            'current_organization_id' => 1,
        ]);
    }

    public function testStep1RedirectsToAngular(): void
    {
        $this->get('/onboarding/step1');
        $this->assertRedirectContains('/app/onboarding');
    }

    public function testStep2RedirectsToAngular(): void
    {
        $this->get('/onboarding/step2');
        $this->assertRedirectContains('/app/onboarding');
    }

    public function testStep3RedirectsToAngular(): void
    {
        $this->get('/onboarding/step3');
        $this->assertRedirectContains('/app/onboarding');
    }

    public function testCompleteRedirectsToAngular(): void
    {
        $this->get('/onboarding/complete');
        $this->assertRedirectContains('/app/dashboard');
    }
}
