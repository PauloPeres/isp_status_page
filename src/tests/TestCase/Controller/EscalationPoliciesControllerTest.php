<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class EscalationPoliciesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = ['app.Organizations', 'app.OrganizationUsers', 'app.Users'];

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
        $this->get('/escalation-policies');
        $this->assertRedirectContains('/app/escalation-policies');
    }

    public function testAddRedirectsToAngular(): void
    {
        $this->get('/escalation-policies/add');
        $this->assertRedirectContains('/app/escalation-policies/new');
    }

    public function testViewRedirectsToAngular(): void
    {
        $this->get('/escalation-policies/view/1');
        $this->assertRedirectContains('/app/escalation-policies/1');
    }
}
