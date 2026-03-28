<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Service\Alert\TelegramAlertChannel;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;

/**
 * TelegramAlertChannel Test Case
 */
class TelegramAlertChannelTest extends TestCase
{
    /**
     * @var \App\Service\Alert\TelegramAlertChannel
     */
    protected TelegramAlertChannel $channel;

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
        $this->channel = new TelegramAlertChannel($this->mockClient);
    }

    /**
     * tearDown method
     */
    protected function tearDown(): void
    {
        unset($this->channel, $this->mockClient);
        unset($_SERVER['APP_URL'], $_ENV['APP_URL']);
        parent::tearDown();
    }

    /**
     * Test getType returns telegram
     */
    public function testGetType(): void
    {
        $this->assertEquals('telegram', $this->channel->getType());
    }

    /**
     * Test getName returns correct name
     */
    public function testGetName(): void
    {
        $this->assertEquals('Telegram Alert Channel', $this->channel->getName());
    }

    /**
     * Test buildMessage for a down incident
     */
    public function testBuildMessageDown(): void
    {
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $message = $this->channel->buildMessage($monitor, $incident);

        $this->assertStringContainsString('<b>Monitor DOWN:', $message);
        $this->assertStringContainsString('Test Monitor', $message);
        $this->assertStringContainsString('<b>Status:</b> DOWN', $message);
        $this->assertStringContainsString('<b>Type:</b> http', $message);
        $this->assertStringContainsString('<b>Incident:</b> #1', $message);
    }

    /**
     * Test buildMessage for an up (resolved) incident
     */
    public function testBuildMessageUp(): void
    {
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(false);

        $message = $this->channel->buildMessage($monitor, $incident);

        $this->assertStringContainsString('<b>Monitor UP:', $message);
        $this->assertStringContainsString('<b>Status:</b> UP', $message);
    }

    /**
     * Test buildMessage escapes HTML in monitor name
     */
    public function testBuildMessageEscapesHtml(): void
    {
        $monitor = $this->createMonitor();
        $monitor->name = 'Test <script>alert(1)</script>';
        $incident = $this->createIncident(true);

        $message = $this->channel->buildMessage($monitor, $incident);

        $this->assertStringNotContainsString('<script>', $message);
        $this->assertStringContainsString('&lt;script&gt;', $message);
    }

    /**
     * Test parseRecipient with valid array
     */
    public function testParseRecipientValidArray(): void
    {
        $result = $this->channel->parseRecipient([
            'bot_token' => '123:ABC',
            'chat_id' => '-100123',
        ]);

        $this->assertNotNull($result);
        $this->assertEquals('123:ABC', $result['bot_token']);
        $this->assertEquals('-100123', $result['chat_id']);
    }

    /**
     * Test parseRecipient with valid JSON string
     */
    public function testParseRecipientValidJsonString(): void
    {
        $result = $this->channel->parseRecipient('{"bot_token": "123:ABC", "chat_id": "-100123"}');

        $this->assertNotNull($result);
        $this->assertEquals('123:ABC', $result['bot_token']);
    }

    /**
     * Test parseRecipient with invalid data
     */
    public function testParseRecipientInvalid(): void
    {
        $this->assertNull($this->channel->parseRecipient('not-json'));
        $this->assertNull($this->channel->parseRecipient(['bot_token' => '123']));
        $this->assertNull($this->channel->parseRecipient(['chat_id' => '123']));
        $this->assertNull($this->channel->parseRecipient(42));
    }

    /**
     * Test send calls correct Telegram API URL
     */
    public function testSendCallsCorrectUrl(): void
    {
        $botToken = '123456:ABC-DEF';
        $chatId = '-100123456';
        $expectedUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(true);

        $this->mockClient->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo($expectedUrl),
                $this->callback(function ($body) use ($chatId) {
                    $data = json_decode($body, true);

                    return $data['chat_id'] === $chatId && $data['parse_mode'] === 'HTML';
                }),
                $this->isType('array')
            )
            ->willReturn($mockResponse);

        $rule = $this->createAlertRule([
            ['bot_token' => $botToken, 'chat_id' => $chatId],
        ]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['results']);
        $this->assertEquals('sent', $result['results'][0]['status']);
        $this->assertEquals("chat:{$chatId}", $result['results'][0]['recipient']);
    }

    /**
     * Test send handles Telegram API error
     */
    public function testSendHandlesApiError(): void
    {
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(false);
        $mockResponse->method('getStatusCode')->willReturn(403);
        $mockResponse->method('getStringBody')->willReturn('{"ok":false,"description":"Forbidden: bot was blocked by the user"}');

        $this->mockClient->method('post')->willReturn($mockResponse);

        $rule = $this->createAlertRule([
            ['bot_token' => '123:ABC', 'chat_id' => '-100123'],
        ]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['results'][0]['status']);
        $this->assertStringContainsString('Forbidden', $result['results'][0]['error']);
    }

    /**
     * Test send handles invalid recipient format
     */
    public function testSendHandlesInvalidRecipient(): void
    {
        $rule = $this->createAlertRule(['invalid-recipient']);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['results'][0]['status']);
        $this->assertStringContainsString('Invalid recipient format', $result['results'][0]['error']);
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
     * Test send handles exception
     */
    public function testSendHandlesException(): void
    {
        $this->mockClient->method('post')->willThrowException(new \Exception('Network error'));

        $rule = $this->createAlertRule([
            ['bot_token' => '123:ABC', 'chat_id' => '-100123'],
        ]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertFalse($result['success']);
        $this->assertEquals('failed', $result['results'][0]['status']);
        $this->assertEquals('Network error', $result['results'][0]['error']);
    }

    /**
     * Test send includes inline keyboard button for down alerts with token
     */
    public function testSendIncludesInlineKeyboardForDownAlert(): void
    {
        $_SERVER['APP_URL'] = 'https://status.example.com';
        $_ENV['APP_URL'] = 'https://status.example.com';

        $botToken = '123456:ABC-DEF';
        $chatId = '-100123456';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(true);

        $capturedBody = null;
        $this->mockClient->expects($this->once())
            ->method('post')
            ->willReturnCallback(function ($url, $body, $options) use ($mockResponse, &$capturedBody) {
                $capturedBody = $body;

                return $mockResponse;
            });

        $rule = $this->createAlertRule([
            ['bot_token' => $botToken, 'chat_id' => $chatId],
        ]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);
        $incident->acknowledgement_token = 'testtoken123';

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertTrue($result['success']);
        $this->assertNotNull($capturedBody);

        $data = json_decode($capturedBody, true);
        $this->assertArrayHasKey('reply_markup', $data);

        $replyMarkup = $data['reply_markup'];
        $this->assertStringContainsString('inline_keyboard', $replyMarkup);
        $this->assertStringContainsString('Acknowledge', $replyMarkup);

        // Decode the nested JSON to verify the URL (avoids JSON escape issues)
        $keyboard = json_decode($replyMarkup, true);
        $this->assertNotNull($keyboard);
        $this->assertArrayHasKey('inline_keyboard', $keyboard);
        $this->assertSame(
            'https://status.example.com/incidents/acknowledge/1/testtoken123',
            $keyboard['inline_keyboard'][0][0]['url']
        );
    }

    /**
     * Test send does not include inline keyboard for resolved alerts
     */
    public function testSendNoInlineKeyboardForResolvedAlert(): void
    {
        $_SERVER['APP_URL'] = 'https://status.example.com';
        $_ENV['APP_URL'] = 'https://status.example.com';

        $botToken = '123456:ABC-DEF';
        $chatId = '-100123456';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(true);

        $capturedBody = null;
        $this->mockClient->expects($this->once())
            ->method('post')
            ->willReturnCallback(function ($url, $body, $options) use ($mockResponse, &$capturedBody) {
                $capturedBody = $body;

                return $mockResponse;
            });

        $rule = $this->createAlertRule([
            ['bot_token' => $botToken, 'chat_id' => $chatId],
        ]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(false);
        $incident->acknowledgement_token = 'testtoken123';

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertTrue($result['success']);
        $data = json_decode($capturedBody, true);
        $this->assertArrayNotHasKey('reply_markup', $data);
    }

    /**
     * Test send does not include inline keyboard when no token
     */
    public function testSendNoInlineKeyboardWhenNoToken(): void
    {
        $botToken = '123456:ABC-DEF';
        $chatId = '-100123456';

        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('isOk')->willReturn(true);

        $capturedBody = null;
        $this->mockClient->expects($this->once())
            ->method('post')
            ->willReturnCallback(function ($url, $body, $options) use ($mockResponse, &$capturedBody) {
                $capturedBody = $body;

                return $mockResponse;
            });

        $rule = $this->createAlertRule([
            ['bot_token' => $botToken, 'chat_id' => $chatId],
        ]);
        $monitor = $this->createMonitor();
        $incident = $this->createIncident(true);
        // No acknowledgement_token set

        $result = $this->channel->send($rule, $monitor, $incident);

        $this->assertTrue($result['success']);
        $data = json_decode($capturedBody, true);
        $this->assertArrayNotHasKey('reply_markup', $data);
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
     * @param array $recipients Telegram recipient objects
     * @return \App\Model\Entity\AlertRule
     */
    private function createAlertRule(array $recipients): AlertRule
    {
        $rule = new AlertRule();
        $rule->id = 1;
        $rule->monitor_id = 1;
        $rule->channel = 'telegram';
        $rule->trigger_on = AlertRule::TRIGGER_ON_DOWN;
        $rule->throttle_minutes = 5;
        $rule->recipients = json_encode($recipients);
        $rule->active = true;

        return $rule;
    }
}
