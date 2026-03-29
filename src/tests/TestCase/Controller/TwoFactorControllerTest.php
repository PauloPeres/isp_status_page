<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\TwoFactorController Test Case
 *
 * @uses \App\Controller\TwoFactorController
 */
class TwoFactorControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
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

    public function testSetupRequiresAuth(): void
    {
        $this->_session = [];
        $this->cookie('csrfToken', '');

        $this->get('/two-factor/setup');
        $this->assertRedirectContains('/users/login');
    }

    public function testSetupLoadsForAuthenticatedUser(): void
    {
        $this->get('/two-factor/setup');
        $this->assertResponseOk();

        $secret = $this->viewVariable('secret');
        $this->assertNotNull($secret, 'Setup page should provide a TOTP secret');
        $this->assertNotEmpty($secret);

        $qrCodeUrl = $this->viewVariable('qrCodeUrl');
        $this->assertNotNull($qrCodeUrl);
        $this->assertStringContainsString('otpauth://totp/', $qrCodeUrl);
    }

    public function testVerifyPageIsPublic(): void
    {
        // The verify page should be accessible without authentication
        // but requires a pending 2FA user in session
        $this->_session = [];

        $this->get('/two-factor/verify');
        // Without pending_2fa_user_id in session, should redirect to login
        $this->assertRedirectContains('/users/login');
    }

    public function testVerifyWithPendingUserShowsForm(): void
    {
        $this->_session = [];
        $this->session([
            'pending_2fa_user_id' => 1,
        ]);

        $this->get('/two-factor/verify');
        $this->assertResponseOk();
    }

    public function testSetupAlreadyEnabledRedirects(): void
    {
        // Enable 2FA for user 1
        $usersTable = $this->getTableLocator()->get('Users');
        $user = $usersTable->get(1);
        $user->two_factor_enabled = true;
        $user->two_factor_secret = 'TESTSECRET12345678';
        $user->setAccess('two_factor_enabled', true);
        $user->setAccess('two_factor_secret', true);
        $usersTable->save($user);

        $this->get('/two-factor/setup');
        $this->assertRedirect();
    }
}
