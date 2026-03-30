<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\RegistrationController Test Case
 *
 * @uses \App\Controller\RegistrationController
 */
class RegistrationControllerTest extends TestCase
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
    ];

    /**
     * Test register page loads (GET)
     */
    public function testRegisterGet(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        $this->get('/register');

        $this->assertResponseOk();
        $this->assertResponseContains('ISP Status');
        $this->assertResponseContains('name="username"');
        $this->assertResponseContains('name="email"');
        $this->assertResponseContains('name="password"');
        $this->assertResponseContains('name="password_confirm"');
        $this->assertResponseContains('/users/login');
    }

    /**
     * Test register page loads via fallback route
     */
    public function testRegisterGetViaControllerRoute(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        $this->get('/registration/register');

        $this->assertResponseOk();
        $this->assertResponseContains('name="username"');
    }

    /**
     * Test register creates user + org + org_user
     */
    public function testRegisterCreatesUserOrgAndOrgUser(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ];

        $this->post('/register', $data);

        // Should redirect to verify-email page
        $this->assertRedirectContains('/verify-email');

        // Verify user was created
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->find()
            ->where(['username' => 'newuser'])
            ->first();

        $this->assertNotNull($user, 'User should have been created');
        $this->assertEquals('newuser@example.com', $user->email);
        $this->assertEmpty($user->email_verified, 'New user should not have verified email');
        $this->assertNotEmpty($user->email_verification_token, 'User should have a verification token');

        // Verify organization was created
        $orgsTable = TableRegistry::getTableLocator()->get('Organizations');
        $org = $orgsTable->find()
            ->where(['name' => "newuser's Organization"])
            ->first();

        $this->assertNotNull($org, 'Organization should have been created');
        $this->assertEquals('free', $org->plan);
        $this->assertTrue($org->active);

        // Verify organization_user link was created
        $orgUsersTable = TableRegistry::getTableLocator()->get('OrganizationUsers');
        $orgUser = $orgUsersTable->find()
            ->where([
                'organization_id' => $org->id,
                'user_id' => $user->id,
            ])
            ->first();

        $this->assertNotNull($orgUser, 'OrganizationUser link should have been created');
        $this->assertEquals('owner', $orgUser->role);
    }

    /**
     * Test register validation - missing fields stays on page
     */
    public function testRegisterValidationMissingFields(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirm' => '',
        ];

        $this->post('/register', $data);

        // Controller should stay on the page and re-render the form
        $this->assertResponseOk();
        $this->assertResponseContains('name="username"');
    }

    /**
     * Test register validation - password mismatch stays on page
     */
    public function testRegisterValidationPasswordMismatch(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirm' => 'differentpassword',
        ];

        $this->post('/register', $data);

        // Should stay on the form page
        $this->assertResponseOk();
        $this->assertResponseContains('name="password_confirm"');

        // User should NOT be created
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->find()
            ->where(['username' => 'testuser'])
            ->first();
        $this->assertNull($user, 'User should not have been created with mismatched passwords');
    }

    /**
     * Test register validation - password too short
     */
    public function testRegisterValidationPasswordTooShort(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirm' => 'short',
        ];

        $this->post('/register', $data);

        $this->assertResponseOk();
        $this->assertResponseContains('name="password"');

        // User should NOT be created
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->find()
            ->where(['username' => 'testuser'])
            ->first();
        $this->assertNull($user, 'User should not have been created with short password');
    }

    /**
     * Test register validation - duplicate email prevents creation
     */
    public function testRegisterValidationDuplicateEmail(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'username' => 'anotheruser',
            'email' => 'admin@example.com', // Already exists in fixture
            'password' => 'password123',
            'password_confirm' => 'password123',
        ];

        $this->post('/register', $data);

        // Should stay on the form page (not redirect)
        $this->assertResponseOk();

        // User should NOT be created with duplicate email
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->find()
            ->where(['username' => 'anotheruser'])
            ->first();
        $this->assertNull($user, 'User should not have been created with duplicate email');
    }

    /**
     * Test register validation - duplicate username prevents creation
     */
    public function testRegisterValidationDuplicateUsername(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'username' => 'admin', // Already exists in fixture
            'email' => 'brandnew@example.com',
            'password' => 'password123',
            'password_confirm' => 'password123',
        ];

        $this->post('/register', $data);

        // Should stay on the form page
        $this->assertResponseOk();

        // The duplicate user should not create a second admin user
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $count = $usersTable->find()
            ->where(['username' => 'admin'])
            ->count();
        $this->assertEquals(1, $count, 'Should not create duplicate username');
    }

    /**
     * Test verifyEmail with valid token
     */
    public function testVerifyEmailWithValidToken(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        // Create a user with a verification token
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get(2); // 'user' from fixtures
        $user->email_verified = false;
        $token = str_repeat('a', 64);
        $user->email_verification_token = $token;
        $user->email_verification_sent_at = new \DateTime();
        $saveResult = $usersTable->save($user);
        $this->assertNotFalse($saveResult, 'Token setup save should succeed');

        // Verify the token was persisted
        $checkUser = $usersTable->get(2);
        $this->assertEquals($token, $checkUser->email_verification_token, 'Token should be persisted');

        $this->get('/verify-email/' . $token);

        // Should redirect to dashboard after successful verification
        $this->assertRedirect('/dashboard');

        // Verify the user was updated
        $updatedUser = $usersTable->get(2);
        $this->assertTrue((bool)$updatedUser->email_verified);
        $this->assertNull($updatedUser->email_verification_token);
    }

    /**
     * Test verifyEmail with invalid token
     */
    public function testVerifyEmailWithInvalidToken(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        $this->get('/verify-email/invalidtoken123');

        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test verifyEmail with expired token
     */
    public function testVerifyEmailWithExpiredToken(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        // Create a user with an expired verification token
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $usersTable->get(2);
        $user->email_verified = false;
        $user->email_verification_token = str_repeat('b', 64);
        $user->email_verification_sent_at = date('Y-m-d H:i:s', strtotime('-48 hours')); // Expired
        $usersTable->save($user);

        $this->get('/verify-email/' . str_repeat('b', 64));

        $this->assertRedirectContains('/users/login');
    }

    /**
     * Test verify email page without token (check your email page)
     */
    public function testVerifyEmailPageWithoutToken(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        $this->get('/registration/verify-email?email=test@example.com');

        $this->assertResponseOk();
        $this->assertResponseContains('test@example.com');
    }

    /**
     * Test login page has register link
     */
    public function testLoginPageHasRegisterLink(): void
    {
        $this->markTestSkipped('Legacy web controller — functionality moved to Angular SPA + API v2');
        $this->get('/users/login');

        $this->assertResponseOk();
        // The register link should be present (either /register or /registration/register)
        $this->assertResponseContains('register');
    }
}
