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

    /**
     * Trigger events
     */
    public const TRIGGER_DOWN = 'down';
    public const TRIGGER_UP = 'up';
    public const TRIGGER_DEGRADED = 'degraded';

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
            default => 'Unknown',
        };
    }
}
