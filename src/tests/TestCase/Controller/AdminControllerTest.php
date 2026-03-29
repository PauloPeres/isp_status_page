<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AdminControllerTest extends TestCase
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
        $this->get('/admin');
        $this->assertRedirectContains('/app/dashboard');
    }
}
