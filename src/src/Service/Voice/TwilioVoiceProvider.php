<?php
declare(strict_types=1);

namespace App\Service\Voice;

use Cake\Http\Client;
use Cake\Log\Log;

/**
 * Twilio Voice Provider
 *
 * Implements SipProviderInterface using the Twilio REST API for voice calls.
 * Uses the same HTTP client pattern as SmsAlertChannel.
 */
class TwilioVoiceProvider implements SipProviderInterface
{
    /**
     * Twilio Account SID
     *
     * @var string
     */
    private string $twilioSid;

    /**
     * Twilio Auth Token
     *
     * @var string
     */
    private string $twilioAuthToken;

    /**
     * Twilio voice phone number (E.164 format)
     *
     * @var string
     */
    private string $twilioVoiceNumber;

    /**
     * HTTP client instance
     *
     * @var \Cake\Http\Client
     */
    private Client $httpClient;

    /**
     * Constructor
     *
     * @param \Cake\Http\Client|null $httpClient HTTP client instance (injectable for testing)
     */
    public function __construct(?Client $httpClient = null)
    {
        $this->twilioSid = (string)env('TWILIO_SID', '');
        $this->twilioAuthToken = (string)env('TWILIO_AUTH_TOKEN', '');
        $this->twilioVoiceNumber = (string)env('TWILIO_VOICE_NUMBER', '');
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * @inheritDoc
     */
    public function initiateCall(string $toNumber, string $answerUrl, string $statusUrl, string $callerId): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'call_sid' => null,
                'error' => 'Twilio voice credentials not configured',
            ];
        }

        $fromNumber = !empty($callerId) ? $callerId : $this->twilioVoiceNumber;

        try {
            $response = $this->httpClient->post(
                "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Calls.json",
                [
                    'To' => $toNumber,
                    'From' => $fromNumber,
                    'Url' => $answerUrl,
                    'StatusCallback' => $statusUrl,
                    'StatusCallbackEvent' => 'initiated ringing answered completed',
                    'StatusCallbackMethod' => 'POST',
                    'Timeout' => 30,
                    'MachineDetection' => 'Enable',
                ],
                [
                    'auth' => ['username' => $this->twilioSid, 'password' => $this->twilioAuthToken],
                ]
            );

            $body = json_decode($response->getStringBody(), true);

            if ($response->getStatusCode() >= 400) {
                $error = $body['message'] ?? $response->getStringBody();
                Log::error("TwilioVoiceProvider: Call to {$toNumber} failed: {$error}");

                return [
                    'success' => false,
                    'call_sid' => null,
                    'error' => $error,
                ];
            }

            $callSid = $body['sid'] ?? null;
            Log::info("TwilioVoiceProvider: Call initiated to {$toNumber}, SID: {$callSid}");

            return [
                'success' => true,
                'call_sid' => $callSid,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error("TwilioVoiceProvider: Exception calling {$toNumber}: {$e->getMessage()}");

            return [
                'success' => false,
                'call_sid' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function cancelCall(string $callSid): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = $this->httpClient->post(
                "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Calls/{$callSid}.json",
                [
                    'Status' => 'canceled',
                ],
                [
                    'auth' => ['username' => $this->twilioSid, 'password' => $this->twilioAuthToken],
                ]
            );

            if ($response->getStatusCode() >= 400) {
                Log::error("TwilioVoiceProvider: Failed to cancel call {$callSid}: {$response->getStringBody()}");

                return false;
            }

            Log::info("TwilioVoiceProvider: Call {$callSid} canceled");

            return true;
        } catch (\Exception $e) {
            Log::error("TwilioVoiceProvider: Exception canceling call {$callSid}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Twilio credentials not configured (TWILIO_SID, TWILIO_AUTH_TOKEN, TWILIO_VOICE_NUMBER)',
            ];
        }

        try {
            $response = $this->httpClient->get(
                "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}.json",
                [],
                [
                    'auth' => ['username' => $this->twilioSid, 'password' => $this->twilioAuthToken],
                ]
            );

            if ($response->getStatusCode() >= 400) {
                return [
                    'success' => false,
                    'message' => "Twilio authentication failed: HTTP {$response->getStatusCode()}",
                ];
            }

            $body = json_decode($response->getStringBody(), true);
            $accountName = $body['friendly_name'] ?? 'Unknown';

            return [
                'success' => true,
                'message' => "Connected to Twilio account: {$accountName}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Connection failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function getProviderName(): string
    {
        return 'twilio';
    }

    /**
     * Check if Twilio credentials are configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->twilioSid)
            && !empty($this->twilioAuthToken)
            && !empty($this->twilioVoiceNumber);
    }
}
