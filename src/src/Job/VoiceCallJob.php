<?php
declare(strict_types=1);

namespace App\Job;

use App\Service\Voice\VoiceCallService;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Interop\Queue\Processor;

/**
 * Voice Call Job
 *
 * Processes a voice call alert by initiating the call via the
 * VoiceCallService. Runs asynchronously on the notifications queue.
 */
class VoiceCallJob implements JobInterface
{
    use LocatorAwareTrait;

    /**
     * Maximum number of retry attempts.
     *
     * @var int|null
     */
    public static ?int $maxAttempts = 3;

    /**
     * Execute the voice call job.
     *
     * @param \Cake\Queue\Job\Message $message The queue message
     * @return string Processor result (ACK or REJECT)
     */
    public function execute(Message $message): string
    {
        $data = $message->getArgument('data') ?? [];
        $callLogId = $data['voice_call_log_id'] ?? null;

        if ($callLogId === null) {
            Log::error('VoiceCallJob: Missing voice_call_log_id in message data');

            return Processor::REJECT;
        }

        try {
            $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');
            $callLog = $voiceCallLogsTable->get($callLogId);

            $voiceCallService = new VoiceCallService();
            $result = $voiceCallService->initiateCall($callLog);

            if ($result['success']) {
                Log::info("VoiceCallJob: Call initiated for log {$callLogId} to {$callLog->phone_number}");
            } else {
                Log::warning("VoiceCallJob: Call failed for log {$callLogId}: {$result['error']}");
            }

            return Processor::ACK;
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            Log::error("VoiceCallJob: VoiceCallLog {$callLogId} not found: {$e->getMessage()}");

            return Processor::REJECT;
        } catch (\Exception $e) {
            Log::error("VoiceCallJob: Failed for log {$callLogId}: {$e->getMessage()}");

            return Processor::REJECT;
        }
    }
}
