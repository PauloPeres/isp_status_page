<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ScheduledReport Entity
 *
 * Represents a scheduled email report configuration.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $frequency
 * @property string $recipients
 * @property bool $include_uptime
 * @property bool $include_response_time
 * @property bool $include_incidents
 * @property bool $include_sla
 * @property \Cake\I18n\DateTime|null $last_sent_at
 * @property \Cake\I18n\DateTime|null $next_send_at
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 */
class ScheduledReport extends Entity
{
    /**
     * Frequency constants
     */
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'name' => true,
        'frequency' => true,
        'recipients' => true,
        'include_uptime' => true,
        'include_response_time' => true,
        'include_incidents' => true,
        'include_sla' => true,
        'last_sent_at' => true,
        'next_send_at' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Get recipients as an array of email addresses.
     *
     * @return array<string>
     */
    public function getRecipientsArray(): array
    {
        if (empty($this->recipients)) {
            return [];
        }

        $decoded = json_decode($this->recipients, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Fallback: comma-separated string
        return array_filter(array_map('trim', explode(',', $this->recipients)));
    }

    /**
     * Set recipients from an array of email addresses.
     *
     * @param array<string> $emails Array of email addresses
     * @return void
     */
    public function setRecipientsFromArray(array $emails): void
    {
        $this->recipients = json_encode(array_values(array_filter(array_map('trim', $emails))));
    }

    /**
     * Get the frequency display label.
     *
     * @return string
     */
    public function getFrequencyLabel(): string
    {
        return match ($this->frequency) {
            self::FREQUENCY_WEEKLY => __('Weekly'),
            self::FREQUENCY_MONTHLY => __('Monthly'),
            default => ucfirst($this->frequency),
        };
    }
}
