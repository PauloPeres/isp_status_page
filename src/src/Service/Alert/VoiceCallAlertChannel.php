<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Job\VoiceCallJob;
use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Model\Entity\VoiceCallLog;
use App\Service\Billing\NotificationCreditService;
use App\Service\Voice\TtsMessageBuilder;
use App\Service\Voice\VoiceCallService;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\QueueManager;

/**
 * Voice Call Alert Channel
 *
 * Sends alert notifications via automated voice calls using Twilio or
 * a custom SIP trunk. Supports IVR-based acknowledgement and escalation.
 *
 * Each voice call costs 3 notification credits.
 */
class VoiceCallAlertChannel implements ChannelInterface
{
    use LocatorAwareTrait;

    /**
     * Credits required per voice call.
     *
     * @var int
     */
    private const CREDITS_PER_CALL = 3;

    /**
     * Notification credit service
     *
     * @var \App\Service\Billing\NotificationCreditService
     */
    private NotificationCreditService $creditService;

    /**
     * TTS message builder
     *
     * @var \App\Service\Voice\TtsMessageBuilder
     */
    private TtsMessageBuilder $ttsBuilder;

    /**
     * Constructor
     *
     * @param \App\Service\Billing\NotificationCreditService|null $creditService Credit service
     * @param \App\Service\Voice\TtsMessageBuilder|null $ttsBuilder TTS builder
     */
    public function __construct(
        ?NotificationCreditService $creditService = null,
        ?TtsMessageBuilder $ttsBuilder = null
    ) {
        $this->creditService = $creditService ?? new NotificationCreditService();
        $this->ttsBuilder = $ttsBuilder ?? new TtsMessageBuilder();
    }

