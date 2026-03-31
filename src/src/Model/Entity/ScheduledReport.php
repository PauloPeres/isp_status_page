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
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';

    /**
     * Virtual fields exposed in JSON/array output.
     *
     * @var list<string>
     */
    protected array $_virtual = ['report_type', 'recipients_list'];

    /**
     * Hidden fields omitted from JSON/array output.
     *
     * @var list<string>
     */
    protected array $_hidden = ['organization_id'];

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
     * Virtual accessor: decode recipients JSON string to array for API output.
     * Named recipients_list (not recipients) to avoid interfering with DB save.
     *
     * @return array<string>
     */
    protected function _getRecipientsList(): array
    {
        $raw = $this->_fields['recipients'] ?? '';
        if (empty($raw)) {
            return [];
        }
        if (is_array($raw)) {
            return $raw;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $raw)));
    }

    /**
     * Virtual accessor: derive report_type from include_* booleans.
     *
     * @return string
     */
    protected function _getReportType(): string
    {
        if (!empty($this->include_sla)) {
            return 'sla';
        }
        if (!empty($this->include_incidents)) {
            return 'incidents';
        }
        if (!empty($this->include_response_time)) {
            return 'performance';
        }

        return 'uptime';
    }

    /**
     * Get recipients as an array of email addresses.
     *
     * Uses the _getRecipients accessor which already handles JSON decoding.
     *
     * @return array<string>
     */
    public function getRecipientsArray(): array
    {
        return $this->recipients;
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
