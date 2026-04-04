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
        $recipients = $rule->getRecipients();

        if (empty($recipients)) {
            Log::warning("VoiceCallAlertChannel: Alert rule {$rule->id} has no recipients configured");

            return [
                'success' => false,
                'results' => [],
            ];
        }

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

        foreach ($recipients as $position => $phoneNumber) {
            $phoneNumber = trim($phoneNumber);
            if (empty($phoneNumber)) {
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
                ];
                continue;
            }

            try {
                // Create VoiceCallLog record
                $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');

                $callLog = $voiceCallLogsTable->newEntity([
                    'organization_id' => $orgId,
                    'incident_id' => $incident->id,
                    'monitor_id' => $monitor->id,
                    'notification_channel_id' => null,
                    'phone_number' => $phoneNumber,
                    'status' => VoiceCallLog::STATUS_INITIATED,
                    'tts_language' => $locale,
                    'tts_message' => $ttsMessage,
                    'cost_credits' => 0,
                    'sip_provider' => 'keepup',
                    'escalation_position' => $position,
                ]);

                $saved = $voiceCallLogsTable->save($callLog);

                if ($saved === false) {
                    $allSuccess = false;
                    $results[] = [
                        'recipient' => $phoneNumber,
                        'status' => 'failed',
                        'error' => 'Failed to create voice call log record',
                    ];
                    continue;
                }

                // Push VoiceCallJob to the notifications queue
                $this->pushVoiceCallJob($callLog);

                $results[] = [
                    'recipient' => $phoneNumber,
                    'status' => 'queued',
                    'error' => null,
                ];

                Log::info("VoiceCallAlertChannel: Queued voice call to {$phoneNumber} for monitor {$monitor->name}");
            } catch (\Exception $e) {
                $allSuccess = false;
                $results[] = [
                    'recipient' => $phoneNumber,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
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
