<?php
declare(strict_types=1);

namespace App\Service\Voice;

use App\Model\Entity\SipConfiguration;
use Cake\Http\Client;
use Cake\Log\Log;

/**
 * Custom SIP Provider
 *
 * Implements SipProviderInterface using either a Twilio SIP Trunk
 * or a direct SIP URI call through Twilio's infrastructure.
 */
class CustomSipProvider implements SipProviderInterface
{
    /**
     * SIP configuration entity
     *
     * @var \App\Model\Entity\SipConfiguration
     */
    private SipConfiguration $config;

    /**
     * Twilio Account SID (still needed to route via Twilio)
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
     * HTTP client instance
     *
     * @var \Cake\Http\Client
     */
    private Client $httpClient;

    /**
     * Constructor
     *
     * @param \App\Model\Entity\SipConfiguration $config The SIP configuration
     * @param \Cake\Http\Client|null $httpClient HTTP client instance (injectable for testing)
     */
    public function __construct(SipConfiguration $config, ?Client $httpClient = null)
    {
        $this->config = $config;
        $this->twilioSid = (string)env('TWILIO_SID', '');
        $this->twilioAuthToken = (string)env('TWILIO_AUTH_TOKEN', '');
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * @inheritDoc
     */
    public function initiateCall(string $toNumber, string $answerUrl, string $statusUrl, string $callerId): array
    {
        if (empty($this->twilioSid) || empty($this->twilioAuthToken)) {
            return [
                'success' => false,
                'call_sid' => null,
                'error' => 'Twilio credentials required for SIP trunk routing',
            ];
        }

        $fromNumber = !empty($callerId) ? $callerId : ($this->config->caller_id ?? '');

        try {
            $params = [
                'To' => $toNumber,
                'From' => $fromNumber,
                'Url' => $answerUrl,
                'StatusCallback' => $statusUrl,
                'StatusCallbackEvent' => 'initiated ringing answered completed',
                'StatusCallbackMethod' => 'POST',
                'Timeout' => 30,
            ];

            // If using a Twilio SIP Trunk, route through the trunk
            if ($this->config->isTwilioTrunk()) {
                // Build SIP URI for trunk routing
                $sipUri = "sip:{$toNumber}@{$this->config->sip_host}";
                $params['To'] = $sipUri;
                $params['SipAuthUsername'] = $this->config->sip_username ?? '';
                $params['SipAuthPassword'] = $this->config->sip_password ?? '';
            }

            $response = $this->httpClient->post(
                "https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Calls.json",
                $params,
                [
                    'auth' => ['username' => $this->twilioSid, 'password' => $this->twilioAuthToken],
                ]
            );

            $body = json_decode($response->getStringBody(), true);

            if ($response->getStatusCode() >= 400) {
                $error = $body['message'] ?? $response->getStringBody();
                Log::error("CustomSipProvider: Call to {$toNumber} failed: {$error}");

                return [
                    'success' => false,
                    'call_sid' => null,
                    'error' => $error,
                ];
            }

            $callSid = $body['sid'] ?? null;
            Log::info("CustomSipProvider: Call initiated to {$toNumber} via SIP trunk, SID: {$callSid}");

            return [
                'success' => true,
                'call_sid' => $callSid,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error("CustomSipProvider: Exception calling {$toNumber}: {$e->getMessage()}");

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
        if (empty($this->twilioSid) || empty($this->twilioAuthToken)) {
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
                Log::error("CustomSipProvider: Failed to cancel call {$callSid}: {$response->getStringBody()}");

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error("CustomSipProvider: Exception canceling call {$callSid}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function testConnection(): array
    {
        $host = $this->config->sip_host;
        $port = $this->config->sip_port ?? 5060;

        if (empty($host)) {
            return [
                'success' => false,
                'message' => 'SIP host not configured',
            ];
        }

        // Test TCP connectivity to the SIP host
        try {
            $transport = $this->config->sip_transport ?? 'udp';

            if ($transport === 'tcp' || $transport === 'tls') {
                $protocol = $transport === 'tls' ? 'tls' : 'tcp';
                $connection = @fsockopen($protocol . '://' . $host, $port, $errno, $errstr, 5);

                if ($connection === false) {
                    return [
                        'success' => false,
                        'message' => "Cannot connect to {$host}:{$port} ({$transport}): {$errstr}",
                    ];
                }

                fclose($connection);
            } else {
                // UDP: try a simple DNS/socket test
                $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
                if ($socket === false) {
                    return [
                        'success' => false,
                        'message' => 'Unable to create UDP socket for SIP OPTIONS test',
                    ];
                }

                socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);

                // Send a minimal SIP OPTIONS request
                $optionsRequest = "OPTIONS sip:{$host} SIP/2.0\r\n"
                    . "Via: SIP/2.0/UDP keepup.local;branch=z9hG4bK-test\r\n"
                    . "From: <sip:test@keepup.local>;tag=test\r\n"
                    . "To: <sip:{$host}>\r\n"
                    . "Call-ID: test-" . uniqid() . "@keepup.local\r\n"
                    . "CSeq: 1 OPTIONS\r\n"
                    . "Max-Forwards: 70\r\n"
                    . "Content-Length: 0\r\n\r\n";

                $sent = @socket_sendto($socket, $optionsRequest, strlen($optionsRequest), 0, $host, $port);
                socket_close($socket);

                if ($sent === false) {
                    return [
                        'success' => false,
                        'message' => "Cannot send SIP OPTIONS to {$host}:{$port} (UDP)",
                    ];
                }
            }

            return [
                'success' => true,
                'message' => "SIP host {$host}:{$port} is reachable via {$transport}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Connection test failed: {$e->getMessage()}",
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function getProviderName(): string
    {
        return 'custom_sip';
    }
}
