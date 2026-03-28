<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * EscalationStep Entity
 *
 * @property int $id
 * @property int $escalation_policy_id
 * @property int $step_number
 * @property int $wait_minutes
 * @property string $channel
 * @property string $recipients
 * @property string|null $message_template
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\EscalationPolicy $escalation_policy
 */
class EscalationStep extends Entity
{
    /**
     * Available alert channels for escalation steps
     */
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SLACK = 'slack';
    public const CHANNEL_DISCORD = 'discord';
    public const CHANNEL_TELEGRAM = 'telegram';
    public const CHANNEL_WEBHOOK = 'webhook';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_WHATSAPP = 'whatsapp';

    /**
     * All valid channels
     */
    public const VALID_CHANNELS = [
        self::CHANNEL_EMAIL,
        self::CHANNEL_SLACK,
        self::CHANNEL_DISCORD,
        self::CHANNEL_TELEGRAM,
        self::CHANNEL_WEBHOOK,
        self::CHANNEL_SMS,
        self::CHANNEL_WHATSAPP,
    ];

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'escalation_policy_id' => true,
        'step_number' => true,
        'wait_minutes' => true,
        'channel' => true,
        'recipients' => true,
        'message_template' => true,
        'created' => true,
        'modified' => true,
        'escalation_policy' => true,
    ];

    /**
     * Get recipients as an array.
     *
     * @return array
     */
    public function getRecipients(): array
    {
        if (empty($this->recipients)) {
            return [];
        }

        if (is_array($this->recipients)) {
            return $this->recipients;
        }

        $decoded = json_decode($this->recipients, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get a human-readable channel name.
     *
     * @return string
     */
    public function getChannelName(): string
    {
        return match ($this->channel) {
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_SLACK => 'Slack',
            self::CHANNEL_DISCORD => 'Discord',
            self::CHANNEL_TELEGRAM => 'Telegram',
            self::CHANNEL_WEBHOOK => 'Webhook',
            self::CHANNEL_SMS => 'SMS',
            self::CHANNEL_WHATSAPP => 'WhatsApp',
            default => ucfirst($this->channel),
        };
    }

    /**
     * Get a formatted summary of the recipients.
     *
     * @return string
     */
    public function getRecipientsSummary(): string
    {
        $recipients = $this->getRecipients();

        if (empty($recipients)) {
            return '(none)';
        }

        if (count($recipients) <= 2) {
            return implode(', ', $recipients);
        }

        return $recipients[0] . ', ' . $recipients[1] . ' +' . (count($recipients) - 2) . ' more';
    }
}
