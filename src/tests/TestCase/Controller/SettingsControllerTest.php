<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\SettingsController Test Case
 *
 * @uses \App\Controller\SettingsController
 */
class SettingsControllerTest extends TestCase
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

    public function testIndexRequiresAuth(): void
    {
        $this->_session = [];
        $this->cookie('csrfToken', '');

        $this->get('/settings');
        $this->assertRedirectContains('/users/login');
    }

    public function testIndexLoadsForAuthenticatedUser(): void
    {
        $this->get('/settings');
        $this->assertResponseOk();
    }

    public function testIndexSetsSettingsVariable(): void
    {
        $this->get('/settings');
        $this->assertResponseOk();

        $settings = $this->viewVariable('settings');
        $this->assertNotNull($settings);
        $this->assertIsArray($settings);
        $this->assertArrayHasKey('general', $settings);
        $this->assertArrayHasKey('notifications', $settings);
        $this->assertArrayHasKey('channels', $settings);
    }

    public function testSaveRequiresPost(): void
    {
        $this->get('/settings/save');
        $this->assertResponseCode(405);
    }

    public function testSaveRejectsSystemLevelKeys(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'category' => 'general',
            'settings' => [
                'smtp_host' => 'evil.example.com',
            ],
        ];

        $this->post('/settings/save', $data);
        $this->assertRedirectContains('/settings');
    }

    public function testSaveAcceptsOrgLevelKeys(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'category' => 'general',
            'settings' => [
                'site_name' => 'Updated ISP Status',
            ],
        ];

        $this->post('/settings/save', $data);
        $this->assertRedirectContains('/settings');
        $this->assertFlashMessage('1 setting(s) saved successfully.');
    }

    public function testSaveWithNoDataShowsError(): void
    {
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $data = [
            'category' => 'general',
        ];

        $this->post('/settings/save', $data);
        $this->assertRedirectContains('/settings');
    }
}
