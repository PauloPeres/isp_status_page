<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\SuperAdmin;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SuperAdmin\UsersController Test Case
 *
 * @uses \App\Controller\SuperAdmin\UsersController
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
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.ApiKeys',
    ];

    /**
     * Test that unauthenticated users are rejected with 403
     */
    public function testIndexUnauthenticated(): void
    {
        $this->get('/super-admin/users');
        $this->assertResponseCode(403);
    }

    /**
     * Test that a non-super-admin user gets 403
     */
    public function testIndexRequiresSuperAdmin(): void
    {
        $this->session([
            'Auth' => [
                'id' => 2,
                'username' => 'user',
                'active' => true,
            ],
        ]);

        $this->get('/super-admin/users');
        $this->assertResponseCode(403);
    }

    /**
     * Test that a super admin can access the users index
     */
    public function testIndexSuperAdmin(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
                'is_super_admin' => true,
            ],
        ]);

        $this->get('/super-admin/users');
        $this->assertResponseOk();
    }

    /**
     * Test that a super admin can view a user
     */
    public function testViewSuperAdmin(): void
    {
        $this->session([
            'Auth' => [
                'id' => 1,
                'username' => 'admin',
                'active' => true,
                'is_super_admin' => true,
            ],
        ]);

        $this->get('/super-admin/users/1');
        $this->assertResponseOk();
    }

    /**
     * Test that a non-super-admin user cannot view a user
     */
    public function testViewRequiresSuperAdmin(): void
    {
        $this->session([
            'Auth' => [
                'id' => 2,
                'username' => 'user',
                'active' => true,
            ],
        ]);

        $this->get('/super-admin/users/1');
        $this->assertResponseCode(403);
    }
}
