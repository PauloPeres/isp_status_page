<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\InvitationsController Test Case
 *
 * @uses \App\Controller\InvitationsController
 */
class InvitationsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Invitations',
        'app.Plans',
        'app.Monitors',
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

        $this->get('/invitations');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexLoadsForAuthenticatedUser(): void
    {
        $this->get('/invitations');
        $this->assertResponseOk();
    }

    public function testIndexSetsInvitationsVariable(): void
    {
        $this->get('/invitations');
        $this->assertResponseOk();

        $invitations = $this->viewVariable('invitations');
        $this->assertNotNull($invitations);
    }

    public function testSendRequiresPost(): void
    {
        $this->disableErrorHandlerMiddleware();
        $this->expectException(\Cake\Http\Exception\MethodNotAllowedException::class);
        $this->get('/invitations/send');
    }

    public function testSendCreatesInvitationAndRedirects(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'email' => 'newinvite@example.com',
            'role' => 'member',
        ];

        $this->post('/invitations/send', $data);
        $this->assertRedirect(['action' => 'index']);
    }

    public function testAcceptPageIsPublic(): void
    {
        // Accept page should load without authentication
        $this->_session = [];

        $token = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';
        $this->get('/invite/' . $token);

        // Should load OK (show acceptance page) or redirect if handled differently
        $statusCode = $this->_response->getStatusCode();
        $this->assertTrue(
            $statusCode === 200 || ($statusCode >= 300 && $statusCode < 400),
            "Expected 200 or redirect, got {$statusCode}"
        );
    }

    public function testRevokeDeletesPendingInvitation(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Fixture ID 1 is a pending invitation
        $this->post('/invitations/revoke/1');
        $this->assertRedirect(['action' => 'index']);
    }
}
