<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\UsersController Test Case
 *
 * @uses \App\Controller\UsersController
 */
class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * Test login method - GET
     */
    public function testLoginGet(): void
    {
        $this->get('/users/login');

        $this->assertResponseOk();
        $this->assertResponseContains('ISP Status');
        $this->assertResponseContains('Entre com sua conta');
        $this->assertResponseContains('Usuário');
        $this->assertResponseContains('Senha');
    }

    /**
     * Test login method - POST with valid credentials
     */
    public function testLoginPostValid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/users/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $this->assertRedirect(['controller' => 'Admin', 'action' => 'index']);
        $this->assertSession('admin', 'Auth.username');
    }

    /**
     * Test login method - POST with invalid credentials
     */
    public function testLoginPostInvalid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/users/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Usuário ou senha inválidos');
    }

    /**
     * Test logout method
     */
    public function testLogout(): void
    {
        // Login first
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);

        $this->get('/users/logout');

        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);
        $this->assertSession(null, 'Auth');
    }

    /**
     * Test index method (requires authentication)
     */
    public function testIndexAuthenticated(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);

        $this->get('/users/index');
        $this->assertResponseOk();
    }

    /**
     * Test index method without authentication
     */
    public function testIndexUnauthenticated(): void
    {
        $this->get('/users/index');
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);
    }

    /**
     * Test add method - GET
     */
    public function testAddGet(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);

        $this->get('/users/add');
        $this->assertResponseOk();
    }

    /**
     * Test add method - POST with valid data
     */
    public function testAddPostValid(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'username' => 'newuser',
            'password' => 'password123',
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'active' => true,
        ];

        $this->post('/users/add', $data);

        $this->assertRedirect(['action' => 'index']);
        $this->assertFlashMessage('Usuário salvo com sucesso.');
    }

    /**
     * Test edit method - GET
     */
    public function testEditGet(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);

        $this->get('/users/edit/1');
        $this->assertResponseOk();
    }

    /**
     * Test delete method
     */
    public function testDelete(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
            ]
        ]);

        $this->enableCsrfToken();
        $this->enableSecurityToken();

        // Note: Can't delete user 1 (admin) in real scenario
        // This is just to test the method works
        $this->post('/users/delete/1');

        // Should redirect to index
        $this->assertRedirect(['action' => 'index']);
    }
}
