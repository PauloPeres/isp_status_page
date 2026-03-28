<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\Incident;

/**
 * Acknowledge URL Trait
 *
 * Provides a shared method for generating incident acknowledgement URLs.
 * Used by SMS, WhatsApp, and Telegram alert channels to include
 * clickable acknowledge links in alert messages.
 */
trait AcknowledgeUrlTrait
{
    /**
     * Get the acknowledge URL for an incident
     *
     * Returns null if the incident is resolved or has no acknowledgement token.
     *
     * @param \App\Model\Entity\Incident $incident The incident
     * @return string|null The acknowledge URL, or null if not applicable
     */
    protected function getAcknowledgeUrl(Incident $incident): ?string
    {
        if ($incident->status === Incident::STATUS_RESOLVED) {
            return null;
        }

        $token = $incident->acknowledgement_token;
        if (empty($token)) {
            return null;
        }

        $baseUrl = rtrim((string)env('APP_URL', 'http://localhost:8765'), '/');

        return "{$baseUrl}/incidents/acknowledge/{$incident->id}/{$token}";
    }
}
