<?php
declare(strict_types=1);

namespace App\Job;

use App\Service\EscalationService;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Interop\Queue\Processor;

/**
 * Escalation Job
 *
 * Processes escalation for a single incident via the EscalationService.
 * Unique per incident to prevent duplicate escalation processing.
 */
class EscalationJob implements JobInterface
{
    use LocatorAwareTrait;

    /**
     * Whether only one instance of this job should exist per unique key.
     *
     * @var bool
     */
    public static bool $shouldBeUnique = true;

    /**
     * Maximum number of retry attempts.
     *
     * @var int|null
     */
    public static ?int $maxAttempts = 2;

    /**
     * Execute the escalation job.
     *
     * @param \Cake\Queue\Job\Message $message The queue message
     * @return string Processor result (ACK or REJECT)
     */
    public function execute(Message $message): string
    {
        $data = $message->getArgument('data') ?? [];
        $incidentId = $data['incident_id'] ?? null;

        if ($incidentId === null) {
            Log::error('EscalationJob: Missing incident_id in message data');

            return Processor::REJECT;
        }

        try {
            // Load the incident
            $incidentsTable = $this->fetchTable('Incidents');
            $incident = $incidentsTable->get($incidentId);

            // Skip if incident is already resolved or acknowledged
            if ($incident->resolved_at !== null) {
                Log::debug("EscalationJob: Incident {$incidentId} already resolved, skipping");

                return Processor::ACK;
            }

            if ($incident->isAcknowledged()) {
                Log::debug("EscalationJob: Incident {$incidentId} already acknowledged, skipping");

                return Processor::ACK;
            }

            // Process escalation
            $escalationService = new EscalationService();
            $result = $escalationService->processEscalation($incident);

            if ($result !== null) {
                Log::info("EscalationJob: {$result} for incident {$incidentId}");
            } else {
                Log::debug("EscalationJob: No escalation action needed for incident {$incidentId}");
            }

            return Processor::ACK;
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            Log::error("EscalationJob: Incident {$incidentId} not found: {$e->getMessage()}");

            return Processor::REJECT;
        } catch (\Exception $e) {
            Log::error("EscalationJob: Failed for incident {$incidentId}: {$e->getMessage()}");

            return Processor::REJECT;
        }
    }
}
