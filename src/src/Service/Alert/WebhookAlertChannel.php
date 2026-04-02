<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use Cake\Http\Client;
use Cake\I18n\DateTime;
use Cake\Log\Log;

/**
 * Webhook Alert Channel
 *
 * Sends alert notifications via custom webhook URLs.
 * POSTs JSON payload with HMAC-SHA256 signature.
 */
class WebhookAlertChannel implements ChannelInterface
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
     * Send alert to all webhook URLs in the rule
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
        $jsonPayload = json_encode($payload);

        // Extract signing secret from the rule's template field (used as config)
        $secret = $this->getSigningSecret($rule);

        $results = [];
        $allSuccess = true;

        foreach ($recipients as $webhookUrl) {
            // SSRF protection: block requests to private/internal networks
            if (!\App\Service\UrlValidator::isUrlSafe($webhookUrl)) {
                $allSuccess = false;

                $results[] = [
                    'recipient' => $webhookUrl,
                    'status' => 'failed',
                    'error' => 'URL targets a private/internal network address',
                ];

                Log::warning("Blocked webhook to private/internal address: {$webhookUrl}");
                continue;
            }

            try {
                $headers = [
                    'type' => 'application/json',
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'ISPStatusPage-Webhook/1.0',
                    ],
                ];

                // Add HMAC signature if a secret is configured
                if (!empty($secret)) {
                    $signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $secret);
                    $headers['headers']['X-Signature-256'] = $signature;
                }

                $response = $this->httpClient->post(
                    $webhookUrl,
                    $jsonPayload,
                    $headers
                );

                if ($response->isOk()) {
                    $results[] = [
                        'recipient' => $webhookUrl,
                        'status' => 'sent',
                        'error' => null,
                    ];

                    Log::info("Webhook alert sent to {$webhookUrl} for monitor {$monitor->name}");
                } else {
                    $allSuccess = false;

                    $results[] = [
                        'recipient' => $webhookUrl,
                        'status' => 'failed',
                        'error' => "HTTP {$response->getStatusCode()}: {$response->getStringBody()}",
                    ];

                    Log::error("Failed to send webhook alert to {$webhookUrl}: HTTP {$response->getStatusCode()}");
                }
            } catch (\Exception $e) {
                $allSuccess = false;

                $results[] = [
                    'recipient' => $webhookUrl,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to send webhook alert to {$webhookUrl}: {$e->getMessage()}");
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
        return 'webhook';
    }

    /**
     * Get human-readable name for this channel
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Webhook Alert Channel';
    }

    /**
     * Build the webhook JSON payload
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return array Webhook payload
     */
    public function buildPayload(Monitor $monitor, Incident $incident): array
    {
        $isDown = $incident->isOngoing();

        return [
            'event_type' => $isDown ? 'monitor.down' : 'monitor.up',
            'monitor' => [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'type' => $monitor->type,
            ],
            'incident' => [
                'id' => $incident->id,
                'title' => $incident->title,
                'status' => $incident->status,
                'started_at' => $incident->started_at ? $incident->started_at->format('c') : null,
            ],
            'timestamp' => DateTime::now()->format('c'),
        ];
    }

    /**
     * Get the HMAC signing secret from the alert rule config
     *
     * The signing secret is stored in the rule's template field as a JSON object:
     * {"webhook_secret": "your-secret-here"}
     *
     * @param \App\Model\Entity\AlertRule $rule The alert rule
     * @return string|null The signing secret, or null if not configured
     */
    public function getSigningSecret(AlertRule $rule): ?string
    {
        if (empty($rule->template)) {
            return null;
        }

        $config = json_decode($rule->template, true);

        if (is_array($config) && !empty($config['webhook_secret'])) {
            return (string)$config['webhook_secret'];
        }

        return null;
    }

    /**
     * Compute the HMAC-SHA256 signature for a payload
     *
     * @param string $payload The JSON payload string
     * @param string $secret The signing secret
     * @return string The signature prefixed with "sha256="
     */
    public function computeSignature(string $payload, string $secret): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }
}
