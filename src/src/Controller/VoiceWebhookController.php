<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Incident;
use App\Model\Entity\VoiceCallLog;
use App\Service\Voice\TtsMessageBuilder;
use App\Service\Voice\VoiceCallService;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\QueueManager;
use App\Job\VoiceEscalationJob;

/**
 * Voice Webhook Controller
 *
 * Handles Twilio webhook callbacks for voice call alerts.
 * This controller is UNAUTHENTICATED (Twilio posts to it),
 * but validates Twilio request signatures for security.
 */
class VoiceWebhookController extends AppController
{
    use LocatorAwareTrait;

    /**
     * TTS message builder
     *
     * @var \App\Service\Voice\TtsMessageBuilder
     */
    private TtsMessageBuilder $ttsBuilder;

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->ttsBuilder = new TtsMessageBuilder();
    }

    /**
     * Before filter callback — allow unauthenticated access for all actions.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // Allow unauthenticated access (Twilio webhooks)
        $this->Authentication->addUnauthenticatedActions([
            'answer',
            'dtmfInput',
            'statusCallback',
        ]);
    }

    /**
     * Answer action — called when the call is answered by the recipient.
     *
     * Looks up the VoiceCallLog, validates Twilio signature, updates status,
     * and returns TwiML XML with the TTS message and IVR prompt.
     *
     * @param string $callLogPublicId The call log's public UUID
     * @return \Cake\Http\Response
     */
    public function answer(string $callLogPublicId): \Cake\Http\Response
    {
        $callLog = $this->findCallLogByPublicId($callLogPublicId);
        if ($callLog === null) {
            return $this->twimlResponse('<Response><Say>Invalid call reference.</Say><Hangup/></Response>');
        }

        if (!$this->validateTwilioSignature()) {
            Log::warning("VoiceWebhook: Invalid Twilio signature for answer/{$callLogPublicId}");

            return $this->response->withStatus(403)->withStringBody('Forbidden');
        }

        // Update status to answered
        $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');
        $callLog->status = VoiceCallLog::STATUS_ANSWERED;
        $voiceCallLogsTable->save($callLog);

        // Build TwiML response
        $locale = $callLog->tts_language ?: 'en';
        $voice = $this->ttsBuilder->getTwilioVoice($locale);
        $langCode = $this->ttsBuilder->getTwilioLanguage($locale);
        $ttsMessage = $callLog->tts_message ?: 'Alert: A monitor is down.';
        $ivrPrompt = $this->ttsBuilder->getIvrPrompt($locale);
        $noInputMessage = $this->ttsBuilder->getNoInputMessage($locale);

        $baseUrl = rtrim((string)env('APP_URL', 'http://localhost:8765'), '/');
        $dtmfUrl = "{$baseUrl}/webhooks/voice/dtmf/{$callLogPublicId}";

        $twiml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $twiml .= '<Response>';
        $twiml .= '<Say voice="' . $this->xmlEscape($voice) . '" language="' . $this->xmlEscape($langCode) . '">' . $this->xmlEscape($ttsMessage) . '</Say>';
        $twiml .= '<Gather numDigits="1" action="' . $this->xmlEscape($dtmfUrl) . '" method="POST" timeout="10">';
        $twiml .= '<Say voice="' . $this->xmlEscape($voice) . '" language="' . $this->xmlEscape($langCode) . '">' . $this->xmlEscape($ivrPrompt) . '</Say>';
        $twiml .= '</Gather>';
        $twiml .= '<Say voice="' . $this->xmlEscape($voice) . '" language="' . $this->xmlEscape($langCode) . '">' . $this->xmlEscape($noInputMessage) . '</Say>';
        $twiml .= '</Response>';

        return $this->twimlResponse($twiml);
    }

    /**
     * DTMF Input action — called when user presses a digit.
     *
     * Handles digit 1 (acknowledge), digit 2 (escalate), or replays prompt.
     *
     * @param string $callLogPublicId The call log's public UUID
     * @return \Cake\Http\Response
     */
    public function dtmfInput(string $callLogPublicId): \Cake\Http\Response
    {
        $callLog = $this->findCallLogByPublicId($callLogPublicId);
        if ($callLog === null) {
            return $this->twimlResponse('<Response><Say>Invalid call reference.</Say><Hangup/></Response>');
        }

        if (!$this->validateTwilioSignature()) {
            Log::warning("VoiceWebhook: Invalid Twilio signature for dtmf/{$callLogPublicId}");

            return $this->response->withStatus(403)->withStringBody('Forbidden');
        }

        $digit = $this->request->getData('Digits');
        $locale = $callLog->tts_language ?: 'en';
        $voice = $this->ttsBuilder->getTwilioVoice($locale);
        $langCode = $this->ttsBuilder->getTwilioLanguage($locale);
        $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');
        $voiceCallService = new VoiceCallService();

        if ($digit === VoiceCallLog::DTMF_ACKNOWLEDGE) {
            // Acknowledge the incident
            $incidentsTable = $this->fetchTable('Incidents');
            try {
                $incident = $incidentsTable->get($callLog->incident_id);
                $incident->acknowledgeBy(null, 'voice_call');
                $incidentsTable->save($incident);
            } catch (\Exception $e) {
                Log::error("VoiceWebhook: Failed to acknowledge incident {$callLog->incident_id}: {$e->getMessage()}");
            }

            // Update call log
            $callLog->dtmf_input = VoiceCallLog::DTMF_ACKNOWLEDGE;
            $callLog->status = VoiceCallLog::STATUS_COMPLETED;
            $voiceCallLogsTable->save($callLog);

            // Charge credits
            $voiceCallService->handleCallCompleted($callLog);

            $ackMessage = $this->ttsBuilder->getAckConfirmation($locale);
            $twiml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $twiml .= '<Response>';
            $twiml .= '<Say voice="' . $this->xmlEscape($voice) . '" language="' . $this->xmlEscape($langCode) . '">' . $this->xmlEscape($ackMessage) . '</Say>';
            $twiml .= '<Hangup/>';
            $twiml .= '</Response>';

            return $this->twimlResponse($twiml);
        }

        if ($digit === VoiceCallLog::DTMF_ESCALATE) {
            // Update call log
            $callLog->dtmf_input = VoiceCallLog::DTMF_ESCALATE;
            $callLog->status = VoiceCallLog::STATUS_COMPLETED;
            $voiceCallLogsTable->save($callLog);

            // Charge credits
            $voiceCallService->handleCallCompleted($callLog);

            // Push escalation job
            $this->pushEscalationJob($callLog->id);

            $escalateMessage = $this->ttsBuilder->getEscalateConfirmation($locale);
            $twiml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $twiml .= '<Response>';
            $twiml .= '<Say voice="' . $this->xmlEscape($voice) . '" language="' . $this->xmlEscape($langCode) . '">' . $this->xmlEscape($escalateMessage) . '</Say>';
            $twiml .= '<Hangup/>';
            $twiml .= '</Response>';

            return $this->twimlResponse($twiml);
        }

        // Any other digit — replay the gather prompt
        $ivrPrompt = $this->ttsBuilder->getIvrPrompt($locale);
        $baseUrl = rtrim((string)env('APP_URL', 'http://localhost:8765'), '/');
        $dtmfUrl = "{$baseUrl}/webhooks/voice/dtmf/{$callLogPublicId}";

        $twiml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $twiml .= '<Response>';
        $twiml .= '<Gather numDigits="1" action="' . $this->xmlEscape($dtmfUrl) . '" method="POST" timeout="10">';
        $twiml .= '<Say voice="' . $this->xmlEscape($voice) . '" language="' . $this->xmlEscape($langCode) . '">' . $this->xmlEscape($ivrPrompt) . '</Say>';
        $twiml .= '</Gather>';
        $twiml .= '</Response>';

        return $this->twimlResponse($twiml);
    }

    /**
     * Status Callback action — called when the call ends (final status).
     *
     * Updates the call log with final status and duration.
     * Triggers escalation if the call was not answered/handled.
     *
     * @param string $callLogPublicId The call log's public UUID
     * @return \Cake\Http\Response
     */
    public function statusCallback(string $callLogPublicId): \Cake\Http\Response
    {
        $callLog = $this->findCallLogByPublicId($callLogPublicId);
        if ($callLog === null) {
            return $this->twimlResponse('<?xml version="1.0" encoding="UTF-8"?><Response/>');
        }

        if (!$this->validateTwilioSignature()) {
            Log::warning("VoiceWebhook: Invalid Twilio signature for status/{$callLogPublicId}");

            return $this->response->withStatus(403)->withStringBody('Forbidden');
        }

        $callStatus = $this->request->getData('CallStatus');
        $callDuration = (int)$this->request->getData('CallDuration');

        $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');

        // Map Twilio status to our status constants
        $statusMap = [
            'completed' => VoiceCallLog::STATUS_COMPLETED,
            'no-answer' => VoiceCallLog::STATUS_NO_ANSWER,
            'busy' => VoiceCallLog::STATUS_BUSY,
            'failed' => VoiceCallLog::STATUS_FAILED,
            'canceled' => VoiceCallLog::STATUS_CANCELED,
        ];

        $mappedStatus = $statusMap[$callStatus] ?? $callStatus;

        // Only update if not already handled by DTMF (completed with dtmf_input)
        if (!$callLog->isTerminal() || empty($callLog->dtmf_input)) {
            $callLog->status = $mappedStatus;
        }
        $callLog->duration_seconds = $callDuration;
        $voiceCallLogsTable->save($callLog);

        // If no-answer/busy/failed and not already handled by DTMF, escalate
        $needsEscalation = in_array($mappedStatus, [
            VoiceCallLog::STATUS_NO_ANSWER,
            VoiceCallLog::STATUS_BUSY,
            VoiceCallLog::STATUS_FAILED,
        ], true);

        if ($needsEscalation && empty($callLog->dtmf_input)) {
            $this->pushEscalationJob($callLog->id);
        }

        return $this->twimlResponse('<?xml version="1.0" encoding="UTF-8"?><Response/>');
    }

    /**
     * Validate Twilio request signature.
     *
     * Computes HMAC-SHA1 of the full webhook URL + sorted POST params
     * and compares with the X-Twilio-Signature header.
     *
     * @return bool True if valid or in debug mode
     */
    private function validateTwilioSignature(): bool
    {
        // Skip validation in debug mode
        if (env('APP_DEBUG', false)) {
            return true;
        }

        $authToken = (string)env('TWILIO_AUTH_TOKEN', '');
        if (empty($authToken)) {
            Log::error('VoiceWebhook: TWILIO_AUTH_TOKEN not configured');

            return false;
        }

        $signature = $this->request->getHeaderLine('X-Twilio-Signature');
        if (empty($signature)) {
            Log::warning('VoiceWebhook: Missing X-Twilio-Signature header');

            return false;
        }

        // Build the full URL
        $url = $this->request->scheme() . '://' . $this->request->host() . $this->request->getRequestTarget();

        // For POST requests, sort params and concatenate
        $postData = $this->request->getData();
        if (is_array($postData) && !empty($postData)) {
            ksort($postData);
            foreach ($postData as $key => $value) {
                $url .= $key . $value;
            }
        }

        // Compute expected signature
        $expectedSignature = base64_encode(hash_hmac('sha1', $url, $authToken, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Find a VoiceCallLog by its public UUID.
     *
     * @param string $publicId The public UUID
     * @return \App\Model\Entity\VoiceCallLog|null
     */
    private function findCallLogByPublicId(string $publicId): ?VoiceCallLog
    {
        $voiceCallLogsTable = $this->fetchTable('VoiceCallLogs');

        try {
            /** @var \App\Model\Entity\VoiceCallLog|null $callLog */
            $callLog = $voiceCallLogsTable->find()
                ->where(['VoiceCallLogs.public_id' => $publicId])
                ->first();

            return $callLog;
        } catch (\Exception $e) {
            Log::error("VoiceWebhook: Error finding call log by public_id {$publicId}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Return a TwiML XML response.
     *
     * @param string $twiml The TwiML XML string
     * @return \Cake\Http\Response
     */
    private function twimlResponse(string $twiml): \Cake\Http\Response
    {
        $this->autoRender = false;

        return $this->response
            ->withType('application/xml')
            ->withStringBody($twiml);
    }

    /**
     * Escape a string for use in XML attributes/text.
     *
     * @param string $value The value to escape
     * @return string
     */
    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Push a VoiceEscalationJob to the queue.
     *
     * @param int $callLogId The call log ID
     * @return void
     */
    private function pushEscalationJob(int $callLogId): void
    {
        try {
            QueueManager::push(VoiceEscalationJob::class, [
                'data' => [
                    'voice_call_log_id' => $callLogId,
                ],
            ], ['config' => 'notifications']);

            Log::info("VoiceWebhook: Pushed VoiceEscalationJob for call log {$callLogId}");
        } catch (\Exception $e) {
            Log::error("VoiceWebhook: Failed to push VoiceEscalationJob for call log {$callLogId}: {$e->getMessage()}");
        }
    }
}
