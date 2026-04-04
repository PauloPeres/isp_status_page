<?php
declare(strict_types=1);

namespace App\Service\Voice;

/**
 * SIP Provider Interface
 *
 * Defines the contract for voice call providers. Implementations handle
 * the actual call initiation and management via different SIP/telephony backends.
 */
interface SipProviderInterface
{
    /**
     * Initiate a voice call.
     *
     * @param string $toNumber Destination phone number in E.164 format
     * @param string $answerUrl Webhook URL for call answer/TwiML instructions
     * @param string $statusUrl Webhook URL for call status callbacks
     * @param string $callerId Caller ID to display (E.164 format)
     * @return array{success: bool, call_sid: string|null, error: string|null}
     */
    public function initiateCall(string $toNumber, string $answerUrl, string $statusUrl, string $callerId): array;

    /**
     * Cancel an active call.
     *
     * @param string $callSid The provider's call reference ID
     * @return bool True if cancellation succeeded
     */
    public function cancelCall(string $callSid): bool;

    /**
     * Test the provider connection/credentials.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(): array;

    /**
     * Get the provider name identifier.
     *
     * @return string
     */
    public function getProviderName(): string;
}
