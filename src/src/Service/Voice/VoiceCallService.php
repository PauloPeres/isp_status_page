<?php
declare(strict_types=1);

namespace App\Service\Voice;

use App\Model\Entity\VoiceCallLog;
use App\Service\Billing\NotificationCreditService;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\QueueManager;
use Cake\Routing\Router;
use Cake\Utility\Text;
use App\Job\VoiceEscalationJob;

/**
 * Voice Call Service
 *
 * Orchestrates voice call alert delivery: resolves the SIP provider,
 * manages call chains (escalation), tracks call state, and handles
 * post-call actions like credit charging and escalation.
 */
class VoiceCallService
{
    use LocatorAwareTrait;

    /**
     * TTS message builder
     *
     * @var \App\Service\Voice\TtsMessageBuilder
     */
    private TtsMessageBuilder $ttsBuilder;

    /**
     * Constructor
     *
     * @param \App\Service\Voice\TtsMessageBuilder|null $ttsBuilder TTS builder instance
     */
    public function __construct(?TtsMessageBuilder $ttsBuilder = null)
    {
        $this->ttsBuilder = $ttsBuilder ?? new TtsMessageBuilder();
    }

    /**
     * Resolve the appropriate SIP provider for an organization.
     *
     * If the organization has a custom SipConfiguration, use CustomSipProvider.
     * Otherwise, fall back to TwilioVoiceProvider.
     *
     * @param int $orgId Organization ID
     * @return \App\Service\Voice\SipProviderInterface
     */
    public function resolveProvider(int $orgId): SipProviderInterface
    {
        $sipConfigTable = $this->fetchTable('SipConfigurations');

        $sipConfig = $sipConfigTable->find()
            ->where([
                'organization_id' => $orgId,
                'active' => true,
            ])
            ->first();

        if ($sipConfig !== null && $sipConfig->provider !== 'keepup_default') {
            return new CustomSipProvider($sipConfig);
        }

        return new TwilioVoiceProvider();
    }

    /**
     * Initiate a voice call chain for an incident.
     *
     * Creates VoiceCallLog records and queues calls for each phone number
     * in the escalation order.
     *
     * @param int $orgId Organization ID
     * @param int $incidentId Incident ID
     * @param int $monitorId Monitor ID
     * @param array<string> $phoneNumbers Phone numbers in escalation order
     * @param string $locale Language locale (en, pt_BR, es)
     * @param int|null $channelId Notification channel ID
     * @return array<array{phone_number: string, call_log_id: int|null, status: string, error: string|null}>
     */
    public function initiateCallChain(
        int $orgId,
        int $incidentId,
        int $monitorId,
        array $phoneNumbers,
        string $locale,
        ?int $channelId = null
    ): array {
        $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');
        $results = [];

        // Load monitor and incident for TTS message
        $monitor = $this->fetchTable('Monitors')->get($monitorId);
        $incident = $this->fetchTable('Incidents')->get($incidentId);

        // Build TTS message
        $isDown = $incident->isOngoing();
        $ttsMessage = $isDown
            ? $this->ttsBuilder->buildDownMessage($monitor, $incident, $locale)
            : $this->ttsBuilder->buildResolvedMessage($monitor, $locale);

        // Resolve provider
        $provider = $this->resolveProvider($orgId);

        foreach ($phoneNumbers as $position => $phoneNumber) {
            $phoneNumber = trim($phoneNumber);
            if (empty($phoneNumber)) {
                continue;
            }

            // Validate E.164 phone number format to prevent toll fraud / SSRF
            if (!preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber)) {
                Log::warning("VoiceCallService: Invalid phone number format: " . substr($phoneNumber, 0, 6) . '...');
                $results[] = [
                    'phone_number' => $phoneNumber,
                    'call_log_id' => null,
                    'status' => 'failed',
                    'error' => 'Invalid phone number format (must be E.164)',
                ];
                continue;
            }

            // Create VoiceCallLog record
            $callLog = $voiceCallLogsTable->newEntity([
                'incident_id' => $incidentId,
                'monitor_id' => $monitorId,
                'notification_channel_id' => $channelId,
                'phone_number' => $phoneNumber,
                'status' => VoiceCallLog::STATUS_INITIATED,
                'tts_language' => $locale,
                'tts_message' => $ttsMessage,
                'cost_credits' => 0,
                'sip_provider' => $provider->getProviderName(),
                'escalation_position' => $position,
            ]);
            $callLog->set('organization_id', $orgId);

            $saved = $voiceCallLogsTable->save($callLog);

            if ($saved === false) {
                Log::error("VoiceCallService: Failed to save VoiceCallLog for {$phoneNumber}");
                $results[] = [
                    'phone_number' => $phoneNumber,
                    'call_log_id' => null,
                    'status' => 'failed',
                    'error' => 'Failed to create call log record',
                ];
                continue;
            }

            // Only initiate the first call; subsequent ones are escalation targets
            if ($position === 0) {
                $callResult = $this->initiateCall($callLog);
                $results[] = [
                    'phone_number' => $phoneNumber,
                    'call_log_id' => $callLog->id,
                    'status' => $callResult['success'] ? 'initiated' : 'failed',
                    'error' => $callResult['error'] ?? null,
                ];
            } else {
                $results[] = [
                    'phone_number' => $phoneNumber,
                    'call_log_id' => $callLog->id,
                    'status' => 'queued',
                    'error' => null,
                ];
            }
        }

