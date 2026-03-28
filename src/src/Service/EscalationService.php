<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\EscalationStep;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Service\Alert\AlertService;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Escalation Service
 *
 * Processes escalation policies for unacknowledged incidents.
 * Each escalation policy has ordered steps that trigger at specified
 * time intervals after the incident starts, sending alerts via
 * configured channels if the incident hasn't been acknowledged.
 */
class EscalationService
{
    use LocatorAwareTrait;

    /**
     * Alert service for dispatching alerts.
     *
     * @var \App\Service\Alert\AlertService
     */
    protected AlertService $alertService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->alertService = new AlertService();
    }

    /**
     * Process escalation for an unacknowledged incident.
     * Called by the EscalationCheckCommand cron job.
     *
     * @param \App\Model\Entity\Incident $incident The unacknowledged incident
     * @return string|null Description of what was executed, or null if nothing was done
     */
    public function processEscalation(Incident $incident): ?string
    {
        try {
            $monitorsTable = $this->fetchTable('Monitors');
            $monitor = $monitorsTable->get($incident->monitor_id);

            if (!$monitor->escalation_policy_id) {
                return null;
            }

            $policiesTable = $this->fetchTable('EscalationPolicies');
            $policy = $policiesTable->get(
                $monitor->escalation_policy_id,
                contain: ['EscalationSteps' => ['sort' => ['EscalationSteps.step_number' => 'ASC']]]
            );

            if (!$policy->active) {
                return null;
            }

            if (empty($policy->escalation_steps)) {
                return null;
            }

            // Determine minutes since incident started
            $minutesSinceStart = (int)$incident->started_at->diffInMinutes(DateTime::now());

            // Find the current step to execute based on time elapsed
            $currentStep = null;
            foreach ($policy->escalation_steps as $step) {
                if ($minutesSinceStart >= $step->wait_minutes) {
                    $currentStep = $step;
                }
            }

            if (!$currentStep) {
                return null;
            }

            // Check if this step was already executed for this incident
            if ($this->wasStepAlreadyExecuted($incident->id, $currentStep->id)) {
                // Check if repeat is enabled and all steps have been executed
                if ($policy->repeat_enabled) {
                    return $this->handleRepeat($policy, $incident, $monitor, $minutesSinceStart);
                }

                return null;
            }

            // Execute the step
            $this->executeStep($currentStep, $monitor, $incident);

            // Log the execution
            $this->logStepExecution($incident->id, $monitor->id, $currentStep->id, $currentStep->step_number, $currentStep->channel);

            return "Executed step {$currentStep->step_number}: {$currentStep->channel} to {$currentStep->getRecipientsSummary()}";
        } catch (\Exception $e) {
            Log::error("Escalation error for incident {$incident->id}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Handle repeat escalation cycle after all steps have been completed.
     *
     * @param \App\Model\Entity\EscalationPolicy $policy The escalation policy
     * @param \App\Model\Entity\Incident $incident The incident
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param int $minutesSinceStart Minutes since incident started
     * @return string|null
     */
    private function handleRepeat(
        \App\Model\Entity\EscalationPolicy $policy,
        Incident $incident,
        Monitor $monitor,
        int $minutesSinceStart,
    ): ?string {
        $lastStep = end($policy->escalation_steps);
        if (!$lastStep) {
            return null;
        }

        // Calculate when the repeat cycle should start
        $repeatStartMinutes = $lastStep->wait_minutes + $policy->repeat_after_minutes;
        if ($minutesSinceStart < $repeatStartMinutes) {
            return null;
        }

        // Find which step in the repeat cycle we should execute
        $cycleMinutes = $minutesSinceStart - $repeatStartMinutes;
        $repeatStep = null;
        foreach ($policy->escalation_steps as $step) {
            if ($cycleMinutes >= $step->wait_minutes) {
                $repeatStep = $step;
            }
        }

        if (!$repeatStep) {
            return null;
        }

        // Check if this repeat step was already executed (use a repeat-specific key)
        $repeatKey = "repeat_{$repeatStartMinutes}_{$repeatStep->id}";
        if ($this->wasRepeatStepExecuted($incident->id, $repeatKey)) {
            return null;
        }

        $this->executeStep($repeatStep, $monitor, $incident);
        $this->logStepExecution($incident->id, $monitor->id, $repeatStep->id, $repeatStep->step_number, $repeatStep->channel, $repeatKey);

        return "Repeat - Executed step {$repeatStep->step_number}: {$repeatStep->channel}";
    }

    /**
     * Check if a specific escalation step was already executed for an incident.
     * Uses the error_message field in alert_logs to track escalation metadata.
     *
     * @param int $incidentId The incident ID
     * @param int $stepId The escalation step ID
     * @return bool
     */
    private function wasStepAlreadyExecuted(int $incidentId, int $stepId): bool
    {
        $alertLogsTable = $this->fetchTable('AlertLogs');

        return $alertLogsTable->find()
            ->where([
                'AlertLogs.incident_id' => $incidentId,
                'AlertLogs.recipient LIKE' => 'escalation:step_%',
                'AlertLogs.error_message LIKE' => '%"escalation_step_id":' . $stepId . '%',
            ])
            ->count() > 0;
    }

    /**
     * Check if a repeat escalation step was already executed.
     *
     * @param int $incidentId The incident ID
     * @param string $repeatKey Unique key for this repeat execution
     * @return bool
     */
    private function wasRepeatStepExecuted(int $incidentId, string $repeatKey): bool
    {
        $alertLogsTable = $this->fetchTable('AlertLogs');

        return $alertLogsTable->find()
            ->where([
                'AlertLogs.incident_id' => $incidentId,
                'AlertLogs.recipient LIKE' => 'escalation:step_%',
                'AlertLogs.error_message LIKE' => '%"repeat_key":"' . $repeatKey . '"%',
            ])
            ->count() > 0;
    }

    /**
     * Execute an escalation step by dispatching an alert via the configured channel.
     *
     * @param \App\Model\Entity\EscalationStep $step The escalation step
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return void
     */
    private function executeStep(EscalationStep $step, Monitor $monitor, Incident $incident): void
    {
        $recipients = $step->getRecipients();
        $channel = $step->channel;
        $minutesSinceStart = (int)$incident->started_at->diffInMinutes(DateTime::now());

        $message = $step->message_template ?: sprintf(
            '[ESCALATION Step %d] %s is %s for %d minutes. Monitor: %s. Incident: %s',
            $step->step_number,
            $monitor->name,
            $monitor->status,
            $minutesSinceStart,
            $monitor->name,
            $incident->title
        );

        Log::info(sprintf(
            'Escalation: Executing step %d (%s) for incident #%d, monitor "%s" - %d min since start',
            $step->step_number,
            $channel,
            $incident->id,
            $monitor->name,
            $minutesSinceStart
        ));

        // Use the AlertService channel infrastructure to dispatch the alert
        try {
            $this->alertService->dispatchToChannel($channel, $recipients, $monitor, $incident, $message);
        } catch (\Exception $e) {
            Log::error(sprintf(
                'Escalation: Failed to execute step %d (%s) for incident #%d: %s',
                $step->step_number,
                $channel,
                $incident->id,
                $e->getMessage()
            ));
        }
    }

    /**
     * Log the execution of an escalation step.
     * Uses the alert_logs table with a special recipient marker to distinguish
     * escalation logs from regular alert logs.
     *
     * @param int $incidentId The incident ID
     * @param int $monitorId The monitor ID
     * @param int $stepId The escalation step ID
     * @param int $stepNumber The step number
     * @param string $channel The alert channel used
     * @param string|null $repeatKey Optional repeat key for repeat cycles
     * @return void
     */
    private function logStepExecution(
        int $incidentId,
        int $monitorId,
        int $stepId,
        int $stepNumber,
        string $channel,
        ?string $repeatKey = null,
    ): void {
        try {
            $alertLogsTable = $this->fetchTable('AlertLogs');

            // Build metadata JSON for the error_message field
            $metadata = [
                'escalation_step_id' => $stepId,
                'escalation_step_number' => $stepNumber,
                'source' => 'escalation',
            ];
            if ($repeatKey) {
                $metadata['repeat_key'] = $repeatKey;
            }

            // Find any alert_rule_id for this monitor to satisfy the NOT NULL constraint
            $alertRulesTable = $this->fetchTable('AlertRules');
            $alertRule = $alertRulesTable->find()
                ->where(['AlertRules.monitor_id' => $monitorId])
                ->first();

            $alertRuleId = $alertRule ? $alertRule->id : null;

            // If no alert rule exists, we cannot create a log entry due to FK constraint.
            // Use a direct insert to bypass validation if needed.
            if (!$alertRuleId) {
                Log::info("Escalation: No alert rule found for monitor {$monitorId}, skipping log entry");

                return;
            }

            $log = $alertLogsTable->newEntity([
                'alert_rule_id' => $alertRuleId,
                'incident_id' => $incidentId,
                'monitor_id' => $monitorId,
                'channel' => $channel,
                'recipient' => 'escalation:step_' . $stepNumber,
                'status' => 'sent',
                'error_message' => json_encode($metadata),
                'sent_at' => DateTime::now(),
            ]);

            $alertLogsTable->save($log);
        } catch (\Exception $e) {
            Log::error("Failed to log escalation step execution: {$e->getMessage()}");
        }
    }
}
