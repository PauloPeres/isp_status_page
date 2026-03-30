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
