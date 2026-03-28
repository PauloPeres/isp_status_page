<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * IncidentUpdate Entity
 *
 * Represents a single update/event in an incident's timeline.
 * Updates can be posted by team members or generated automatically
 * by the system when incidents are created, resolved, or acknowledged.
 *
 * @property int $id
 * @property int $incident_id
 * @property int $organization_id
 * @property int|null $user_id
 * @property string $status
 * @property string $message
 * @property bool $is_public
 * @property string $source
 * @property \Cake\I18n\DateTime $created
 *
 * @property \App\Model\Entity\Incident $incident
 * @property \App\Model\Entity\User|null $user
 * @property \App\Model\Entity\Organization $organization
 */
class IncidentUpdate extends Entity
{
    /**
     * Status constants
     */
    public const STATUS_INVESTIGATING = 'investigating';
    public const STATUS_IDENTIFIED = 'identified';
    public const STATUS_MONITORING = 'monitoring';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_UPDATE = 'update';

    /**
     * Source constants
     */
    public const SOURCE_WEB = 'web';
    public const SOURCE_API = 'api';
    public const SOURCE_SYSTEM = 'system';
    public const SOURCE_EMAIL = 'email';
    public const SOURCE_TELEGRAM = 'telegram';
    public const SOURCE_SMS = 'sms';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'incident_id' => true,
        'organization_id' => true,
        'user_id' => true,
        'status' => true,
        'message' => true,
        'is_public' => true,
        'source' => true,
        'created' => true,
        'incident' => true,
        'user' => true,
        'organization' => true,
    ];

    /**
     * Get the CSS badge class for this update's status
     *
     * @return string
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_INVESTIGATING => 'warning',
            self::STATUS_IDENTIFIED => 'info',
            self::STATUS_MONITORING => 'info',
            self::STATUS_RESOLVED => 'success',
            self::STATUS_UPDATE => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Get human-readable status label
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_INVESTIGATING => 'Investigating',
            self::STATUS_IDENTIFIED => 'Identified',
            self::STATUS_MONITORING => 'Monitoring',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_UPDATE => 'Update',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if this update is a status change (not just a general update)
     *
     * @return bool
     */
    public function isStatusChange(): bool
    {
        return in_array($this->status, [
            self::STATUS_INVESTIGATING,
            self::STATUS_IDENTIFIED,
            self::STATUS_MONITORING,
            self::STATUS_RESOLVED,
        ]);
    }
}
