<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * NotificationChannel Entity
 *
 * Represents a configured connection to a notification service.
 * Created once per organization, reused across notification policies.
 *
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $type
 * @property string $configuration
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\NotificationPolicyStep[] $notification_policy_steps
 */
class NotificationChannel extends Entity
{
    /**
     * Channel types
     */
    public const TYPE_EMAIL = 'email';
    public const TYPE_SLACK = 'slack';
    public const TYPE_DISCORD = 'discord';
    public const TYPE_TELEGRAM = 'telegram';
    public const TYPE_SMS = 'sms';
    public const TYPE_WHATSAPP = 'whatsapp';
    public const TYPE_PAGERDUTY = 'pagerduty';
    public const TYPE_OPSGENIE = 'opsgenie';
    public const TYPE_WEBHOOK = 'webhook';

    /**
     * All valid channel types
     */
    public const VALID_TYPES = [
        self::TYPE_EMAIL,
        self::TYPE_SLACK,
        self::TYPE_DISCORD,
        self::TYPE_TELEGRAM,
        self::TYPE_SMS,
        self::TYPE_WHATSAPP,
        self::TYPE_PAGERDUTY,
        self::TYPE_OPSGENIE,
        self::TYPE_WEBHOOK,
    ];

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => true,
        'name' => true,
        'type' => true,
        'configuration' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Sensitive keys within the configuration JSON that must be masked in output.
     */
    private const SENSITIVE_CONFIG_KEYS = ['password', 'token', 'secret', 'api_key', 'auth_token', 'bot_token', 'routing_key', 'webhook_url'];

    /**
     * Get configuration as array (with sensitive values masked).
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        $raw = $this->_fields['configuration'] ?? null;
        if (empty($raw)) {
            return [];
        }

        if (is_array($raw)) {
            $config = $raw;
        } else {
            $decoded = json_decode($raw, true);
            $config = is_array($decoded) ? $decoded : [];
        }

        // Mask sensitive values so they are never leaked via API responses
        foreach (self::SENSITIVE_CONFIG_KEYS as $sensitiveKey) {
            if (isset($config[$sensitiveKey]) && !empty($config[$sensitiveKey])) {
                $config[$sensitiveKey] = '••••••••';
            }
        }

        return $config;
    }

    /**
     * Get raw configuration as array without masking (for internal use only).
     *
     * @return array
     */
    public function getRawConfiguration(): array
    {
        $raw = $this->_fields['configuration'] ?? null;
        if (empty($raw)) {
            return [];
        }

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set configuration from array
     *
     * @param array|string|null $value Configuration array or JSON string
     * @return array
     */
    protected function _setConfiguration(array|string|null $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return $value;
    }

    /**
     * Get the channel type identifier.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the channel name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get human-readable type name.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return match ($this->type) {
            self::TYPE_EMAIL => 'Email',
            self::TYPE_SLACK => 'Slack',
            self::TYPE_DISCORD => 'Discord',
            self::TYPE_TELEGRAM => 'Telegram',
            self::TYPE_SMS => 'SMS',
            self::TYPE_WHATSAPP => 'WhatsApp',
            self::TYPE_PAGERDUTY => 'PagerDuty',
            self::TYPE_OPSGENIE => 'OpsGenie',
            self::TYPE_WEBHOOK => 'Webhook',
            default => ucfirst($this->type),
        };
    }

    /**
     * Check if channel is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }
}
