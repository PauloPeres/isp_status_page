<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * WebhookDelivery Entity
 *
 * @property int $id
 * @property int $webhook_endpoint_id
 * @property string $event_type
 * @property string $payload
 * @property int|null $response_code
 * @property string|null $response_body
 * @property int $attempts
 * @property \Cake\I18n\DateTime|null $delivered_at
 * @property \Cake\I18n\DateTime|null $next_retry_at
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\WebhookEndpoint $webhook_endpoint
 */
class WebhookDelivery extends Entity
{
    /**
     * Maximum number of delivery attempts.
     */
    public const MAX_ATTEMPTS = 5;

    /**
     * Fields that can be mass assigned.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'webhook_endpoint_id' => true,
        'event_type' => true,
        'payload' => true,
        'response_code' => true,
        'response_body' => true,
        'attempts' => true,
        'delivered_at' => true,
        'next_retry_at' => true,
        'created' => true,
    ];

    /**
     * Check if the delivery was successful.
     *
     * @return bool
     */
    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    /**
     * Check if the delivery has exhausted all retry attempts.
     *
     * @return bool
     */
    public function isExhausted(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS && !$this->isDelivered();
    }

    /**
     * Check if the delivery is pending retry.
     *
     * @return bool
     */
    public function isPendingRetry(): bool
    {
        return !$this->isDelivered() && !$this->isExhausted();
    }
}
