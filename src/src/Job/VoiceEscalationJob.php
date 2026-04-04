<?php
declare(strict_types=1);

namespace App\Job;

use App\Model\Entity\VoiceCallLog;
use App\Service\Voice\VoiceCallService;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Cake\Queue\QueueManager;
use Interop\Queue\Processor;

/**
 * Voice Escalation Job
 *
 * Calls the next person in the voice call escalation chain after
 * a call ends without acknowledgement (no-answer, busy, failed,
 * or explicit escalation via DTMF).
 */
class VoiceEscalationJob implements JobInterface
{
    use LocatorAwareTrait;

    /**
     * Whether this job should be unique in the queue.
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
     * Execute the voice escalation job.
     *
     * @param \Cake\Queue\Job\Message $message The queue message
     * @return string Processor result (ACK or REJECT)
     */
    public function execute(Message $message): string
    {
        $data = $message->getArgument('data') ?? [];
        $callLogId = $data['voice_call_log_id'] ?? null;

        if ($callLogId === null) {
            Log::error('VoiceEscalationJob: Missing voice_call_log_id in message data');

            return Processor::REJECT;
        }

        try {
            $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');
            $callLog = $voiceCallLogsTable->get($callLogId);

            // Check if the incident is already resolved or acknowledged
            $incidentsTable = $this->fetchTable('Incidents');
            $incident = $incidentsTable->get($callLog->incident_id);

            if ($incident->isResolved()) {
                Log::info("VoiceEscalationJob: Incident {$callLog->incident_id} is already resolved, skipping escalation");

                return Processor::ACK;
            }

            if ($incident->isAcknowledged()) {
                Log::info("VoiceEscalationJob: Incident {$callLog->incident_id} is already acknowledged, skipping escalation");

                return Processor::ACK;
            }

            // Find the next position in the escalation chain
            $nextPosition = $callLog->escalation_position + 1;

            /** @var \App\Model\Entity\VoiceCallLog|null $nextCallLog */
            $nextCallLog = $voiceCallLogsTable->find()
                ->where([
                    'VoiceCallLogs.incident_id' => $callLog->incident_id,
                    'VoiceCallLogs.escalation_position' => $nextPosition,
                ])
                ->first();

            if ($nextCallLog === null) {
                Log::info("VoiceEscalationJob: Voice escalation exhausted for incident {$callLog->incident_id} — no person at position {$nextPosition}");

                return Processor::ACK;
            }

            // If the next call log is already in a terminal state, skip
            if ($nextCallLog->isTerminal()) {
                Log::info("VoiceEscalationJob: Next call log {$nextCallLog->id} at position {$nextPosition} is already terminal, skipping");

                return Processor::ACK;
            }

            // Push a VoiceCallJob for the next person
            QueueManager::push(VoiceCallJob::class, [
                'data' => [
                    'voice_call_log_id' => $nextCallLog->id,
                ],
            ], ['config' => 'notifications']);

            Log::info("VoiceEscalationJob: Escalated to position {$nextPosition} (call log {$nextCallLog->id}) for incident {$callLog->incident_id}");

            return Processor::ACK;
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            Log::error("VoiceEscalationJob: Record not found for call log {$callLogId}: {$e->getMessage()}");

            return Processor::REJECT;
        } catch (\Exception $e) {
            Log::error("VoiceEscalationJob: Failed for call log {$callLogId}: {$e->getMessage()}");

            return Processor::REJECT;
        }
    }
}
