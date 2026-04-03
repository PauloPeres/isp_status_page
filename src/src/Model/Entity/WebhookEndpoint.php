<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * WebhookEndpoint Entity
 *
 * @property int $id
 * @property int $organization_id
 * @property string $url
 * @property string $secret
 * @property string|null $events
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\WebhookDelivery[] $webhook_deliveries
 */
class WebhookEndpoint extends Entity
{
    /**
     * Fields that can be mass assigned.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'public_id' => true,
        'organization_id' => true,
        'url' => true,
        'secret' => true,
        'events' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Fields to hide from serialization.
     *
     * @var array<string>
     */
    protected array $_hidden = [
        'secret',
    ];

    /**
     * Get subscribed events as an array.
     *
     * @return array
     */
    public function getEvents(): array
    {
        if (empty($this->events)) {
            return [];
        }

        if (is_array($this->events)) {
            return $this->events;
        }

        $decoded = json_decode($this->events, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Check if this endpoint is subscribed to a given event type.
     *
     * An empty events list means "subscribe to all events".
     *
     * @param string $eventType The event type to check.
     * @return bool
     */
    public function isSubscribedTo(string $eventType): bool
    {
        $events = $this->getEvents();

        // Empty events list = subscribed to everything
        if (empty($events)) {
            return true;
        }

        return in_array($eventType, $events, true);
    }
}
