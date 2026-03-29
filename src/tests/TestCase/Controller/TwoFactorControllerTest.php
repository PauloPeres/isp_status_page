<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

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
            'Auth' => ['id' => 1, 'username' => 'admin', 'active' => true, 'organization_id' => 1],
            'current_organization_id' => 1,
        ]);
    }

    public function testSetupRedirectsToAngular(): void
    {
        $this->get('/two-factor/setup');
        $this->assertRedirectContains('/app/settings/security');
    }

    public function testVerifyWithoutPendingUserId(): void
    {
        $this->_session = [];
        $this->cookie('csrfToken', '');
        $this->get('/two-factor/verify');
        $this->assertRedirectContains('/users/login');
    }

    public function testRecoveryCodesRedirectsToAngular(): void
    {
        $this->get('/two-factor/recovery-codes');
        $this->assertRedirectContains('/app/settings/security');
    }
}
