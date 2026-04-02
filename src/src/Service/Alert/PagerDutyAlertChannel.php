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
 * PagerDuty Alert Channel (C-02)
 *
 * Sends incident alerts to PagerDuty via the Events API v2.
 * Recipients should be PagerDuty routing/integration keys.
 *
 * @see https://developer.pagerduty.com/api-reference/368ae3d938c9e-send-an-event-to-pager-duty
 */
class PagerDutyAlertChannel implements ChannelInterface
{
    private const EVENTS_API_URL = 'https://events.pagerduty.com/v2/enqueue';

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

        foreach ($recipients as $routingKey) {
            $routingKey = trim($routingKey);
            if (empty($routingKey)) {
                continue;
            }

            try {
                $severity = $this->mapSeverity($incident->severity ?? 'major');
                $isResolved = $incident->status === 'resolved';

                $payload = [
                    'routing_key' => $routingKey,
                    'event_action' => $isResolved ? 'resolve' : 'trigger',
                    'dedup_key' => "isp-status-incident-{$incident->id}",
                    'payload' => [
                        'summary' => $this->buildSummary($monitor, $incident),
                        'source' => $monitor->name,
                        'severity' => $severity,
                        'component' => $monitor->type,
                        'group' => Configure::read('Brand.fullName', 'ISP Status Page'),
                        'custom_details' => [
                            'monitor_id' => $monitor->id,
                            'monitor_type' => $monitor->type,
                            'monitor_status' => $monitor->status,
                            'incident_id' => $incident->id,
                            'incident_status' => $incident->status,
                            'incident_severity' => $incident->severity ?? 'unknown',
                        ],
                    ],
                ];

                $response = $this->httpClient->post(
                    self::EVENTS_API_URL,
                    json_encode($payload),
                    ['type' => 'application/json']
                );

                if ($response->isSuccess()) {
                    $results[] = [
                        'recipient' => $this->maskKey($routingKey),
                        'status' => 'sent',
                        'error' => null,
                    ];
                    Log::info("PagerDuty alert sent for incident {$incident->id}");
                } else {
                    $body = $response->getStringBody();
                    $results[] = [
                        'recipient' => $this->maskKey($routingKey),
                        'status' => 'failed',
                        'error' => "HTTP {$response->getStatusCode()}: {$body}",
                    ];
                    Log::error("PagerDuty alert failed for incident {$incident->id}: HTTP {$response->getStatusCode()}");
                }
            } catch (\Exception $e) {
                $results[] = [
                    'recipient' => $this->maskKey($routingKey),
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
                Log::error("PagerDuty alert exception: {$e->getMessage()}");
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
        return 'pagerduty';
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'PagerDuty';
    }

    /**
     * Map ISP Status severity to PagerDuty severity.
     */
    private function mapSeverity(string $severity): string
    {
        return match ($severity) {
            'critical' => 'critical',
            'major' => 'error',
            'minor' => 'warning',
            'maintenance' => 'info',
            default => 'error',
        };
    }

    /**
     * Build a human-readable summary for PagerDuty.
     */
    private function buildSummary(Monitor $monitor, Incident $incident): string
    {
        if ($incident->status === 'resolved') {
            return "[RESOLVED] {$monitor->name} is back up";
        }

        return "[{$incident->severity}] {$monitor->name} - {$incident->title}";
    }

    /**
     * Mask a routing key for safe logging.
     */
    private function maskKey(string $key): string
    {
        if (strlen($key) <= 8) {
            return '****';
        }

        return substr($key, 0, 4) . '****' . substr($key, -4);
    }
}
