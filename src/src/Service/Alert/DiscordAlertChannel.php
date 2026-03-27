<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use Cake\Http\Client;
use Cake\Log\Log;

/**
 * Discord Alert Channel
 *
 * Sends alert notifications via Discord webhook URLs.
 * Uses Discord embed format with color-coded sidebar.
 */
class DiscordAlertChannel implements ChannelInterface
{
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
     * Send alert to all Discord webhook URLs in the rule
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

        $payload = $this->buildPayload($monitor, $incident);

        $results = [];
        $allSuccess = true;

        foreach ($recipients as $webhookUrl) {
            try {
                $response = $this->httpClient->post(
                    $webhookUrl,
                    json_encode($payload),
                    ['type' => 'application/json']
                );

                if ($response->isOk() || $response->getStatusCode() === 204) {
                    $results[] = [
                        'recipient' => $webhookUrl,
                        'status' => 'sent',
                        'error' => null,
                    ];

                    Log::info("Discord alert sent to webhook for monitor {$monitor->name}");
                } else {
                    $allSuccess = false;

                    $results[] = [
                        'recipient' => $webhookUrl,
                        'status' => 'failed',
                        'error' => "HTTP {$response->getStatusCode()}: {$response->getStringBody()}",
                    ];

                    Log::error("Failed to send Discord alert: HTTP {$response->getStatusCode()}");
                }
            } catch (\Exception $e) {
                $allSuccess = false;

                $results[] = [
                    'recipient' => $webhookUrl,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to send Discord alert: {$e->getMessage()}");
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
        return 'discord';
    }

    /**
     * Get human-readable name for this channel
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Discord Alert Channel';
    }

    /**
     * Build the Discord message payload with embeds
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return array Discord payload
     */
    public function buildPayload(Monitor $monitor, Incident $incident): array
    {
        $isDown = $incident->isOngoing();
        $statusText = $isDown ? 'DOWN' : 'UP';
        // Discord embed colors are decimal integers
        $color = $isDown ? 0xE53935 : 0x43A047;
        $timestamp = $incident->started_at ? $incident->started_at->format('c') : null;

        return [
            'username' => 'ISP Status Monitor',
            'embeds' => [
                [
                    'title' => "Monitor {$statusText}: {$monitor->name}",
                    'description' => "Incident #{$incident->id} - {$incident->title}",
                    'color' => $color,
                    'fields' => [
                        [
                            'name' => 'Monitor',
                            'value' => $monitor->name,
                            'inline' => true,
                        ],
                        [
                            'name' => 'Status',
                            'value' => $statusText,
                            'inline' => true,
                        ],
                        [
                            'name' => 'Type',
                            'value' => $monitor->type,
                            'inline' => true,
                        ],
                        [
                            'name' => 'Incident',
                            'value' => "#{$incident->id}",
                            'inline' => true,
                        ],
                    ],
                    'timestamp' => $timestamp,
                    'footer' => [
                        'text' => 'ISP Status Page',
                    ],
                ],
            ],
        ];
    }
}
