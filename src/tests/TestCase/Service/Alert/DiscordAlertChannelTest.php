<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Service\Alert\DiscordAlertChannel;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * DiscordAlertChannel Test Case
 */
class DiscordAlertChannelTest extends TestCase
{
    /**
     * @var \App\Service\Alert\DiscordAlertChannel
     */
    protected DiscordAlertChannel $channel;

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
        $this->channel = new DiscordAlertChannel($this->mockClient);
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
     * Test getType returns discord
     */
    public function testGetType(): void
    {
        $this->assertEquals('discord', $this->channel->getType());
    }

    /**
     * Test getName returns correct name
     */
    public function testGetName(): void
    {
        $this->assertEquals('Discord Alert Channel', $this->channel->getName());
    }

    /**
     * Test buildPayload for a down incident
     */
    public function testBuildPayloadDown(): void
    {
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $payload = $this->channel->buildPayload($monitor, $incident);

        $this->assertArrayHasKey('embeds', $payload);
        $this->assertCount(1, $payload['embeds']);
        $embed = $payload['embeds'][0];

        $this->assertStringContainsString('DOWN', $embed['title']);
        $this->assertStringContainsString('Test Monitor', $embed['title']);
        $this->assertEquals(0xE53935, $embed['color']);
        $this->assertEquals('ISP Status Monitor', $payload['username']);
    }

    /**
     * Test buildPayload for an up (resolved) incident
     */
    public function testBuildPayloadUp(): void
    {
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(false);

        $payload = $this->channel->buildPayload($monitor, $incident);

        $embed = $payload['embeds'][0];
        $this->assertStringContainsString('UP', $embed['title']);
        $this->assertEquals(0x43A047, $embed['color']);
    }

    /**
     * Test buildPayload includes all required fields
     */
    public function testBuildPayloadFields(): void
    {
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $payload = $this->channel->buildPayload($monitor, $incident);
        $embed = $payload['embeds'][0];

        $fieldNames = array_column($embed['fields'], 'name');
        $this->assertContains('Monitor', $fieldNames);
        $this->assertContains('Status', $fieldNames);
        $this->assertContains('Type', $fieldNames);
        $this->assertContains('Incident', $fieldNames);
        $this->assertArrayHasKey('timestamp', $embed);
        $this->assertArrayHasKey('footer', $embed);
    }

    /**
     * Test send calls correct webhook URL
     */
    public function testSendCallsCorrectUrl(): void
    {
        $webhookUrl = 'https://discord.com/api/webhooks/123456/abcdef';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(false);
        $mockResponse->method('getStatusCode')->willReturn(204);

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
    }

    /**
     * Test send handles HTTP failure
     */
    public function testSendHandlesHttpFailure(): void
    {
        $webhookUrl = 'https://discord.com/api/webhooks/123456/abcdef';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(false);
        $mockResponse->method('getStatusCode')->willReturn(400);
        $mockResponse->method('getStringBody')->willReturn('Bad Request');

        $this->mockClient->method('post')->willReturn($mockResponse);

        $rule = $this->createAlertRule([$webhookUrl]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['results'][0]['status']);
    }

    /**
     * Test send handles exception
     */
    public function testSendHandlesException(): void
    {
        $webhookUrl = 'https://discord.com/api/webhooks/123456/abcdef';

        $this->mockClient->method('post')->willThrowException(new \Exception('DNS resolution failed'));

        $rule = $this->createAlertRule([$webhookUrl]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['results'][0]['status']);
        $this->assertEquals('DNS resolution failed', $result['results'][0]['error']);
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
        $rule->channel = 'discord';
        $rule->trigger_on = AlertRule::TRIGGER_ON_DOWN;
        $rule->throttle_minutes = 5;
        $rule->recipients = json_encode($recipients);
        $rule->active = true;

        return $rule;
    }
}
