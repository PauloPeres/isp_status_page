<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;

/**
 * Channel Interface
 *
 * Defines the contract that all alert channels must implement.
 * Each channel is responsible for delivering alert notifications
 * via a specific medium (email, SMS, Telegram, etc).
 */
interface ChannelInterface
{
    /**
     * Send an alert notification
     *
     * @param \App\Model\Entity\AlertRule $rule The alert rule being triggered
     * @param \App\Model\Entity\Monitor $monitor The monitor that triggered the alert
     * @param \App\Model\Entity\Incident $incident The related incident
     * @return array Result with keys:
     *   - success: bool Whether all sends succeeded
     *   - results: array Per-recipient results with keys:
     *     - recipient: string
     *     - status: string ('sent'|'failed')
     *     - error: string|null Error message if failed
     */
    public function send(AlertRule $rule, Monitor $monitor, Incident $incident): array;

    /**
     * Get the channel type identifier
     *
     * @return string Channel type (e.g., 'email', 'telegram', 'sms')
     */
    public function getType(): string;

    /**
     * Get human-readable name for this channel
     *
     * @return string Channel display name
     */
    public function getName(): string;
}
