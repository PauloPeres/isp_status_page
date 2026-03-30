<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertLog;
use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Model\Table\AlertLogsTable;
use App\Model\Table\AlertRulesTable;
use App\Service\MaintenanceService;
use App\Service\NotificationCreditService;
use App\Service\NotificationScheduleService;
use App\Service\QuietHoursService;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Alert Service
 *
 * Coordinates alert dispatching across all configured channels.
 * Finds applicable alert rules for a monitor/incident, checks
 * throttling, triggers the appropriate channel, and logs results.
 */
class AlertService
{
    use LocatorAwareTrait;

    /**
     * Registry of available alert channels
     *
     * @var array<string, \App\Service\Alert\ChannelInterface>
     */
    protected array $channels = [];

    /**
     * Alert rules table
     *
     * @var \App\Model\Table\AlertRulesTable
     */
    protected AlertRulesTable $AlertRules;

    /**
     * Alert logs table
     *
     * @var \App\Model\Table\AlertLogsTable
     */
    protected AlertLogsTable $AlertLogs;

    /**
     * Maintenance service for checking active maintenance windows
     *
     * @var \App\Service\MaintenanceService
     */
    protected MaintenanceService $maintenanceService;

    /**
     * Quiet hours service for checking notification suppression
     *
     * @var \App\Service\QuietHoursService
     */
    protected QuietHoursService $quietHoursService;
    protected NotificationScheduleService $scheduleService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->AlertRules = $this->fetchTable('AlertRules');
        $this->AlertLogs = $this->fetchTable('AlertLogs');
        $this->maintenanceService = new MaintenanceService();
        $this->quietHoursService = new QuietHoursService();
        $this->scheduleService = new NotificationScheduleService();
    }

    /**
     * Register an alert channel
     *
     * @param \App\Service\Alert\ChannelInterface $channel The channel to register
     * @return void
     */
    public function registerChannel(ChannelInterface $channel): void
    {
        $this->channels[$channel->getType()] = $channel;

        Log::debug("Registered alert channel: {$channel->getName()} (type: {$channel->getType()})");
    }

    /**
     * Get a registered channel by type
     *
     * @param string $type Channel type
     * @return \App\Service\Alert\ChannelInterface|null
     */
    public function getChannel(string $type): ?ChannelInterface
    {
        return $this->channels[$type] ?? null;
    }

    /**
     * Get all registered channels
     *
     * @return array<string, \App\Service\Alert\ChannelInterface>
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Dispatch alerts for a monitor/incident
     *
     * Finds applicable alert rules, checks throttling, triggers
     * the appropriate channel, and logs all results.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor that triggered the alert
     * @param \App\Model\Entity\Incident $incident The related incident
     * @return int Number of alerts dispatched
     */
    public function dispatch(Monitor $monitor, Incident $incident): int
    {
        try {
            // Stop sending alerts if incident is already acknowledged
            if ($incident->isAcknowledged()) {
                Log::debug("Incident {$incident->id} already acknowledged, skipping alert dispatch");

                return 0;
            }

            // Check if monitor is in a maintenance window with alert suppression
            if ($this->maintenanceService->shouldSuppressAlert((int)$monitor->id)) {
                Log::debug("Monitor {$monitor->id} is in maintenance window, suppressing alert dispatch");

                return 0;
            }

            // Check quiet hours — suppress alerts based on org settings and severity
            $orgId = (int)$monitor->organization_id;
            $severity = $incident->severity ?? 'warning';
            if ($this->quietHoursService->shouldSuppressAlert($orgId, $severity)) {
                Log::info("Alert suppressed by quiet hours for org {$orgId}, monitor {$monitor->id}, severity {$severity}");

                return 0;
            }

            $rules = $this->AlertRules->getActiveRulesForMonitor($monitor->id);

            if (empty($rules)) {
                Log::debug("No active alert rules for monitor {$monitor->id}");

                return 0;
            }

            $dispatched = 0;

            foreach ($rules as $rule) {
                try {
                    // Check if the rule should trigger for this monitor status
                    if (!$this->shouldTrigger($rule, $monitor)) {
                        Log::debug("Alert rule {$rule->id} should not trigger for monitor status '{$monitor->status}'");
                        continue;
                    }

                    // Check throttling
                    if (!$this->checkThrottle($rule)) {
                        Log::debug("Alert rule {$rule->id} throttled (cooldown: {$rule->throttle_minutes} min)");
                        continue;
                    }

                    // Get the channel for this rule
                    $channel = $this->getChannel($rule->channel);

                    if ($channel === null) {
                        Log::warning("No channel registered for type: {$rule->channel}");
                        $this->logAlert($rule, $incident, AlertLog::STATUS_FAILED, "Channel '{$rule->channel}' not registered");
                        continue;
                    }

                    // Check per-channel notification schedule (C-05)
                    $alertSeverity = $incident->severity ?? 'major';
                    if ($this->scheduleService->shouldSuppress((int)$monitor->organization_id, $rule->channel, $alertSeverity)) {
                        Log::debug("Alert rule {$rule->id} suppressed by notification schedule for channel {$rule->channel}");
                        $this->logAlert($rule, $incident, AlertLog::STATUS_SUPPRESSED ?? 'suppressed', "Suppressed by notification schedule");
                        continue;
                    }

                    // Check notification credits for paid channels (SMS, WhatsApp)
                    $creditService = new NotificationCreditService();
                    if ($creditService->getCostForChannel($rule->channel) > 0) {
                        $orgId = (int)$monitor->organization_id;
                        if (!$creditService->hasCredits($orgId, $rule->channel)) {
                            Log::warning("Insufficient credits for org {$orgId}, channel {$rule->channel}, falling back to email");
                            $this->logAlert($rule, $incident, AlertLog::STATUS_FAILED, "Insufficient credits for {$rule->channel}");

                            // Fall back to email channel
                            $emailChannel = $this->getChannel('email');
                            if ($emailChannel !== null) {
                                $emailChannel->send($rule, $monitor, $incident);
                            }

                            continue;
                        }
                    }

                    // Send the alert
                    $result = $channel->send($rule, $monitor, $incident);

                    // Log results per recipient
                    if (isset($result['results']) && is_array($result['results'])) {
                        foreach ($result['results'] as $recipientResult) {
                            $this->logAlert(
                                $rule,
                                $incident,
                                $recipientResult['status'] ?? AlertLog::STATUS_FAILED,
                                $recipientResult['error'] ?? null,
                                $recipientResult['recipient'] ?? ''
                            );
                        }
                    }

                    if (!empty($result['success'])) {
                        $dispatched++;

                        // Deduct credits after successful send on paid channels
                        if ($creditService->getCostForChannel($rule->channel) > 0) {
                            $orgId = (int)$monitor->organization_id;
                            $creditService->deduct($orgId, $rule->channel);
                        }
                    }

                    Log::info("Alert dispatched for rule {$rule->id} via {$rule->channel}", [
                        'monitor_id' => $monitor->id,
                        'incident_id' => $incident->id,
                        'success' => $result['success'] ?? false,
                    ]);
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch alert for rule {$rule->id}: {$e->getMessage()}");
                    $this->logAlert($rule, $incident, AlertLog::STATUS_FAILED, $e->getMessage());
                }
            }

            return $dispatched;
        } catch (\Exception $e) {
            Log::error("AlertService dispatch failed: {$e->getMessage()}");

            return 0;
        }
    }

    /**
     * Check if an alert rule should trigger based on the monitor's current status
     *
     * @param \App\Model\Entity\AlertRule $rule The alert rule
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @return bool True if the rule should trigger
     */
    public function shouldTrigger(AlertRule $rule, Monitor $monitor): bool
    {
        if (!$rule->isActive()) {
            return false;
        }

        return match ($rule->trigger_on) {
            AlertRule::TRIGGER_ON_DOWN => $monitor->status === Monitor::STATUS_DOWN,
            AlertRule::TRIGGER_ON_UP => $monitor->status === Monitor::STATUS_UP,
            AlertRule::TRIGGER_ON_DEGRADED => $monitor->status === Monitor::STATUS_DEGRADED,
            AlertRule::TRIGGER_ON_CHANGE => true,
            default => false,
        };
    }

    /**
     * Check if an alert rule is within its throttle/cooldown period
     *
     * @param \App\Model\Entity\AlertRule $rule The alert rule
     * @return bool True if the alert can be sent (not throttled)
     */
    public function checkThrottle(AlertRule $rule): bool
    {
        // No throttle configured
        if ($rule->throttle_minutes <= 0) {
            return true;
        }

        // Find the most recent successful alert log for this rule
        $lastLog = $this->AlertLogs->find()
            ->where([
                'AlertLogs.alert_rule_id' => $rule->id,
                'AlertLogs.status' => AlertLog::STATUS_SENT,
            ])
            ->orderBy(['AlertLogs.created' => 'DESC'])
            ->first();

        if ($lastLog === null) {
            return true; // No previous alert, allow sending
        }

        // Check if enough time has passed since last alert
        $cooldownEnd = $lastLog->created->modify("+{$rule->throttle_minutes} minutes");

        return DateTime::now()->greaterThan($cooldownEnd);
    }

    /**
     * Dispatch an alert directly to a specific channel with explicit recipients.
     * Used by the EscalationService to send escalation step alerts.
     *
     * @param string $channelType The channel type (email, slack, discord, etc.)
     * @param array $recipients List of recipients
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @param string $message Custom message to send
     * @return bool True if the alert was sent successfully
     */
    public function dispatchToChannel(
        string $channelType,
        array $recipients,
        Monitor $monitor,
        Incident $incident,
        string $message = '',
    ): bool {
        $channel = $this->getChannel($channelType);

        if ($channel === null) {
            Log::warning("Escalation: No channel registered for type: {$channelType}");

            return false;
        }

        try {
            // Create a temporary AlertRule-like entity for the channel
            $alertRulesTable = $this->fetchTable('AlertRules');
            $tempRule = $alertRulesTable->newEmptyEntity();
            $tempRule->channel = $channelType;
            $tempRule->recipients = json_encode($recipients);
            $tempRule->template = $message;
            $tempRule->monitor_id = $monitor->id;
            $tempRule->trigger_on = AlertRule::TRIGGER_ON_DOWN;
            $tempRule->active = true;
            $tempRule->throttle_minutes = 0;

            $result = $channel->send($tempRule, $monitor, $incident);

            return !empty($result['success']);
        } catch (\Exception $e) {
            Log::error("Escalation dispatchToChannel failed for {$channelType}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Log an alert dispatch result
     *
     * @param \App\Model\Entity\AlertRule $rule The alert rule
     * @param \App\Model\Entity\Incident $incident The incident
     * @param string $status The status ('sent', 'failed', 'queued')
     * @param string|null $error Optional error message
     * @param string $recipient The recipient address
     * @return \App\Model\Entity\AlertLog|false
     */
    public function logAlert(
        AlertRule $rule,
        Incident $incident,
        string $status,
        ?string $error = null,
        string $recipient = ''
    ): AlertLog|false {
        try {
            $logEntity = $this->AlertLogs->newEntity([
                'alert_rule_id' => $rule->id,
                'incident_id' => $incident->id,
                'monitor_id' => $rule->monitor_id,
                'channel' => $rule->channel,
                'recipient' => $recipient ?: 'unknown',
                'status' => $status,
                'sent_at' => $status === AlertLog::STATUS_SENT ? DateTime::now() : null,
                'error_message' => $error,
            ]);

            $saved = $this->AlertLogs->save($logEntity);

            if (!$saved) {
                Log::error('Failed to save alert log', [
                    'rule_id' => $rule->id,
                    'errors' => $logEntity->getErrors(),
                ]);

                return false;
            }

            return $saved;
        } catch (\Exception $e) {
            Log::error("Failed to log alert: {$e->getMessage()}");

            return false;
        }
    }
}
