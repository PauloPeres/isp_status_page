<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class InvitationsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Invitations',
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
        $this->get('/invitations');
        $this->assertRedirectContains('/app/team');
    }

    public function testSendRedirectsToAngular(): void
    {
        $this->get('/invitations/send');
        $this->assertRedirectContains('/app/team');
    }

    public function testAcceptWithInvalidToken(): void
    {
        $this->_session = [];
        $this->cookie('csrfToken', '');
        $this->get('/invite/0000000000000000000000000000000000000000000000000000000000000000');
        $this->assertRedirectContains('/');
    }
}
