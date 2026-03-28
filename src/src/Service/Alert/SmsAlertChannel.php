<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use Cake\Http\Client;
use Cake\Log\Log;

/**
 * SMS Alert Channel
 *
 * Sends alert notifications via SMS using the Twilio Messages API.
 * Requires TWILIO_SID, TWILIO_AUTH_TOKEN, and TWILIO_FROM_NUMBER
 * environment variables to be configured.
 */
class SmsAlertChannel implements ChannelInterface
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
     * Twilio sender phone number (E.164 format)
     *
     * @var string
     */
    private string $twilioFromNumber;

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
        $this->twilioFromNumber = (string)env('TWILIO_FROM_NUMBER', '');
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * Send SMS alert to all recipients in the rule
     *
     * @param \App\Model\Entity\AlertRule $rule The alert rule
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return array Result with success flag and per-recipient results
     */
    public function send(AlertRule $rule, Monitor $monitor, Incident $incident): array
    {
        if (!$this->isConfigured()) {
            Log::warning('SMS channel not configured (TWILIO_SID missing)');

            return [
                'success' => false,
                'results' => [],
            ];
        }

        $recipients = $rule->getRecipients();

        if (empty($recipients)) {
            Log::warning("Alert rule {$rule->id} has no recipients configured");

            return [
                'success' => false,
                'results' => [],
            ];
        }

        $message = $this->formatMessage($monitor, $incident);
        $results = [];
        $allSuccess = true;

        foreach ($recipients as $phoneNumber) {
            $phoneNumber = trim($phoneNumber);
            if (empty($phoneNumber)) {
                continue;
            }

            try {
                $response = $this->httpClient->post(
                    "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Messages.json",
                    [
                        'To' => $phoneNumber,
                        'From' => $this->twilioFromNumber,
                        'Body' => $message,
                    ],
                    [
                        'auth' => ['username' => $this->twilioSid, 'password' => $this->twilioAuthToken],
                    ]
                );

                if ($response->getStatusCode() >= 400) {
                    $allSuccess = false;

                    $results[] = [
                        'recipient' => $phoneNumber,
                        'status' => 'failed',
                        'error' => "HTTP {$response->getStatusCode()}: {$response->getStringBody()}",
                    ];

                    Log::error("SMS send failed to {$phoneNumber}: {$response->getStringBody()}");
                } else {
                    $results[] = [
                        'recipient' => $phoneNumber,
                        'status' => 'sent',
                        'error' => null,
                    ];

                    Log::info("SMS alert sent to {$phoneNumber} for monitor {$monitor->name}");
                }
            } catch (\Exception $e) {
                $allSuccess = false;

                $results[] = [
                    'recipient' => $phoneNumber,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                Log::error("SMS send error to {$phoneNumber}: {$e->getMessage()}");
            }
        }

        return [
            'success' => $allSuccess,
            'results' => $results,
        ];
    }

    /**
     * Get the channel type identifier
     *
     * @return string
     */
    public function getType(): string
    {
        return 'sms';
    }

    /**
     * Get human-readable name for this channel
     *
     * @return string
     */
    public function getName(): string
    {
        return 'SMS Alert Channel';
    }

    /**
     * Check if Twilio credentials are configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->twilioSid)
            && !empty($this->twilioAuthToken)
            && !empty($this->twilioFromNumber);
    }

    /**
     * Format the SMS message body
     *
     * SMS messages are kept short to stay within the 160 character
     * limit of a single SMS segment where possible.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return string Formatted message
     */
    public function formatMessage(Monitor $monitor, Incident $incident): string
    {
        $isDown = $incident->isOngoing();
        $name = $monitor->name;

        if ($isDown) {
            $time = $incident->started_at ? $incident->started_at->format('H:i') : 'now';

            return "[ALERT] {$name} is DOWN. Started: {$time}";
        }

        return "[RESOLVED] {$name} is back UP.";
    }
}
