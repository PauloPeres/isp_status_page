<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\WebhookDelivery;
use Cake\Http\Client;
use Cake\I18n\DateTime;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * WebhookDeliveryService
 *
 * Handles dispatching webhook events to registered endpoints,
 * delivering payloads with HMAC-SHA256 signatures, and retrying
 * failed deliveries with exponential backoff.
 */
class WebhookDeliveryService
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * Maximum number of delivery attempts.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * HTTP request timeout in seconds.
     */
    public const REQUEST_TIMEOUT = 10;

    /**
     * HTTP client instance (injectable for testing).
     *
     * @var \Cake\Http\Client|null
     */
    protected ?Client $httpClient;

    /**
     * Constructor.
     *
     * @param \Cake\Http\Client|null $httpClient Optional HTTP client for testing.
     */
    public function __construct(?Client $httpClient = null)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Dispatch a webhook event to all matching endpoints for the given organization.
     *
     * Finds active webhook endpoints that are subscribed to the event type,
     * creates delivery records, and attempts immediate delivery.
     *
     * @param string $eventType Event type (e.g. "monitor.down", "incident.created").
     * @param array $payload Event payload data.
     * @param int $orgId Organization ID.
     * @return array<int> Array of created webhook delivery IDs.
     */
    public function dispatch(string $eventType, array $payload, int $orgId): array
    {
        $endpointsTable = $this->fetchTable('WebhookEndpoints');

        $endpoints = $endpointsTable->find()
            ->where([
                'WebhookEndpoints.organization_id' => $orgId,
                'WebhookEndpoints.active' => true,
            ])
            ->all();

        $deliveryIds = [];
        $deliveriesTable = $this->fetchTable('WebhookDeliveries');

        foreach ($endpoints as $endpoint) {
            // Check if the endpoint is subscribed to this event type
            if (!$endpoint->isSubscribedTo($eventType)) {
                continue;
            }

            $jsonPayload = json_encode([
                'event' => $eventType,
                'data' => $payload,
                'timestamp' => (new DateTime())->toIso8601String(),
            ]);

            $delivery = $deliveriesTable->newEntity([
                'webhook_endpoint_id' => $endpoint->id,
                'event_type' => $eventType,
                'payload' => $jsonPayload,
                'attempts' => 0,
                'created' => new DateTime(),
            ]);

            if ($deliveriesTable->save($delivery)) {
                $deliveryIds[] = $delivery->id;

                // Attempt immediate delivery
                $this->deliver($delivery->id);
            } else {
                $this->log(
                    sprintf('Failed to save webhook delivery for endpoint %d, event %s', $endpoint->id, $eventType),
                    'error'
                );
            }
        }

        return $deliveryIds;
    }

    /**
     * Deliver a webhook payload to the endpoint.
     *
     * Makes an HTTP POST request with JSON payload and HMAC-SHA256 signature.
     * On failure, schedules a retry with exponential backoff (max 5 attempts).
     *
     * @param int $deliveryId Webhook delivery ID.
     * @return bool True if delivery was successful.
     */
    public function deliver(int $deliveryId): bool
    {
        $deliveriesTable = $this->fetchTable('WebhookDeliveries');
        $endpointsTable = $this->fetchTable('WebhookEndpoints');

        try {
            $delivery = $deliveriesTable->get($deliveryId);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->log(sprintf('Webhook delivery %d not found', $deliveryId), 'error');
            return false;
        }

        // Already delivered or exhausted
        if ($delivery->isDelivered() || $delivery->isExhausted()) {
            return $delivery->isDelivered();
        }

        try {
            $endpoint = $endpointsTable->get($delivery->webhook_endpoint_id);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->log(sprintf('Webhook endpoint %d not found for delivery %d', $delivery->webhook_endpoint_id, $deliveryId), 'error');
            return false;
        }

        // Increment attempt counter
        $delivery->attempts += 1;

        $signature = $this->sign($delivery->payload, $endpoint->secret);

        try {
            $client = $this->getHttpClient();

            $response = $client->post($endpoint->url, $delivery->payload, [
                'type' => 'json',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $delivery->event_type,
                    'User-Agent' => 'ISPStatusPage-Webhook/1.0',
                ],
                'timeout' => self::REQUEST_TIMEOUT,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = (string)$response->getBody();

            $delivery->response_code = $statusCode;
            $delivery->response_body = mb_substr($responseBody, 0, 65535);

            // Consider 2xx responses as successful
            if ($statusCode >= 200 && $statusCode < 300) {
                $delivery->delivered_at = new DateTime();
                $delivery->next_retry_at = null;
                $deliveriesTable->save($delivery);

                $this->log(
                    sprintf('Webhook delivery %d succeeded (HTTP %d)', $deliveryId, $statusCode),
                    'info'
                );

                return true;
            }

            // Non-2xx response — schedule retry
            $this->scheduleRetry($delivery);
            $deliveriesTable->save($delivery);

            $this->log(
                sprintf('Webhook delivery %d failed (HTTP %d), attempt %d/%d', $deliveryId, $statusCode, $delivery->attempts, self::MAX_ATTEMPTS),
                'warning'
            );

            return false;
        } catch (\Exception $e) {
            $delivery->response_code = null;
            $delivery->response_body = $e->getMessage();

            $this->scheduleRetry($delivery);
            $deliveriesTable->save($delivery);

            $this->log(
                sprintf('Webhook delivery %d exception: %s, attempt %d/%d', $deliveryId, $e->getMessage(), $delivery->attempts, self::MAX_ATTEMPTS),
                'error'
            );

            return false;
        }
    }

    /**
     * Generate an HMAC-SHA256 signature for a payload.
     *
     * @param string $payload The payload to sign.
     * @param string $secret The signing secret.
     * @return string The hex-encoded HMAC-SHA256 signature.
     */
    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Schedule a retry with exponential backoff.
     *
     * Backoff schedule: 1min, 5min, 30min, 2h, 12h
     *
     * @param \App\Model\Entity\WebhookDelivery $delivery The delivery entity.
     * @return void
     */
    protected function scheduleRetry(WebhookDelivery $delivery): void
    {
        if ($delivery->attempts >= self::MAX_ATTEMPTS) {
            $delivery->next_retry_at = null;
            return;
        }

        // Exponential backoff in seconds: 60, 300, 1800, 7200, 43200
        $backoffSeconds = [60, 300, 1800, 7200, 43200];
        $delay = $backoffSeconds[$delivery->attempts - 1] ?? 43200;

        $delivery->next_retry_at = (new DateTime())->modify("+{$delay} seconds");
    }

    /**
     * Get the HTTP client instance.
     *
     * @return \Cake\Http\Client
     */
    protected function getHttpClient(): Client
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }
}
