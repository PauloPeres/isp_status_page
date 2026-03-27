<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertLog;
use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Model\Table\AlertLogsTable;
use App\Model\Table\AlertRulesTable;
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
     * Constructor
     */
    public function __construct()
    {
        $this->AlertRules = $this->fetchTable('AlertRules');
        $this->AlertLogs = $this->fetchTable('AlertLogs');
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
