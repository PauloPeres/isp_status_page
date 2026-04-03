<?php
declare(strict_types=1);

namespace App\Job;

use App\Service\WebhookDeliveryService;
use Cake\Log\Log;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Cake\Queue\QueueManager;
use Interop\Queue\Processor;

/**
 * Webhook Delivery Job
 *
 * Delivers a single webhook payload to its endpoint.
 * On failure, re-queues itself with exponential backoff delay
 * up to the maximum number of attempts.
 */
class WebhookDeliveryJob implements JobInterface
{
    /**
     * Maximum number of retry attempts.
     *
     * @var int|null
     */
    public static ?int $maxAttempts = 5;

    /**
     * Execute the webhook delivery job.
     *
     * @param \Cake\Queue\Job\Message $message The queue message
     * @return string Processor result (ACK or REJECT)
     */
    public function execute(Message $message): string
    {
        $data = $message->getArgument('data') ?? [];
        $deliveryId = $data['delivery_id'] ?? null;
        $attempt = $data['attempt'] ?? 1;

        if ($deliveryId === null) {
            Log::error('WebhookDeliveryJob: Missing delivery_id in message data');

            return Processor::REJECT;
        }

        try {
            $webhookService = new WebhookDeliveryService();
            $success = $webhookService->deliver((int)$deliveryId);

            if ($success) {
                Log::info("WebhookDeliveryJob: Successfully delivered webhook {$deliveryId}");

                return Processor::ACK;
            }

            // Delivery failed — re-queue with backoff if under max attempts
            if ($attempt < WebhookDeliveryService::MAX_ATTEMPTS) {
                $nextAttempt = $attempt + 1;

                // Exponential backoff in seconds: 60, 300, 1800, 7200, 43200
                $backoffSeconds = [60, 300, 1800, 7200, 43200];
                $delay = $backoffSeconds[$attempt - 1] ?? 43200;

                Log::warning("WebhookDeliveryJob: Delivery {$deliveryId} failed, re-queuing attempt {$nextAttempt}/{$this::$maxAttempts} with {$delay}s delay");

                QueueManager::push(
                    static::class,
                    [
                        'data' => [
                            'delivery_id' => $deliveryId,
                            'attempt' => $nextAttempt,
                        ],
                    ],
                    [
                        'config' => 'default',
                        'delay' => $delay,
                    ]
                );
            } else {
                Log::error("WebhookDeliveryJob: Delivery {$deliveryId} exhausted all {$this::$maxAttempts} attempts");
            }

            return Processor::ACK;
        } catch (\Exception $e) {
            Log::error("WebhookDeliveryJob: Exception for delivery {$deliveryId}: {$e->getMessage()}");

            return Processor::REJECT;
        }
    }
}
