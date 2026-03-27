<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use Cake\Http\Client;
use Cake\Log\Log;

/**
 * Telegram Alert Channel
 *
 * Sends alert notifications via Telegram Bot API.
 * Recipients format: JSON objects with bot_token and chat_id.
 */
class TelegramAlertChannel implements ChannelInterface
{
    /**
     * Telegram Bot API base URL
     *
     * @var string
     */
    protected const API_BASE_URL = 'https://api.telegram.org/bot';

    /**
     * HTTP client instance
     *
     * @var \Cake\Http\Client
     */
    protected Client $httpClient;

    /**
     * Constructor
     *
     * @param \Cake\Http\Client|null $httpClient HTTP client instance
     */
    public function __construct(?Client $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * Send alert to all Telegram recipients in the rule
     *
     * Recipients are JSON objects: {"bot_token": "...", "chat_id": "..."}
     *
     * @param \App\Model\Entity\AlertRule $rule The alert rule
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return array Result with success flag and per-recipient results
     */
    public function send(AlertRule $rule, Monitor $monitor, Incident $incident): array
    {
        $recipients = $rule->getRecipients();

        if (empty($recipients)) {
            Log::warning("Alert rule {$rule->id} has no recipients configured");

            return [
                'success' => false,
                'results' => [],
            ];
        }

        $message = $this->buildMessage($monitor, $incident);

        $results = [];
        $allSuccess = true;

        foreach ($recipients as $recipient) {
            $recipientData = $this->parseRecipient($recipient);

            if ($recipientData === null) {
                $allSuccess = false;
                $recipientLabel = is_string($recipient) ? $recipient : json_encode($recipient);

                $results[] = [
                    'recipient' => $recipientLabel,
                    'status' => 'failed',
                    'error' => 'Invalid recipient format. Expected {"bot_token": "...", "chat_id": "..."}',
                ];

                Log::error("Invalid Telegram recipient format for alert rule {$rule->id}");
                continue;
            }

            $recipientLabel = "chat:{$recipientData['chat_id']}";

            try {
                $url = static::API_BASE_URL . $recipientData['bot_token'] . '/sendMessage';

                $response = $this->httpClient->post(
                    $url,
                    json_encode([
                        'chat_id' => $recipientData['chat_id'],
                        'text' => $message,
                        'parse_mode' => 'HTML',
                        'disable_web_page_preview' => true,
                    ]),
                    ['type' => 'application/json']
                );

                if ($response->isOk()) {
                    $results[] = [
                        'recipient' => $recipientLabel,
                        'status' => 'sent',
                        'error' => null,
                    ];

                    Log::info("Telegram alert sent to {$recipientLabel} for monitor {$monitor->name}");
                } else {
                    $allSuccess = false;
                    $body = json_decode($response->getStringBody(), true);
                    $errorDesc = $body['description'] ?? "HTTP {$response->getStatusCode()}";

                    $results[] = [
                        'recipient' => $recipientLabel,
                        'status' => 'failed',
                        'error' => $errorDesc,
                    ];

                    Log::error("Failed to send Telegram alert to {$recipientLabel}: {$errorDesc}");
                }
            } catch (\Exception $e) {
                $allSuccess = false;

                $results[] = [
                    'recipient' => $recipientLabel,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to send Telegram alert to {$recipientLabel}: {$e->getMessage()}");
            }
        }

        return [
            'success' => $allSuccess,
            'results' => $results,
        ];
    }

    /**
     * Get the channel type identifier
     *
     * @return string
     */
    public function getType(): string
    {
        return 'telegram';
    }

    /**
     * Get human-readable name for this channel
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Telegram Alert Channel';
    }

    /**
     * Build the Telegram message with HTML formatting
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return string HTML-formatted message
     */
    public function buildMessage(Monitor $monitor, Incident $incident): string
    {
        $isDown = $incident->isOngoing();
        $statusEmoji = $isDown ? "\xF0\x9F\x94\xB4" : "\xF0\x9F\x9F\xA2"; // Red/Green circle
        $statusText = $isDown ? 'DOWN' : 'UP';
        $timestamp = $incident->started_at ? $incident->started_at->format('Y-m-d H:i:s T') : 'N/A';

        $lines = [
            "{$statusEmoji} <b>Monitor {$statusText}: {$this->escapeHtml($monitor->name)}</b>",
            '',
            "<b>Monitor:</b> {$this->escapeHtml($monitor->name)}",
            "<b>Status:</b> {$statusText}",
            "<b>Type:</b> {$monitor->type}",
            "<b>Timestamp:</b> {$timestamp}",
            '',
            "<b>Incident:</b> #{$incident->id} - {$this->escapeHtml($incident->title)}",
        ];

        return implode("\n", $lines);
    }

    /**
     * Parse a recipient entry into bot_token and chat_id
     *
     * @param mixed $recipient Recipient data (array or JSON string)
     * @return array|null Parsed data with bot_token and chat_id, or null if invalid
     */
    public function parseRecipient(mixed $recipient): ?array
    {
        if (is_string($recipient)) {
            $recipient = json_decode($recipient, true);
        }

        if (!is_array($recipient)) {
            return null;
        }

        if (empty($recipient['bot_token']) || empty($recipient['chat_id'])) {
            return null;
        }

        return [
            'bot_token' => (string)$recipient['bot_token'],
            'chat_id' => (string)$recipient['chat_id'],
        ];
    }

    /**
     * Escape HTML special characters for Telegram HTML mode
     *
     * @param string $text Text to escape
     * @return string Escaped text
     */
    protected function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
