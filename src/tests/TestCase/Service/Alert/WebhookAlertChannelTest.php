<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Service\Alert\WebhookAlertChannel;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * WebhookAlertChannel Test Case
 */
class WebhookAlertChannelTest extends TestCase
{
    /**
     * @var \App\Service\Alert\WebhookAlertChannel
     */
    protected WebhookAlertChannel $channel;

    /**
     * @var \Cake\Http\Client&\PHPUnit\Framework\MockObject\MockObject
     */
    protected Client $mockClient;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = $this->createMock(Client::class);
        $this->channel = new WebhookAlertChannel($this->mockClient);
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        unset($this->channel, $this->mockClient);
        parent::tearDown();
    }

    /**
     * Test getType returns webhook
     */
    public function testGetType(): void
    {
        $this->assertEquals('webhook', $this->channel->getType());
    }

    /**
     * Test getName returns correct name
     */
    public function testGetName(): void
    {
        $this->assertEquals('Webhook Alert Channel', $this->channel->getName());
    }

    /**
     * Test buildPayload for a down incident
     */
    public function testBuildPayloadDown(): void
    {
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $payload = $this->channel->buildPayload($monitor, $incident);

        $this->assertEquals('monitor.down', $payload['event_type']);
        $this->assertEquals(1, $payload['monitor']['id']);
        $this->assertEquals('Test Monitor', $payload['monitor']['name']);
        $this->assertEquals('http', $payload['monitor']['type']);
        $this->assertEquals(1, $payload['incident']['id']);
        $this->assertEquals('Test Incident', $payload['incident']['title']);
        $this->assertEquals(Incident::STATUS_INVESTIGATING, $payload['incident']['status']);
        $this->assertArrayHasKey('started_at', $payload['incident']);
        $this->assertArrayHasKey('timestamp', $payload);
    }

    /**
     * Test buildPayload for an up (resolved) incident
     */
    public function testBuildPayloadUp(): void
    {
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(false);

        $payload = $this->channel->buildPayload($monitor, $incident);

        $this->assertEquals('monitor.up', $payload['event_type']);
    }

    /**
     * Test getSigningSecret with valid config
     */
    public function testGetSigningSecretValid(): void
    {
        $rule = $this->createAlertRule([]);
        $rule->template = json_encode(['webhook_secret' => 'my-secret-key']);

        $secret = $this->channel->getSigningSecret($rule);

        $this->assertEquals('my-secret-key', $secret);
    }

    /**
     * Test getSigningSecret with no config
     */
    public function testGetSigningSecretEmpty(): void
    {
        $rule = $this->createAlertRule([]);
        $rule->template = null;

        $this->assertNull($this->channel->getSigningSecret($rule));

        $rule->template = '';
        $this->assertNull($this->channel->getSigningSecret($rule));
    }

    /**
     * Test getSigningSecret with invalid JSON
     */
    public function testGetSigningSecretInvalidJson(): void
    {
        $rule = $this->createAlertRule([]);
        $rule->template = 'not-json';

        $this->assertNull($this->channel->getSigningSecret($rule));
    }

    /**
     * Test computeSignature produces valid HMAC
     */
    public function testComputeSignature(): void
    {
        $payload = '{"event_type":"monitor.down"}';
        $secret = 'test-secret';

        $signature = $this->channel->computeSignature($payload, $secret);

        $this->assertStringStartsWith('sha256=', $signature);
        $expectedHash = hash_hmac('sha256', $payload, $secret);
        $this->assertEquals('sha256=' . $expectedHash, $signature);
    }

    /**
     * Test send calls correct URL with signature header
     */
    public function testSendCallsCorrectUrlWithSignature(): void
    {
        $webhookUrl = 'https://example.com/webhook';
        $secret = 'my-webhook-secret';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(true);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo($webhookUrl),
                $this->isType('string'),
                $this->callback(function ($options) {
                    return isset($options['headers']['X-Signature-256'])
                        && str_starts_with($options['headers']['X-Signature-256'], 'sha256=');
                })
            )
            ->willReturn($mockResponse);

        $rule = $this->createAlertRule([$webhookUrl]);
        $rule->template = json_encode(['webhook_secret' => $secret]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['results']);
        $this->assertEquals('sent', $result['results'][0]['status']);
    }

    /**
     * Test send without signing secret omits X-Signature-256
     */
    public function testSendWithoutSecret(): void
    {
        $webhookUrl = 'https://example.com/webhook';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(true);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo($webhookUrl),
                $this->isType('string'),
                $this->callback(function ($options) {
                    return !isset($options['headers']['X-Signature-256']);
                })
            )
            ->willReturn($mockResponse);

        $rule = $this->createAlertRule([$webhookUrl]);
        $rule->template = null;
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertTrue($result['success']);
    }

    /**
     * Test send handles HTTP failure
     */
    public function testSendHandlesHttpFailure(): void
    {
        $webhookUrl = 'https://example.com/webhook';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(false);
        $mockResponse->method('getStatusCode')->willReturn(502);
        $mockResponse->method('getStringBody')->willReturn('Bad Gateway');

        $this->mockClient->method('post')->willReturn($mockResponse);

        $rule = $this->createAlertRule([$webhookUrl]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['results'][0]['status']);
        $this->assertStringContainsString('502', $result['results'][0]['error']);
    }

    /**
     * Test send handles exception
     */
    public function testSendHandlesException(): void
    {
        $webhookUrl = 'https://example.com/webhook';

        $this->mockClient->method('post')->willThrowException(new \Exception('Connection refused'));

        $rule = $this->createAlertRule([$webhookUrl]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['results'][0]['status']);
        $this->assertEquals('Connection refused', $result['results'][0]['error']);
    }

    /**
     * Test send with no recipients
     */
    public function testSendWithNoRecipients(): void
    {
        $rule = $this->createAlertRule([]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['results']);
    }

    /**
     * Test send with multiple webhook URLs
     */
    public function testSendWithMultipleWebhooks(): void
    {
        $urls = [
            'https://example.com/webhook1',
            'https://example.com/webhook2',
        ];

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(true);

        $this->mockClient->method('post')->willReturn($mockResponse);

        $rule = $this->createAlertRule($urls);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['results']);
    }

    /**
     * Create a Monitor entity for testing
     *
     * @return \App\Model\Entity\Monitor
     */
    private function createMonitor(): Monitor
    {
        $monitor = new Monitor();
        $monitor->id = 1;
        $monitor->name = 'Test Monitor';
        $monitor->type = 'http';
        $monitor->status = Monitor::STATUS_DOWN;

        return $monitor;
    }

    /**
     * Create an Incident entity for testing
     *
     * @param bool $ongoing Whether the incident is ongoing
     * @return \App\Model\Entity\Incident
     */
    private function createIncident(bool $ongoing): Incident
    {
        $incident = new Incident();
        $incident->id = 1;
        $incident->title = 'Test Incident';
        $incident->status = $ongoing ? Incident::STATUS_INVESTIGATING : Incident::STATUS_RESOLVED;
        $incident->started_at = new DateTime('2026-03-27 10:00:00');

        return $incident;
    }

    /**
     * Create an AlertRule entity for testing
     *
     * @param array $recipients Webhook URLs
     * @return \App\Model\Entity\AlertRule
     */
    private function createAlertRule(array $recipients): AlertRule
    {
        $rule = new AlertRule();
        $rule->id = 1;
        $rule->monitor_id = 1;
        $rule->channel = 'webhook';
        $rule->trigger_on = AlertRule::TRIGGER_ON_DOWN;
        $rule->throttle_minutes = 5;
        $rule->recipients = json_encode($recipients);
        $rule->template = null;
        $rule->active = true;

        return $rule;
    }
}
