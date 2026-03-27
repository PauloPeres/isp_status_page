<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api\V1;

use App\Service\ApiKeyService;
use App\Tenant\TenantContext;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\Api\V1\MonitorsController Test Case
 */
class MonitorsControllerTest extends TestCase
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
        'app.Monitors',
        'app.MonitorChecks',
        'app.Incidents',
        'app.ApiKeys',
        'app.AlertRules',
        'app.AlertLogs',
    ];

    /**
     * Plain API key with read+write permissions.
     */
    private string $writeApiKey;

    /**
     * Plain API key with read-only permissions.
     */
    private string $readApiKey;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Generate real API keys so the middleware can validate them.
        $service = new ApiKeyService();

        $result = $service->generate(1, 1, 'Test Write Key', ['read', 'write']);
        $this->writeApiKey = $result['key'];

        $result = $service->generate(1, 1, 'Test Read Key', ['read']);
        $this->readApiKey = $result['key'];

        // Reset tenant context after key generation (it may have been set).
        TenantContext::reset();
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void
    {
        TenantContext::reset();
        parent::tearDown();
    }

    /**
     * Set up an API request with the given Bearer token.
     *
     * @param string $token The plain API key.
     * @return void
     */
    private function authenticateApi(string $token): void
    {
        $this->configRequest([
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Test listing monitors returns JSON with success structure.
     */
    public function testIndexReturnsJson(): void
    {
        $this->authenticateApi($this->readApiKey);

        $this->get('/api/v1/monitors');

        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
        $this->assertNotEmpty($body['data']);
    }

    /**
     * Test creating a monitor with write permission succeeds.
     */
    public function testAddWithWritePermission(): void
    {
        $data = [
            'name' => 'New API Monitor',
            'type' => 'http',
            'configuration' => '{"url":"https://test.example.com"}',
            'check_interval' => 60,
            'timeout' => 10,
            'retry_count' => 3,
            'status' => 'unknown',
            'active' => true,
            'visible_on_status_page' => true,
            'display_order' => 0,
        ];

        $this->configRequest([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->writeApiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'input' => json_encode($data),
        ]);

        $this->post('/api/v1/monitors', json_encode($data));

        $this->assertResponseCode(201);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals('New API Monitor', $body['data']['name']);
    }

    /**
     * Test creating a monitor with read-only permission is rejected.
     */
    public function testAddWithReadOnlyPermissionRejected(): void
    {
        $this->authenticateApi($this->readApiKey);

        $data = [
            'name' => 'Should Not Be Created',
            'type' => 'http',
            'check_interval' => 60,
            'timeout' => 10,
            'retry_count' => 3,
            'status' => 'unknown',
            'active' => true,
            'visible_on_status_page' => true,
            'display_order' => 0,
        ];

        $this->post('/api/v1/monitors', json_encode($data));

        $this->assertResponseCode(403);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertStringContainsString('permissions', $body['message']);
    }

    /**
     * Test viewing a single monitor returns the correct data.
     */
    public function testViewSingleMonitor(): void
    {
        $this->authenticateApi($this->readApiKey);

        $this->get('/api/v1/monitors/1');

        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals(1, $body['data']['id']);
        $this->assertEquals('Website', $body['data']['name']);
    }

    /**
     * Test viewing a non-existent monitor returns 404.
     */
    public function testViewNonExistentMonitor(): void
    {
        $this->authenticateApi($this->readApiKey);

        $this->get('/api/v1/monitors/9999');

        $this->assertResponseCode(404);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['error']);
        $this->assertStringContainsString('not found', $body['message']);
    }

    /**
     * Test deleting a monitor with write permission.
     */
    public function testDeleteWithWritePermission(): void
    {
        $this->authenticateApi($this->writeApiKey);

        $this->delete('/api/v1/monitors/3');

        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertTrue($body['data']['deleted']);
    }

    /**
     * Test listing checks for a monitor.
     */
    public function testChecksEndpoint(): void
    {
        $this->authenticateApi($this->readApiKey);

        $this->get('/api/v1/monitors/1/checks');

        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
    }

    /**
     * Test pausing a monitor.
     */
    public function testPauseMonitor(): void
    {
        $this->authenticateApi($this->writeApiKey);

        $this->post('/api/v1/monitors/1/pause');

        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertFalse($body['data']['active']);
    }

    /**
     * Test resuming a monitor.
     */
    public function testResumeMonitor(): void
    {
        $this->authenticateApi($this->writeApiKey);

        $this->post('/api/v1/monitors/3/resume');

        $this->assertResponseOk();
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertTrue($body['data']['active']);
    }

    /**
     * Test that unauthenticated request returns 401.
     */
    public function testUnauthenticatedReturns401(): void
    {
        $this->get('/api/v1/monitors');

        $this->assertResponseCode(401);
        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['error']);
    }
}
