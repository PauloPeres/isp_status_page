<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Service\Alert\SlackAlertChannel;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * SlackAlertChannel Test Case
 */
class SlackAlertChannelTest extends TestCase
{
    /**
     * @var \App\Service\Alert\SlackAlertChannel
     */
    protected SlackAlertChannel $channel;

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
        $this->channel = new SlackAlertChannel($this->mockClient);
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
     * Test getType returns slack
     */
    public function testGetType(): void
    {
        $this->assertEquals('slack', $this->channel->getType());
    }

    /**
     * Test getName returns correct name
     */
    public function testGetName(): void
    {
        $this->assertEquals('Slack Alert Channel', $this->channel->getName());
    }

    /**
     * Test buildPayload for a down incident
     */
    public function testBuildPayloadDown(): void
    {
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $payload = $this->channel->buildPayload($monitor, $incident);

        $this->assertArrayHasKey('text', $payload);
        $this->assertArrayHasKey('attachments', $payload);
        $this->assertStringContainsString('DOWN', $payload['text']);
        $this->assertStringContainsString('Test Monitor', $payload['text']);
        $this->assertEquals('#E53935', $payload['attachments'][0]['color']);
    }

    /**
     * Test buildPayload for an up (resolved) incident
     */
    public function testBuildPayloadUp(): void
    {
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(false);

        $payload = $this->channel->buildPayload($monitor, $incident);

        $this->assertStringContainsString('UP', $payload['text']);
        $this->assertEquals('#43A047', $payload['attachments'][0]['color']);
    }

    /**
     * Test send calls correct webhook URL
     */
    public function testSendCallsCorrectUrl(): void
    {
        $webhookUrl = 'https://hooks.slack.com/services/T00/B00/xxx';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(true);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo($webhookUrl),
                $this->isType('string'),
                $this->isType('array')
            )
            ->willReturn($mockResponse);

        $rule = $this->createAlertRule([$webhookUrl]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['results']);
        $this->assertEquals('sent', $result['results'][0]['status']);
        $this->assertEquals($webhookUrl, $result['results'][0]['recipient']);
    }

    /**
     * Test send handles HTTP failure
     */
    public function testSendHandlesHttpFailure(): void
    {
        $webhookUrl = 'https://hooks.slack.com/services/T00/B00/xxx';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(false);
        $mockResponse->method('getStatusCode')->willReturn(500);
        $mockResponse->method('getStringBody')->willReturn('Internal Server Error');

        $this->mockClient->method('post')->willReturn($mockResponse);

        $rule = $this->createAlertRule([$webhookUrl]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['results'][0]['status']);
        $this->assertStringContainsString('500', $result['results'][0]['error']);
    }

    /**
     * Test send handles exception
     */
    public function testSendHandlesException(): void
    {
        $webhookUrl = 'https://hooks.slack.com/services/T00/B00/xxx';

        $this->mockClient->method('post')->willThrowException(new \Exception('Connection timeout'));

        $rule = $this->createAlertRule([$webhookUrl]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['results'][0]['status']);
        $this->assertEquals('Connection timeout', $result['results'][0]['error']);
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
            'https://hooks.slack.com/services/T00/B00/aaa',
            'https://hooks.slack.com/services/T00/B00/bbb',
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
        $rule->channel = 'slack';
        $rule->trigger_on = AlertRule::TRIGGER_ON_DOWN;
        $rule->throttle_minutes = 5;
        $rule->recipients = json_encode($recipients);
        $rule->active = true;

        return $rule;
    }
}