    /**
     * Send voice call alerts to all recipients in the rule.
     *
     * @param \App\Model\Entity\AlertRule $rule The alert rule
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return array Result with success flag and per-recipient results
     */
    public function send(AlertRule $rule, Monitor $monitor, Incident $incident): array
    {
        // Use resolved recipients if available, fall back to legacy getRecipients()
        /** @var \App\Service\Alert\ResolvedRecipient[]|null $resolvedRecipients */
        $resolvedRecipients = $rule->_resolvedRecipients ?? null;

        $orgId = $monitor->organization_id;
        $results = [];
        $allSuccess = true;

        // Resolve language from organization settings (default to 'en')
        $locale = $this->resolveLocale($orgId);

        // Build TTS message
        $isDown = $incident->isOngoing();
        $ttsMessage = $isDown
            ? $this->ttsBuilder->buildDownMessage($monitor, $incident, $locale)
            : $this->ttsBuilder->buildResolvedMessage($monitor, $locale);

        // Build the list of phone numbers to call with optional user_id
        $callList = [];
        if ($resolvedRecipients !== null && !empty($resolvedRecipients)) {
            foreach ($resolvedRecipients as $position => $resolved) {
                $callList[] = [
                    'phone_number' => trim($resolved->address),
                    'user_id' => $resolved->userId,
                    'position' => $position,
                ];
            }
        } else {
            $recipients = $rule->getRecipients();
            if (empty($recipients)) {
                Log::warning("VoiceCallAlertChannel: Alert rule {$rule->id} has no recipients configured");

                return [
                    'success' => false,
                    'results' => [],
                ];
            }
            foreach ($recipients as $position => $phoneNumber) {
                $callList[] = [
                    'phone_number' => trim($phoneNumber),
                    'user_id' => null,
                    'position' => $position,
                ];
            }
        }

        foreach ($callList as $entry) {
            $phoneNumber = $entry['phone_number'];
            $userId = $entry['user_id'];
            $position = $entry['position'];

            if (empty($phoneNumber)) {
                continue;
            }

            // Validate E.164 phone number format to prevent toll fraud / SSRF
            if (!preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber)) {
                Log::warning("VoiceCallAlertChannel: Invalid phone number format for org {$orgId}: " . substr($phoneNumber, 0, 6) . '...');
                $results[] = [
                    'recipient' => $phoneNumber,
                    'status' => 'failed',
                    'error' => 'Invalid phone number format (must be E.164)',
                    'user_id' => $userId,
                ];
                $allSuccess = false;
                continue;
            }

            // Check credits before initiating call
            if (!$this->creditService->hasCredits($orgId, self::CREDITS_PER_CALL)) {
                Log::warning("VoiceCallAlertChannel: Insufficient credits for org {$orgId} to call {$phoneNumber}");

                $allSuccess = false;
                $results[] = [
                    'recipient' => $phoneNumber,
                    'status' => 'failed',
                    'error' => 'Insufficient notification credits (requires ' . self::CREDITS_PER_CALL . ')',
                    'user_id' => $userId,
                ];
                continue;
            }

            try {
                // Create VoiceCallLog record
                $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');

                $callLog = $voiceCallLogsTable->newEntity([
                    'incident_id' => $incident->id,
                    'monitor_id' => $monitor->id,
                    'user_id' => $userId,
                    'notification_channel_id' => null,
                    'phone_number' => $phoneNumber,
                    'status' => VoiceCallLog::STATUS_INITIATED,
                    'tts_language' => $locale,
                    'tts_message' => $ttsMessage,
                    'cost_credits' => 0,
                    'sip_provider' => 'keepup',
                    'escalation_position' => $position,
                ]);
                $callLog->set('organization_id', $orgId);

                $saved = $voiceCallLogsTable->save($callLog);

                if ($saved === false) {
                    $allSuccess = false;
                    $results[] = [
                        'recipient' => $phoneNumber,
                        'status' => 'failed',
                        'error' => 'Failed to create voice call log record',
                        'user_id' => $userId,
                    ];
                    continue;
                }

                // Push VoiceCallJob to the notifications queue
                $this->pushVoiceCallJob($callLog);

                $results[] = [
                    'recipient' => $phoneNumber,
                    'status' => 'queued',
                    'error' => null,
                    'user_id' => $userId,
                ];

                Log::info("VoiceCallAlertChannel: Queued voice call to {$phoneNumber} (user_id: {$userId}) for monitor {$monitor->name}");
            } catch (\Exception $e) {
                $allSuccess = false;
                $results[] = [
                    'recipient' => $phoneNumber,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                ];

                Log::error("VoiceCallAlertChannel: Error queueing call to {$phoneNumber}: {$e->getMessage()}");
            }
        }

        return [
            'success' => $allSuccess,
            'results' => $results,
        ];
    }

    /**
     * Get the channel type identifier.
     *
     * @return string
     */
    public function getType(): string
    {
        return 'voice_call';
    }

    /**
     * Get human-readable name for this channel.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Voice Call';
    }

    /**
     * Push a VoiceCallJob to the notifications queue.
     *
     * Falls back to synchronous execution if the queue is not available.
     *
     * @param \App\Model\Entity\VoiceCallLog $callLog The call log entity
     * @return void
     */
    private function pushVoiceCallJob(VoiceCallLog $callLog): void
    {
        $useQueue = !empty(Configure::read('Queue.notifications'));

        if ($useQueue) {
            try {
                QueueManager::push(VoiceCallJob::class, [
                    'data' => [
                        'voice_call_log_id' => $callLog->id,
                    ],
                ], ['config' => 'notifications']);

                return;
            } catch (\Exception $e) {
                Log::warning("VoiceCallAlertChannel: Failed to push VoiceCallJob to queue, falling back to sync: {$e->getMessage()}");
            }
        }

        // Fallback: synchronous execution
        $voiceCallService = new VoiceCallService();
        $voiceCallService->initiateCall($callLog);
    }

    /**
     * Resolve the locale for an organization.
     *
     * @param int $orgId Organization ID
     * @return string The locale (en, pt_BR, es)
     */
    private function resolveLocale(int $orgId): string
    {
        try {
            $org = $this->fetchTable('Organizations')->get($orgId);

            return $org->language ?? 'en';
        } catch (\Exception $e) {
            return 'en';
        }
    }
}
