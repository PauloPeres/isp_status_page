<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use Cake\Http\Client;
use Cake\Core\Configure;
use Cake\Log\Log;

/**
 * OpsGenie Alert Channel (C-02)
 *
 * Sends incident alerts to OpsGenie (Atlassian) via the Alert API v2.
 * Recipients should be OpsGenie API keys (integration keys).
 *
 * @see https://docs.opsgenie.com/docs/alert-api
 */
class OpsGenieAlertChannel implements ChannelInterface
{
    private const API_URL = 'https://api.opsgenie.com/v2/alerts';

    private Client $httpClient;

    public function __construct(?Client $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Client([
            'timeout' => 15,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function send(AlertRule $rule, Monitor $monitor, Incident $incident): array
    {
        $recipients = $rule->getRecipients();
        $results = [];

        foreach ($recipients as $apiKey) {
            $apiKey = trim($apiKey);
            if (empty($apiKey)) {
                continue;
            }

            try {
                $isResolved = $incident->status === 'resolved';

                if ($isResolved) {
                    $result = $this->closeAlert($apiKey, $monitor, $incident);
                } else {
                    $result = $this->createAlert($apiKey, $monitor, $incident);
                }

                $results[] = $result;
            } catch (\Exception $e) {
                $results[] = [
                    'recipient' => $this->maskKey($apiKey),
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
                Log::error("OpsGenie alert exception: {$e->getMessage()}");
            }
        }

        return [
            'success' => count(array_filter($results, fn($r) => $r['status'] === 'sent')) > 0,
            'results' => $results,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return 'opsgenie';
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'OpsGenie';
    }

    /**
     * Create an alert in OpsGenie.
     */
    private function createAlert(string $apiKey, Monitor $monitor, Incident $incident): array
    {
        $priority = $this->mapPriority($incident->severity ?? 'major');

        $payload = [
            'message' => "[{$incident->severity}] {$monitor->name} - {$incident->title}",
            'alias' => "isp-status-incident-{$incident->id}",
            'description' => $incident->description ?? "Monitor {$monitor->name} is {$monitor->status}",
            'priority' => $priority,
            'source' => Configure::read('Brand.fullName', 'ISP Status Page'),
            'entity' => $monitor->name,
            'tags' => ['isp-status', $monitor->type, $monitor->status],
            'details' => [
                'monitor_id' => (string)$monitor->id,
                'monitor_type' => $monitor->type,
                'monitor_status' => $monitor->status,
                'incident_id' => (string)$incident->id,
                'incident_status' => $incident->status,
            ],
        ];

        $response = $this->httpClient->post(
            self::API_URL,
            json_encode($payload),
            [
                'type' => 'application/json',
                'headers' => [
                    'Authorization' => "GenieKey {$apiKey}",
                ],
            ]
        );

        if ($response->isSuccess()) {
            Log::info("OpsGenie alert created for incident {$incident->id}");

            return [
                'recipient' => $this->maskKey($apiKey),
                'status' => 'sent',
                'error' => null,
            ];
        }

        $body = $response->getStringBody();
        Log::error("OpsGenie alert failed: HTTP {$response->getStatusCode()}: {$body}");

        return [
            'recipient' => $this->maskKey($apiKey),
            'status' => 'failed',
            'error' => "HTTP {$response->getStatusCode()}: {$body}",
        ];
    }

    /**
     * Close an alert in OpsGenie (on incident resolution).
     */
    private function closeAlert(string $apiKey, Monitor $monitor, Incident $incident): array
    {
        $alias = "isp-status-incident-{$incident->id}";
        $url = self::API_URL . "/{$alias}/close?identifierType=alias";

        $payload = [
            'note' => "Resolved: {$monitor->name} is back up",
            'source' => Configure::read('Brand.fullName', 'ISP Status Page'),
        ];

        $response = $this->httpClient->post(
            $url,
            json_encode($payload),
            [
                'type' => 'application/json',
                'headers' => [
                    'Authorization' => "GenieKey {$apiKey}",
                ],
            ]
        );

        if ($response->isSuccess()) {
            Log::info("OpsGenie alert closed for incident {$incident->id}");

            return [
                'recipient' => $this->maskKey($apiKey),
                'status' => 'sent',
                'error' => null,
            ];
        }

        $body = $response->getStringBody();
        Log::error("OpsGenie close failed: HTTP {$response->getStatusCode()}: {$body}");

        return [
            'recipient' => $this->maskKey($apiKey),
            'status' => 'failed',
            'error' => "HTTP {$response->getStatusCode()}: {$body}",
        ];
    }

    /**
     * Map ISP Status severity to OpsGenie priority.
     */
    private function mapPriority(string $severity): string
    {
        return match ($severity) {
            'critical' => 'P1',
            'major' => 'P2',
            'minor' => 'P3',
            'maintenance' => 'P5',
            default => 'P3',
        };
    }

    /**
     * Mask an API key for safe logging.
     */
    private function maskKey(string $key): string
    {
        if (strlen($key) <= 8) {
            return '****';
        }

        return substr($key, 0, 4) . '****' . substr($key, -4);
    }
}
