<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AlertRule Entity
 *
 * @property int $id
 * @property int $monitor_id
 * @property string $channel
 * @property string $trigger_on
 * @property int $throttle_minutes
 * @property string $recipients
 * @property string|null $template
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Monitor $monitor
 * @property \App\Model\Entity\AlertLog[] $alert_logs
 */
class AlertRule extends Entity
{
    /**
     * Alert channels
     */
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_TELEGRAM = 'telegram';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_PHONE = 'phone';

    /**
     * Trigger types (matching DATABASE.md)
     */
    public const TRIGGER_ON_DOWN = 'on_down';
    public const TRIGGER_ON_UP = 'on_up';
    public const TRIGGER_ON_DEGRADED = 'on_degraded';
    public const TRIGGER_ON_CHANGE = 'on_change';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'monitor_id' => true,
        'channel' => true,
        'trigger_on' => true,
        'throttle_minutes' => true,
        'recipients' => true,
        'template' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'monitor' => true,
        'alert_logs' => true,
    ];

    /**
     * Get recipients as array
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
     * Set recipients from array
     *
     * @param array|string $value Recipients array or JSON string
     * @return string
     */
    protected function _setRecipients(array|string $value): string
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * Check if alert rule is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Check if channel is email
     *
     * @return bool
     */
    public function isEmailChannel(): bool
    {
        return $this->channel === self::CHANNEL_EMAIL;
    }

    /**
     * Check if channel is WhatsApp
     *
     * @return bool
     */
    public function isWhatsAppChannel(): bool
    {
        return $this->channel === self::CHANNEL_WHATSAPP;
    }

    /**
     * Check if channel is Telegram
     *
     * @return bool
     */
    public function isTelegramChannel(): bool
    {
        return $this->channel === self::CHANNEL_TELEGRAM;
    }

    /**
     * Check if channel is SMS
     *
     * @return bool
     */
    public function isSmsChannel(): bool
    {
        return $this->channel === self::CHANNEL_SMS;
    }

    /**
     * Check if channel is Phone
     *
     * @return bool
     */
    public function isPhoneChannel(): bool
    {
        return $this->channel === self::CHANNEL_PHONE;
    }

    /**
     * Get human-readable channel name
     *
     * @return string
     */
    public function getChannelName(): string
    {
        return match ($this->channel) {
            self::CHANNEL_EMAIL => 'Email',
            self::CHANNEL_WHATSAPP => 'WhatsApp',
            self::CHANNEL_TELEGRAM => 'Telegram',
            self::CHANNEL_SMS => 'SMS',
            self::CHANNEL_PHONE => 'Phone',
            default => 'Unknown',
        };
    }

    /**
     * Get human-readable trigger name
     *
     * @return string
     */
    public function getTriggerName(): string
    {
        return match ($this->trigger_on) {
            self::TRIGGER_ON_DOWN => 'When Down',
            self::TRIGGER_ON_UP => 'When Up',
            self::TRIGGER_ON_DEGRADED => 'When Degraded',
            self::TRIGGER_ON_CHANGE => 'On Status Change',
            default => 'Unknown',
        };
    }

    /**
     * Check if this rule should trigger for a given status change
     *
     * @param string $oldStatus Old monitor status
     * @param string $newStatus New monitor status
     * @return bool
     */
    public function shouldTrigger(string $oldStatus, string $newStatus): bool
    {
        // Don't trigger if rule is inactive
        if (!$this->isActive()) {
            return false;
        }

        // No change, don't trigger
        if ($oldStatus === $newStatus) {
            return false;
        }

        return match ($this->trigger_on) {
            self::TRIGGER_ON_DOWN => $newStatus === Monitor::STATUS_DOWN,
            self::TRIGGER_ON_UP => $newStatus === Monitor::STATUS_UP,
            self::TRIGGER_ON_DEGRADED => $newStatus === Monitor::STATUS_DEGRADED,
            self::TRIGGER_ON_CHANGE => true, // Triggers on any status change
            default => false,
        };
    }
}
