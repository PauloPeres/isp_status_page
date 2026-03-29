<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\WebhookDeliveryService;
use Cake\TestSuite\TestCase;

/**
 * App\Service\WebhookDeliveryService Test Case
 */
class WebhookDeliveryServiceTest extends TestCase
{
    protected WebhookDeliveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WebhookDeliveryService();
    }

    protected function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    public function testSignReturnsHmacSha256(): void
    {
        $payload = '{"event":"test","data":{}}';
        $secret = 'my-webhook-secret';

        $signature = $this->service->sign($payload, $secret);

        $this->assertNotEmpty($signature);
        // HMAC-SHA256 produces a 64-char hex string
        $this->assertEquals(64, strlen($signature));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $signature);
    }

    public function testSignIsDeterministic(): void
    {
        $payload = '{"event":"monitor.down"}';
        $secret = 'secret123';

        $sig1 = $this->service->sign($payload, $secret);
        $sig2 = $this->service->sign($payload, $secret);

        $this->assertEquals($sig1, $sig2);
    }

    public function testSignDiffersWithDifferentSecrets(): void
    {
        $payload = '{"event":"test"}';

        $sig1 = $this->service->sign($payload, 'secret-a');
        $sig2 = $this->service->sign($payload, 'secret-b');

        $this->assertNotEquals($sig1, $sig2);
    }

    public function testSignDiffersWithDifferentPayloads(): void
    {
        $secret = 'shared-secret';

        $sig1 = $this->service->sign('payload-1', $secret);
        $sig2 = $this->service->sign('payload-2', $secret);

        $this->assertNotEquals($sig1, $sig2);
    }

    public function testSignMatchesPhpHmac(): void
    {
        $payload = 'test-payload';
        $secret = 'test-secret';

        $expected = hash_hmac('sha256', $payload, $secret);
        $actual = $this->service->sign($payload, $secret);

        $this->assertEquals($expected, $actual);
    }

    public function testMaxAttemptsConstant(): void
    {
        $this->assertEquals(5, WebhookDeliveryService::MAX_ATTEMPTS);
    }

    public function testRequestTimeoutConstant(): void
    {
        $this->assertEquals(10, WebhookDeliveryService::REQUEST_TIMEOUT);
    }
}