        return $results;
    }

    /**
     * Initiate a single voice call from a VoiceCallLog record.
     *
     * @param \App\Model\Entity\VoiceCallLog $callLog The call log entity
     * @return array{success: bool, error: string|null}
     */
    public function initiateCall(VoiceCallLog $callLog): array
    {
        $provider = $this->resolveProvider($callLog->organization_id);
        $webhookUrls = $this->buildWebhookUrls($callLog->public_id);

        $callerId = '';
        $sipConfigTable = $this->fetchTable('SipConfigurations');
        $sipConfig = $sipConfigTable->find()
            ->where([
                'organization_id' => $callLog->organization_id,
                'active' => true,
            ])
            ->first();

        if ($sipConfig !== null && !empty($sipConfig->caller_id)) {
            $callerId = $sipConfig->caller_id;
        } elseif (!empty(env('TWILIO_VOICE_NUMBER'))) {
            $callerId = (string)env('TWILIO_VOICE_NUMBER');
        }

        $result = $provider->initiateCall(
            $callLog->phone_number,
            $webhookUrls['answer_url'],
            $webhookUrls['status_url'],
            $callerId
        );

        // Update call log with provider's call SID
        $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');

        if ($result['success'] && !empty($result['call_sid'])) {
            $callLog->call_sid = $result['call_sid'];
            $callLog->status = VoiceCallLog::STATUS_INITIATED;
        } else {
            $callLog->status = VoiceCallLog::STATUS_FAILED;
        }

        $voiceCallLogsTable->save($callLog);

        return [
            'success' => $result['success'],
            'error' => $result['error'] ?? null,
        ];
    }

    /**
     * Handle a completed call — charge credits.
     *
     * @param \App\Model\Entity\VoiceCallLog $callLog The completed call log
     * @return void
     */
    public function handleCallCompleted(VoiceCallLog $callLog): void
    {
        // Idempotency guard: if credits were already charged, do not charge again.
        // This prevents double-charging if the webhook fires more than once.
        if ($callLog->cost_credits > 0) {
            Log::debug("VoiceCallService: Credits already charged for call log {$callLog->id}, skipping");

            return;
        }

        $creditService = new NotificationCreditService();

        // Charge credits for answered calls
        if ($callLog->wasAnswered()) {
            // Mask phone number in transaction description to avoid storing PII
            $phone = $callLog->phone_number;
            $maskedPhone = strlen($phone) > 6
                ? substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 7) . substr($phone, -4)
                : '***';

            $deducted = $creditService->deductCredits(
                $callLog->organization_id,
                VoiceCallLog::CREDITS_PER_CALL,
                'voice_call',
                "Voice call to {$maskedPhone} for incident #{$callLog->incident_id}"
            );

            if ($deducted) {
                $callLog->cost_credits = VoiceCallLog::CREDITS_PER_CALL;
                $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');
                $voiceCallLogsTable->save($callLog);
            }
        }
    }

    /**
     * Handle a no-answer call — escalate to the next person.
     *
     * @param \App\Model\Entity\VoiceCallLog $callLog The unanswered call log
     * @return void
     */
    public function handleNoAnswer(VoiceCallLog $callLog): void
    {
        try {
            QueueManager::push(VoiceEscalationJob::class, [
                'data' => [
                    'voice_call_log_id' => $callLog->id,
                ],
            ], ['config' => 'notifications']);

            Log::info("VoiceCallService: Pushed VoiceEscalationJob for call log {$callLog->id} (incident {$callLog->incident_id})");
        } catch (\Exception $e) {
            Log::error("VoiceCallService: Failed to push VoiceEscalationJob for call log {$callLog->id}: {$e->getMessage()}");

            // Fallback: try direct escalation
            $this->handleNoAnswerDirect($callLog);
        }
    }

    /**
     * Direct escalation fallback (no queue).
     *
     * Used when the queue is unavailable.
     *
     * @param \App\Model\Entity\VoiceCallLog $callLog The unanswered call log
     * @return void
     */
    private function handleNoAnswerDirect(VoiceCallLog $callLog): void
    {
        $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');

        // Find the next call in the escalation chain
        $nextCall = $voiceCallLogsTable->find()
            ->where([
                'incident_id' => $callLog->incident_id,
                'escalation_position' => $callLog->escalation_position + 1,
            ])
            ->first();

        if ($nextCall === null) {
            Log::info("VoiceCallService: No more escalation targets for incident {$callLog->incident_id}");

            return;
        }

        if ($nextCall->isTerminal()) {
            Log::info("VoiceCallService: Next call log {$nextCall->id} is already terminal, skipping");

            return;
        }

        $result = $this->initiateCall($nextCall);

        if ($result['success']) {
            Log::info("VoiceCallService: Escalated to position {$nextCall->escalation_position} for incident {$callLog->incident_id}");
        } else {
            Log::error("VoiceCallService: Failed to escalate to position {$nextCall->escalation_position}: {$result['error']}");
        }
    }

    /**
     * Build webhook URLs for a call log.
     *
     * @param string $callLogPublicId The call log's public UUID
     * @return array{answer_url: string, dtmf_url: string, status_url: string}
     */
    public function buildWebhookUrls(string $callLogPublicId): array
    {
        $baseUrl = rtrim((string)env('APP_URL', 'http://localhost:8765'), '/');

        return [
            'answer_url' => "{$baseUrl}/webhooks/voice/answer/{$callLogPublicId}",
            'dtmf_url' => "{$baseUrl}/webhooks/voice/dtmf/{$callLogPublicId}",
            'status_url' => "{$baseUrl}/webhooks/voice/status/{$callLogPublicId}",
        ];
    }
}
