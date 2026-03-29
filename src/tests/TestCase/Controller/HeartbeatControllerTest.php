<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\HeartbeatController Test Case
 *
 * @uses \App\Controller\HeartbeatController
 */
class HeartbeatControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.Heartbeats',
    ];

    public function testPingWithValidTokenReturnsOk(): void
    {
        $token = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';
        $this->get('/heartbeat/' . $token);
        $this->assertResponseOk();

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertTrue($body['ok']);
    }

    public function testPingWithInvalidTokenReturns404(): void
    {
        $token = '0000000000000000000000000000000000000000000000000000000000000000';
        $this->get('/heartbeat/' . $token);
        $this->assertResponseCode(404);

        $body = json_decode((string)$this->_response->getBody(), true);
        $this->assertFalse($body['ok']);
        $this->assertEquals('Heartbeat not found', $body['error']);
    }

    public function testPingUpdatesLastPingAt(): void
    {
        $token = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';

        $heartbeatsTable = $this->getTableLocator()->get('Heartbeats');
        $before = $heartbeatsTable->get(1);
        $beforePing = $before->last_ping_at;

        $this->get('/heartbeat/' . $token);
        $this->assertResponseOk();

        $after = $heartbeatsTable->get(1);
        $this->assertNotEquals($beforePing, $after->last_ping_at);
    }

    public function testPingIsPublicNoAuthRequired(): void
    {
        // No session set at all
        $token = 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2';
        $this->get('/heartbeat/' . $token);
        $this->assertResponseOk();
    }
}
