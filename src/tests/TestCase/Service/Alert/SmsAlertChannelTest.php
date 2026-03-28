<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Service\Alert\SmsAlertChannel;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * App\Service\Alert\SmsAlertChannel Test Case
 */
class SmsAlertChannelTest extends TestCase
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
     * @return \App\Service\Alert\SmsAlertChannel
     */
    protected function createConfiguredChannel(): SmsAlertChannel
    {
        // Set environment variables for Twilio configuration
        $_SERVER['TWILIO_SID'] = 'AC_test_sid_123';
        $_ENV['TWILIO_SID'] = 'AC_test_sid_123';
        $_SERVER['TWILIO_AUTH_TOKEN'] = 'test_auth_token_456';
        $_ENV['TWILIO_AUTH_TOKEN'] = 'test_auth_token_456';
        $_SERVER['TWILIO_FROM_NUMBER'] = '+15551234567';
        $_ENV['TWILIO_FROM_NUMBER'] = '+15551234567';

        return new SmsAlertChannel($this->mockClient);
    }

    /**
     * Create an unconfigured channel (no env vars)
     *
     * @return \App\Service\Alert\SmsAlertChannel
     */
    protected function createUnconfiguredChannel(): SmsAlertChannel
    {
        unset($_SERVER['TWILIO_SID'], $_ENV['TWILIO_SID']);
        unset($_SERVER['TWILIO_AUTH_TOKEN'], $_ENV['TWILIO_AUTH_TOKEN']);
        unset($_SERVER['TWILIO_FROM_NUMBER'], $_ENV['TWILIO_FROM_NUMBER']);

        return new SmsAlertChannel($this->mockClient);
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
        $rule->channel = 'sms';
        $rule->recipients = json_encode($recipients);

        return $rule;
    }

    /**
     * Test getType returns 'sms'
     *
     * @return void
     */
    public function testGetType(): void
    {
        $channel = $this->createUnconfiguredChannel();
        $this->assertSame('sms', $channel->getType());
    }

    /**
     * Test getName returns human-readable name
     *
     * @return void
     */
    public function testGetName(): void
    {
        $channel = $this->createUnconfiguredChannel();
        $this->assertSame('SMS Alert Channel', $channel->getName());
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
     * Test successful SMS send
     *
     * @return void
     */
    public function testSendSuccessful(): void
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
                    return $data['To'] === '+5511999999999'
                        && $data['From'] === '+15551234567'
                        && str_contains($data['Body'], 'Test Server')
                        && str_contains($data['Body'], 'DOWN');
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
     * Test SMS send with HTTP error
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
        $mockResponse->method('getStringBody')->willReturn('Invalid phone number');

        $this->mockClient->method('post')->willReturn($mockResponse);

        $result = $channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertCount(1, $result['results']);
        $this->assertSame('failed', $result['results'][0]['status']);
        $this->assertStringContainsString('400', $result['results'][0]['error']);
    }

    /**
     * Test SMS send with exception
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
     * Test SMS send to multiple recipients with partial failure
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
     * Test formatMessage for down incident
     *
     * @return void
     */
    public function testFormatMessageDown(): void
    {
        $channel = $this->createUnconfiguredChannel();
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $message = $channel->formatMessage($monitor, $incident);

        $this->assertStringContainsString('[ALERT]', $message);
        $this->assertStringContainsString('Test Server', $message);
        $this->assertStringContainsString('DOWN', $message);
        $this->assertStringContainsString('14:30', $message);
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

        $this->assertStringContainsString('[RESOLVED]', $message);
        $this->assertStringContainsString('Test Server', $message);
        $this->assertStringContainsString('UP', $message);
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
            $_SERVER['TWILIO_FROM_NUMBER'], $_ENV['TWILIO_FROM_NUMBER']
        );
        parent::tearDown();
    }
}
