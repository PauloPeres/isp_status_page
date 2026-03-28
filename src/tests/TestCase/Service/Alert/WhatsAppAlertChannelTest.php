<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Service\Alert\WhatsAppAlertChannel;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * App\Service\Alert\WhatsAppAlertChannel Test Case
 */
class WhatsAppAlertChannelTest extends TestCase
{
    /**
     * @var \Cake\Http\Client&\PHPUnit\Framework\MockObject\MockObject
     */
    protected Client $mockClient;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = $this->createMock(Client::class);
    }

    /**
     * Create a channel instance with test credentials injected
     *
     * @return \App\Service\Alert\WhatsAppAlertChannel
     */
    protected function createConfiguredChannel(): WhatsAppAlertChannel
    {
        $_SERVER['TWILIO_SID'] = 'AC_test_sid_123';
        $_ENV['TWILIO_SID'] = 'AC_test_sid_123';
        $_SERVER['TWILIO_AUTH_TOKEN'] = 'test_auth_token_456';
        $_ENV['TWILIO_AUTH_TOKEN'] = 'test_auth_token_456';
        $_SERVER['TWILIO_WHATSAPP_NUMBER'] = '+15559876543';
        $_ENV['TWILIO_WHATSAPP_NUMBER'] = '+15559876543';

        return new WhatsAppAlertChannel($this->mockClient);
    }

    /**
     * Create an unconfigured channel (no env vars)
     *
     * @return \App\Service\Alert\WhatsAppAlertChannel
     */
    protected function createUnconfiguredChannel(): WhatsAppAlertChannel
    {
        unset($_SERVER['TWILIO_SID'], $_ENV['TWILIO_SID']);
        unset($_SERVER['TWILIO_AUTH_TOKEN'], $_ENV['TWILIO_AUTH_TOKEN']);
        unset($_SERVER['TWILIO_WHATSAPP_NUMBER'], $_ENV['TWILIO_WHATSAPP_NUMBER']);

        return new WhatsAppAlertChannel($this->mockClient);
    }

    /**
     * Create a mock monitor entity
     *
     * @return \App\Model\Entity\Monitor
     */
    protected function createMonitor(): Monitor
    {
        $monitor = new Monitor();
        $monitor->id = 1;
        $monitor->name = 'Test Server';
        $monitor->type = 'http';

        return $monitor;
    }

    /**
     * Create a mock incident entity
     *
     * @param bool $ongoing Whether the incident is ongoing (down)
     * @return \App\Model\Entity\Incident
     */
    protected function createIncident(bool $ongoing = true): Incident
    {
        $incident = new Incident();
        $incident->id = 10;
        $incident->title = 'Test Incident';
        $incident->started_at = new DateTime('2026-03-27 14:30:00');

        if ($ongoing) {
            $incident->status = 'investigating';
            $incident->resolved_at = null;
        } else {
            $incident->status = 'resolved';
            $incident->resolved_at = new DateTime('2026-03-27 15:00:00');
        }

        return $incident;
    }

    /**
     * Create a mock alert rule entity
     *
     * @param array $recipients Array of phone numbers
     * @return \App\Model\Entity\AlertRule
     */
    protected function createAlertRule(array $recipients = ['+5511999999999']): AlertRule
    {
        $rule = new AlertRule();
        $rule->id = 5;
        $rule->channel = 'whatsapp';
        $rule->recipients = json_encode($recipients);

        return $rule;
    }

    /**
     * Test getType returns 'whatsapp'
     *
     * @return void
     */
    public function testGetType(): void
    {
        $channel = $this->createUnconfiguredChannel();
        $this->assertSame('whatsapp', $channel->getType());
    }

    /**
     * Test getName returns human-readable name
     *
     * @return void
     */
    public function testGetName(): void
    {
        $channel = $this->createUnconfiguredChannel();
        $this->assertSame('WhatsApp Alert Channel', $channel->getName());
    }

    /**
     * Test isConfigured returns false when env vars are missing
     *
     * @return void
     */
    public function testIsConfiguredReturnsFalseWhenNotConfigured(): void
    {
        $channel = $this->createUnconfiguredChannel();
        $this->assertFalse($channel->isConfigured());
    }

    /**
     * Test isConfigured returns true when all env vars are set
     *
     * @return void
     */
    public function testIsConfiguredReturnsTrueWhenConfigured(): void
    {
        $channel = $this->createConfiguredChannel();
        $this->assertTrue($channel->isConfigured());
    }

    /**
     * Test send returns failure when not configured
     *
     * @return void
     */
    public function testSendReturnsFailureWhenNotConfigured(): void
    {
        $channel = $this->createUnconfiguredChannel();
        $rule = $this->createAlertRule();
        $monitor = $this->createMonitor();
        $incident = $this->createIncident();

        $result = $channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['results']);
    }

    /**
     * Test send returns failure when no recipients
     *
     * @return void
     */
    public function testSendReturnsFailureWhenNoRecipients(): void
    {
        $channel = $this->createConfiguredChannel();
        $rule = $this->createAlertRule([]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident();

        $result = $channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['results']);
    }

    /**
     * Test successful WhatsApp send with whatsapp: prefix on numbers
     *
     * @return void
     */
    public function testSendSuccessfulWithWhatsAppPrefix(): void
    {
        $channel = $this->createConfiguredChannel();
        $rule = $this->createAlertRule(['+5511999999999']);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident();

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getStatusCode')->willReturn(201);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                'https://api.twilio.com/2010-04-01/Accounts/AC_test_sid_123/Messages.json',
                $this->callback(function ($data) {
                    return $data['To'] === 'whatsapp:+5511999999999'
                        && $data['From'] === 'whatsapp:+15559876543'
                        && str_contains($data['Body'], 'Test Server');
                }),
                $this->callback(function ($options) {
                    return isset($options['auth'])
                        && $options['auth']['username'] === 'AC_test_sid_123'
                        && $options['auth']['password'] === 'test_auth_token_456';
                })
            )
            ->willReturn($mockResponse);

        $result = $channel->send($rule, $monitor, $incident);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['results']);
        $this->assertSame('sent', $result['results'][0]['status']);
        $this->assertSame('+5511999999999', $result['results'][0]['recipient']);
        $this->assertNull($result['results'][0]['error']);
    }

    /**
     * Test WhatsApp send with HTTP error
     *
     * @return void
     */
    public function testSendWithHttpError(): void
    {
        $channel = $this->createConfiguredChannel();
        $rule = $this->createAlertRule(['+5511999999999']);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident();

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getStatusCode')->willReturn(400);
        $mockResponse->method('getStringBody')->willReturn('Invalid WhatsApp number');

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertCount(1, $result['results']);
        $this->assertSame('failed', $result['results'][0]['status']);
        $this->assertStringContainsString('400', $result['results'][0]['error']);
    }

    /**
     * Test WhatsApp send with exception
     *
     * @return void
     */
    public function testSendWithException(): void
    {
        $channel = $this->createConfiguredChannel();
        $rule = $this->createAlertRule(['+5511999999999']);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident();

        $this->mockClient->method('post')
            ->willThrowException(new \RuntimeException('Connection timeout'));

        $result = $channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertCount(1, $result['results']);
        $this->assertSame('failed', $result['results'][0]['status']);
        $this->assertSame('Connection timeout', $result['results'][0]['error']);
    }

    /**
     * Test WhatsApp send to multiple recipients with partial failure
     *
     * @return void
     */
    public function testSendToMultipleRecipientsWithPartialFailure(): void
    {
        $channel = $this->createConfiguredChannel();
        $rule = $this->createAlertRule(['+5511999999999', '+5511888888888']);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident();

        $successResponse = $this->createMock(Response::class);
        $successResponse->method('getStatusCode')->willReturn(201);

        $failResponse = $this->createMock(Response::class);
        $failResponse->method('getStatusCode')->willReturn(400);
        $failResponse->method('getStringBody')->willReturn('Invalid number');

        $this->mockClient->expects($this->exactly(2))
            ->method('post')
            ->willReturnOnConsecutiveCalls($successResponse, $failResponse);

        $result = $channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertCount(2, $result['results']);
        $this->assertSame('sent', $result['results'][0]['status']);
        $this->assertSame('failed', $result['results'][1]['status']);
    }

    /**
     * Test formatMessage for down incident includes emoji and monitor name
     *
     * @return void
     */
    public function testFormatMessageDown(): void
    {
        $channel = $this->createUnconfiguredChannel();
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $message = $channel->formatMessage($monitor, $incident);

        $this->assertStringContainsString('DOWN', $message);
        $this->assertStringContainsString('Test Server', $message);
        $this->assertStringContainsString('Monitor:', $message);
        $this->assertStringContainsString('14:30', $message);
        $this->assertStringContainsString('Check your status page', $message);
    }

    /**
     * Test formatMessage for resolved incident
     *
     * @return void
     */
    public function testFormatMessageResolved(): void
    {
        $channel = $this->createUnconfiguredChannel();
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(false);

        $message = $channel->formatMessage($monitor, $incident);

        $this->assertStringContainsString('RESOLVED', $message);
        $this->assertStringContainsString('Test Server', $message);
        $this->assertStringContainsString('Monitor:', $message);
        $this->assertStringContainsString('Check your status page', $message);
    }

    /**
     * Test that WhatsApp messages are more formatted than SMS (contain newlines)
     *
     * @return void
     */
    public function testFormatMessageContainsNewlines(): void
    {
        $channel = $this->createUnconfiguredChannel();
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $message = $channel->formatMessage($monitor, $incident);

        $this->assertStringContainsString("\n", $message);
    }

    /**
     * Test formatMessage for down incident includes acknowledge URL when token exists
     *
     * @return void
     */
    public function testFormatMessageDownIncludesAcknowledgeUrl(): void
    {
        $_SERVER['APP_URL'] = 'https://status.example.com';
        $_ENV['APP_URL'] = 'https://status.example.com';

        $channel = $this->createUnconfiguredChannel();
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);
        $incident->acknowledgement_token = 'abc123token';

        $message = $channel->formatMessage($monitor, $incident);

        $this->assertStringContainsString('Acknowledge:', $message);
        $this->assertStringContainsString('https://status.example.com/incidents/acknowledge/10/abc123token', $message);
    }

    /**
     * Test formatMessage for down incident without token falls back to generic message
     *
     * @return void
     */
    public function testFormatMessageDownWithoutTokenNoAcknowledgeUrl(): void
    {
        $channel = $this->createUnconfiguredChannel();
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $message = $channel->formatMessage($monitor, $incident);

        $this->assertStringNotContainsString('Acknowledge:', $message);
        $this->assertStringContainsString('Check your status page for details.', $message);
    }

    /**
     * Test formatMessage for resolved incident does not include acknowledge URL
     *
     * @return void
     */
    public function testFormatMessageResolvedNoAcknowledgeUrl(): void
    {
        $_SERVER['APP_URL'] = 'https://status.example.com';
        $_ENV['APP_URL'] = 'https://status.example.com';

        $channel = $this->createUnconfiguredChannel();
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(false);
        $incident->acknowledgement_token = 'abc123token';

        $message = $channel->formatMessage($monitor, $incident);

        $this->assertStringNotContainsString('Acknowledge:', $message);
        $this->assertStringNotContainsString('/incidents/acknowledge/', $message);
    }

    /**
     * Clean up environment variables after each test
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset(
            $_SERVER['TWILIO_SID'], $_ENV['TWILIO_SID'],
            $_SERVER['TWILIO_AUTH_TOKEN'], $_ENV['TWILIO_AUTH_TOKEN'],
            $_SERVER['TWILIO_WHATSAPP_NUMBER'], $_ENV['TWILIO_WHATSAPP_NUMBER'],
            $_SERVER['APP_URL'], $_ENV['APP_URL']
        );
        parent::tearDown();
    }
}
