<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use Cake\Http\Client;
use Cake\Log\Log;

/**
 * Slack Alert Channel
 *
 * Sends alert notifications via Slack incoming webhook URLs.
 * Recipients contain Slack webhook URLs.
 */
class SlackAlertChannel implements ChannelInterface
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
     * Send alert to all Slack webhook URLs in the rule
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

                if ($response->isOk()) {
                    $results[] = [
                        'recipient' => $webhookUrl,
                        'status' => 'sent',
                        'error' => null,
                    ];

                    Log::info("Slack alert sent to webhook for monitor {$monitor->name}");
                } else {
                    $allSuccess = false;

                    $results[] = [
                        'recipient' => $webhookUrl,
                        'status' => 'failed',
                        'error' => "HTTP {$response->getStatusCode()}: {$response->getStringBody()}",
                    ];

                    Log::error("Failed to send Slack alert: HTTP {$response->getStatusCode()}");
                }
            } catch (\Exception $e) {
                $allSuccess = false;

                $results[] = [
                    'recipient' => $webhookUrl,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to send Slack alert: {$e->getMessage()}");
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
        return 'slack';
    }

    /**
     * Get human-readable name for this channel
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Slack Alert Channel';
    }

    /**
     * Build the Slack message payload using Block Kit
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return array Slack payload
     */
    public function buildPayload(Monitor $monitor, Incident $incident): array
    {
        $isDown = $incident->isOngoing();
        $statusEmoji = $isDown ? ':red_circle:' : ':large_green_circle:';
        $statusText = $isDown ? 'DOWN' : 'UP';
        $color = $isDown ? '#E53935' : '#43A047';
        $timestamp = $incident->started_at ? $incident->started_at->format('Y-m-d H:i:s T') : 'N/A';

        $fallbackText = "{$statusEmoji} Monitor *{$monitor->name}* is *{$statusText}*";

        return [
            'text' => $fallbackText,
            'attachments' => [
                [
                    'color' => $color,
                    'blocks' => [
                        [
                            'type' => 'header',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => "{$statusEmoji} Monitor {$statusText}: {$monitor->name}",
                                'emoji' => true,
                            ],
                        ],
                        [
                            'type' => 'section',
                            'fields' => [
                                [
                                    'type' => 'mrkdwn',
                                    'text' => "*Monitor:*\n{$monitor->name}",
                                ],
                                [
                                    'type' => 'mrkdwn',
                                    'text' => "*Status:*\n{$statusText}",
                                ],
                                [
                                    'type' => 'mrkdwn',
                                    'text' => "*Type:*\n{$monitor->type}",
                                ],
                                [
                                    'type' => 'mrkdwn',
                                    'text' => "*Timestamp:*\n{$timestamp}",
                                ],
                            ],
                        ],
                        [
                            'type' => 'section',
                            'text' => [
                                'type' => 'mrkdwn',
                                'text' => "*Incident:* #{$incident->id} - {$incident->title}",
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
