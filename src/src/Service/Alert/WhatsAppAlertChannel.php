<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use Cake\Http\Client;
use Cake\I18n\DateTime;
use Cake\Log\Log;

/**
 * WhatsApp Alert Channel
 *
 * Sends alert notifications via WhatsApp using the Twilio Messages API.
 * Uses the same Twilio account credentials as SMS but with the
 * 'whatsapp:' prefix on phone numbers.
 *
 * Requires TWILIO_SID, TWILIO_AUTH_TOKEN, and TWILIO_WHATSAPP_NUMBER
 * environment variables to be configured.
 */
class WhatsAppAlertChannel implements ChannelInterface
{
    use AcknowledgeUrlTrait;
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
     * Twilio WhatsApp-enabled sender number (E.164 format, without whatsapp: prefix)
     *
     * @var string
     */
    private string $twilioWhatsAppNumber;

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
        $this->twilioWhatsAppNumber = (string)env('TWILIO_WHATSAPP_NUMBER', '');
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * Send WhatsApp alert to all recipients in the rule
     *
     * @param \App\Model\Entity\AlertRule $rule The alert rule
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return array Result with success flag and per-recipient results
     */
    public function send(AlertRule $rule, Monitor $monitor, Incident $incident): array
    {
        if (!$this->isConfigured()) {
            Log::warning('WhatsApp channel not configured (TWILIO_SID or TWILIO_WHATSAPP_NUMBER missing)');

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
                        'To' => 'whatsapp:' . $phoneNumber,
                        'From' => 'whatsapp:' . $this->twilioWhatsAppNumber,
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

                    Log::error("WhatsApp send failed to {$phoneNumber}: {$response->getStringBody()}");
                } else {
                    $results[] = [
                        'recipient' => $phoneNumber,
                        'status' => 'sent',
                        'error' => null,
                    ];

                    Log::info("WhatsApp alert sent to {$phoneNumber} for monitor {$monitor->name}");
                }
            } catch (\Exception $e) {
                $allSuccess = false;

                $results[] = [
                    'recipient' => $phoneNumber,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                Log::error("WhatsApp send error to {$phoneNumber}: {$e->getMessage()}");
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
        return 'whatsapp';
    }

    /**
     * Get human-readable name for this channel
     *
     * @return string
     */
    public function getName(): string
    {
        return 'WhatsApp Alert Channel';
    }

    /**
     * Check if Twilio WhatsApp credentials are configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->twilioSid)
            && !empty($this->twilioAuthToken)
            && !empty($this->twilioWhatsAppNumber);
    }

    /**
     * Format the WhatsApp message body
     *
     * WhatsApp messages can be longer and more formatted than SMS,
     * so we include additional details and use line breaks for readability.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return string Formatted message
     */
    public function formatMessage(Monitor $monitor, Incident $incident): string
    {
        $isDown = $incident->isOngoing();
        $name = $monitor->name;
        $time = $incident->started_at ? $incident->started_at->format('H:i') : DateTime::now()->format('H:i');

        if ($isDown) {
            $ackUrl = $this->getAcknowledgeUrl($incident);
            $message = "\xF0\x9F\x94\xB4 DOWN\n\nMonitor: {$name}\nTime: {$time}";
            if ($ackUrl) {
                $message .= "\n\n\xF0\x9F\x91\x89 Acknowledge: {$ackUrl}";
            } else {
                $message .= "\n\nCheck your status page for details.";
            }

            return $message;
        }

        return "\xE2\x9C\x85 RESOLVED\n\nMonitor: {$name}\nTime: {$time}\n\nCheck your status page for details.";
    }
}
