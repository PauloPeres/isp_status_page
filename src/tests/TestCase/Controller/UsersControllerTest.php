<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
    ];

    public function testLoginGet(): void
    {
        $this->get('/users/login');
        $this->assertResponseOk();
        $this->assertResponseContains('ISP Status');
    }

    public function testLoginPostValid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/users/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);

        $this->assertRedirectContains('/app/dashboard');
        $this->assertSession('admin', 'Auth.username');
    }

    public function testLoginPostInvalid(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/users/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $this->assertResponseOk();
    }

    public function testLogout(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true],
            'current_organization_id' => 1,
        ]);

        $this->get('/users/logout');
        $this->assertRedirect(['controller' => 'Users', 'action' => 'login']);
        $this->assertSession(null, 'Auth');
    }

    public function testIndexRedirectsToAngular(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true],
            'current_organization_id' => 1,
        ]);
        $this->get('/users/index');
        $this->assertRedirectContains('/app/users');
    }

    public function testAddRedirectsToAngular(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true],
            'current_organization_id' => 1,
        ]);
        $this->get('/users/add');
        $this->assertRedirectContains('/app/users/new');
    }

    public function testEditRedirectsToAngular(): void
    {
        $this->session([
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true],
            'current_organization_id' => 1,
        ]);
        $this->get('/users/edit/1');
        $this->assertRedirectContains('/app/profile');
    }
}
