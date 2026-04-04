<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * VoiceCallLog Entity
 *
 * Tracks individual voice call attempts for alert notifications.
 *
 * @property int $id
 * @property int $organization_id
 * @property int|null $user_id
 * @property int $incident_id
 * @property int $monitor_id
 * @property int|null $notification_channel_id
 * @property string $phone_number
 * @property string|null $call_sid
 * @property string $status
 * @property string|null $dtmf_input
 * @property int|null $duration_seconds
 * @property string $tts_language
 * @property string|null $tts_message
 * @property int $cost_credits
 * @property string $sip_provider
 * @property int $escalation_position
 * @property string $public_id
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \App\Model\Entity\Organization $organization
 * @property \App\Model\Entity\Incident $incident
 * @property \App\Model\Entity\Monitor $monitor
 * @property \App\Model\Entity\NotificationChannel|null $notification_channel
 */
class VoiceCallLog extends Entity
{
    /**
     * Call status constants
     */
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_RINGING = 'ringing';
    public const STATUS_ANSWERED = 'answered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_ANSWER = 'no-answer';
    public const STATUS_BUSY = 'busy';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';

    /**
     * All valid statuses
     */
    public const VALID_STATUSES = [
        self::STATUS_INITIATED,
        self::STATUS_RINGING,
        self::STATUS_ANSWERED,
        self::STATUS_COMPLETED,
        self::STATUS_NO_ANSWER,
        self::STATUS_BUSY,
        self::STATUS_FAILED,
        self::STATUS_CANCELED,
    ];

    /**
     * DTMF input: acknowledge
     */
    public const DTMF_ACKNOWLEDGE = '1';

    /**
     * DTMF input: escalate
     */
    public const DTMF_ESCALATE = '2';

    /**
     * Credits cost per voice call
     */
    public const CREDITS_PER_CALL = 3;

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'organization_id' => false,
        'user_id' => true,
        'incident_id' => true,
        'monitor_id' => true,
        'notification_channel_id' => true,
        'phone_number' => true,
        'call_sid' => true,
        'status' => true,
        'dtmf_input' => true,
        'duration_seconds' => true,
        'tts_language' => true,
        'tts_message' => true,
        'cost_credits' => true,
        'sip_provider' => true,
        'escalation_position' => true,
        'public_id' => false,
        'created' => false,
        'modified' => false,
    ];

    /**
     * Check if the call was answered.
     *
     * @return bool
     */
    public function wasAnswered(): bool
    {
        return in_array($this->status, [self::STATUS_ANSWERED, self::STATUS_COMPLETED], true);
    }

    /**
     * Check if the call was acknowledged via DTMF.
     *
     * @return bool
     */
    public function wasAcknowledged(): bool
    {
        return $this->dtmf_input === self::DTMF_ACKNOWLEDGE;
    }

    /**
     * Check if the call was escalated via DTMF.
     *
     * @return bool
     */
    public function wasEscalated(): bool
    {
        return $this->dtmf_input === self::DTMF_ESCALATE;
    }

    /**
     * Check if the call is in a terminal state.
     *
     * @return bool
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_NO_ANSWER,
            self::STATUS_BUSY,
            self::STATUS_FAILED,
            self::STATUS_CANCELED,
        ], true);
    }
}
