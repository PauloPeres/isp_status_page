<?php
declare(strict_types=1);

namespace App\Service\Alert;

/**
 * ResolvedRecipient Value Object
 *
 * Represents a resolved notification recipient with an optional
 * user_id for auditable acknowledgement tracking.
 */
class ResolvedRecipient
{
    public function __construct(
        public readonly ?int $userId,
        public readonly string $address,
        public readonly string $username,
        public readonly string $channelType,
    ) {
    }
}
