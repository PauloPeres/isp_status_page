<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * Incident Entity
 *
 * @property int $id
 * @property int $monitor_id
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property string $severity
 * @property \Cake\I18n\DateTime $started_at
 * @property \Cake\I18n\DateTime|null $identified_at
 * @property \Cake\I18n\DateTime|null $resolved_at
 * @property int|null $duration
 * @property bool $auto_created
 * @property int|null $acknowledged_by_user_id
 * @property \Cake\I18n\DateTime|null $acknowledged_at
 * @property string|null $acknowledged_via
 * @property string|null $acknowledgement_token
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Monitor $monitor
 * @property \App\Model\Entity\User|null $acknowledged_by_user
 * @property \App\Model\Entity\AlertLog[] $alert_logs
 * @property \App\Model\Entity\IncidentUpdate[] $incident_updates
 */
class Incident extends Entity
{
    /**
     * Incident statuses
     */
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_IDENTIFIED = 'identified';
    public const STATUS_MONITORING = 'monitoring';
    public const STATUS_RESOLVED = 'resolved';

    /**
     * Acknowledgement channels
     */
    public const ACK_VIA_EMAIL = 'email';
    public const ACK_VIA_WEB = 'web';
    public const ACK_VIA_TELEGRAM = 'telegram';
    public const ACK_VIA_SMS = 'sms';
    public const ACK_VIA_VOICE_CALL = 'voice_call';

    /**
     * Token expiry in hours
     */
    public const TOKEN_EXPIRY_HOURS = 24;

    /**
     * Incident severities
     */
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_MAJOR = 'major';
    public const SEVERITY_MINOR = 'minor';
    public const SEVERITY_MAINTENANCE = 'maintenance';

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
        'public_id' => true,
        'organization_id' => true,
        'monitor_id' => true,
        'title' => true,
        'description' => true,
        'status' => true,
        'severity' => true,
        'started_at' => true,
        'identified_at' => true,
        'resolved_at' => true,
        'duration' => true,
        'auto_created' => true,
        'acknowledged_by_user_id' => false,
        'acknowledged_at' => false,
        'acknowledged_via' => false,
        'acknowledgement_token' => false,
        'created' => true,
        'modified' => true,
        'monitor' => true,
        'acknowledged_by_user' => true,
        'alert_logs' => true,
        'incident_updates' => true,
    ];

    /**
     * Fields that are excluded from JSON / array representations.
     *
     * @var list<string>
     */
    protected array $_hidden = [
        'acknowledgement_token',
    ];

    /**
     * Check if incident is resolved
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if incident is ongoing
     *
     * @return bool
     */
    public function isOngoing(): bool
    {
        return !$this->isResolved();
    }

    /**
     * Get severity badge class for UI
     *
     * @return string
     */
    public function getSeverityBadgeClass(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_MAJOR => 'warning',
            self::SEVERITY_MINOR => 'info',
            self::SEVERITY_MAINTENANCE => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get human-readable status name
     *
     * @return string
     */
    public function getStatusName(): string
    {
        return match ($this->status) {
            self::STATUS_INVESTIGATING => 'Investigating',
            self::STATUS_IDENTIFIED => 'Identified',
            self::STATUS_MONITORING => 'Monitoring',
            self::STATUS_RESOLVED => 'Resolved',
            default => 'Unknown',
        };
    }

    /**
     * Check if incident has been acknowledged
     *
     * @return bool
     */
    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    /**
     * Acknowledge this incident by a user
     *
     * Only the first acknowledgement is accepted. Subsequent calls return false.
     *
     * @param int|null $userId The user ID who acknowledged (null for token-based)
     * @param string $via The channel used to acknowledge ('email', 'web', 'telegram', 'sms')
     * @return bool True if acknowledged, false if already acknowledged
     */
    public function acknowledgeBy(?int $userId, string $via): bool
    {
        // Only first acknowledgement is accepted
        if ($this->isAcknowledged()) {
            return false;
        }

        $this->acknowledged_by_user_id = $userId;
        $this->acknowledged_at = DateTime::now();
        $this->acknowledged_via = $via;

        return true;
    }

    /**
     * Generate a secure acknowledgement token
     *
     * @return string The generated token
     */
    public function generateAcknowledgementToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->acknowledgement_token = $token;

        return $token;
    }

    /**
     * Check if the acknowledgement token is still valid (not expired)
     *
     * Token expires 24h after incident creation.
     *
     * @return bool
     */
    public function isTokenValid(): bool
    {
        if (empty($this->acknowledgement_token)) {
            return false;
        }

        $expiresAt = $this->created->modify('+' . self::TOKEN_EXPIRY_HOURS . ' hours');

        return DateTime::now()->lessThan($expiresAt);
    }
}
