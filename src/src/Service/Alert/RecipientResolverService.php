<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\NotificationChannel;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * RecipientResolverService
 *
 * Resolves notification channel recipients to ResolvedRecipient value objects,
 * linking each recipient to a user_id when possible for auditable acknowledgement.
 *
 * Supports both NEW format (objects with user_id) and OLD format (plain strings).
 */
class RecipientResolverService
{
    use LocatorAwareTrait;

    /**
     * Channel types that use phone numbers as contact addresses.
     */
    private const PHONE_CHANNELS = [
        NotificationChannel::TYPE_SMS,
        NotificationChannel::TYPE_VOICE_CALL,
        NotificationChannel::TYPE_WHATSAPP,
    ];

    /**
     * Resolve recipients for a notification channel.
     *
     * @param \App\Model\Entity\NotificationChannel $channel The notification channel
     * @param int $orgId The organization ID
     * @return array<\App\Service\Alert\ResolvedRecipient>
     */
    public function resolveForChannel(NotificationChannel $channel, int $orgId): array
    {
        $config = $channel->getRawConfiguration();
        $channelType = $channel->getType();

        // Determine the recipients array from configuration
        $rawRecipients = $config['recipients'] ?? $config['phone_numbers'] ?? [];

        if (empty($rawRecipients)) {
            Log::debug("RecipientResolverService: No recipients found in channel {$channel->id} config");

            return [];
        }

        // Detect format: new format has objects with user_id, old format has plain strings
        $isNewFormat = $this->isNewFormat($rawRecipients);

        if ($isNewFormat) {
            return $this->resolveNewFormat($rawRecipients, $channelType, $orgId);
        }

        return $this->resolveOldFormat($rawRecipients, $channelType, $orgId);
    }

    /**
     * Resolve recipients for an AlertRule.
     *
     * AlertRules store recipients as a JSON array directly on the entity.
     * This method resolves those recipients to ResolvedRecipient objects,
     * performing reverse lookup to find user_ids where possible.
     *
     * @param \App\Model\Entity\AlertRule $rule The alert rule
     * @param int $orgId The organization ID
     * @return array<\App\Service\Alert\ResolvedRecipient>
     */
    public function resolveForRule(AlertRule $rule, int $orgId): array
    {
        $rawRecipients = $rule->getRecipients();
        $channelType = $rule->channel;

        if (empty($rawRecipients)) {
            Log::debug("RecipientResolverService: No recipients found in alert rule {$rule->id}");

            return [];
        }

        // Detect format: new format has objects with user_id, old format has plain strings
        $isNewFormat = $this->isNewFormat($rawRecipients);

        if ($isNewFormat) {
            return $this->resolveNewFormat($rawRecipients, $channelType, $orgId);
        }

        return $this->resolveOldFormat($rawRecipients, $channelType, $orgId);
    }

    /**
     * Detect whether the recipients array uses the new format (objects with user_id).
     *
     * @param array $recipients Raw recipients from configuration
     * @return bool
     */
    private function isNewFormat(array $recipients): bool
    {
        if (empty($recipients)) {
            return false;
        }

        $first = reset($recipients);

        return is_array($first) && isset($first['user_id']);
    }

    /**
     * Resolve recipients in the new format: {user_id: 42, type: "member"}.
     *
     * Loads the user from the Users table, verifies org membership via OrganizationUsers,
     * and extracts the appropriate contact field (email or phone_number).
     *
     * @param array $rawRecipients New-format recipient objects
     * @param string $channelType The channel type
     * @param int $orgId The organization ID
     * @return array<\App\Service\Alert\ResolvedRecipient>
     */
    private function resolveNewFormat(array $rawRecipients, string $channelType, int $orgId): array
    {
        $resolved = [];
        $usersTable = $this->fetchTable('Users');
        $orgUsersTable = $this->fetchTable('OrganizationUsers');

        foreach ($rawRecipients as $entry) {
            $userId = (int)($entry['user_id'] ?? 0);
            if ($userId <= 0) {
                Log::warning("RecipientResolverService: Invalid user_id in new format recipient");
                continue;
            }

            try {
                // Load user
                $user = $usersTable->find()
                    ->where(['Users.id' => $userId])
                    ->first();

                if ($user === null) {
                    Log::warning("RecipientResolverService: User {$userId} not found, skipping");
                    continue;
                }

                // Verify org membership
                $membership = $orgUsersTable->find()
                    ->where([
                        'OrganizationUsers.organization_id' => $orgId,
                        'OrganizationUsers.user_id' => $userId,
                    ])
                    ->first();

                if ($membership === null) {
                    Log::warning("RecipientResolverService: User {$userId} is not a member of org {$orgId}, skipping");
                    continue;
                }

                // Get the appropriate contact field
                $address = $this->getContactAddress($user, $channelType);
                if (empty($address)) {
                    Log::warning("RecipientResolverService: User {$userId} has no contact info for channel {$channelType}, skipping");
                    continue;
                }

                $resolved[] = new ResolvedRecipient(
                    userId: $userId,
                    address: $address,
                    username: $user->username ?? $user->email ?? "user-{$userId}",
                    channelType: $channelType,
                );
            } catch (\Exception $e) {
                Log::error("RecipientResolverService: Error resolving user {$userId}: {$e->getMessage()}");
            }
        }

        return $resolved;
    }

    /**
     * Resolve recipients in the old format: plain strings like "alice@co.com" or "+5511999999999".
     *
     * Attempts a reverse lookup to find the user in the org whose email/phone matches.
     * If found, returns with user_id populated. If not, returns with user_id=null (degraded mode).
     *
     * @param array $rawRecipients Old-format plain string recipients
     * @param string $channelType The channel type
     * @param int $orgId The organization ID
     * @return array<\App\Service\Alert\ResolvedRecipient>
     */
    private function resolveOldFormat(array $rawRecipients, string $channelType, int $orgId): array
    {
        $resolved = [];
        $usersTable = $this->fetchTable('Users');
        $orgUsersTable = $this->fetchTable('OrganizationUsers');

        // Pre-load org user IDs for efficient lookup
        $orgUserIds = $orgUsersTable->find()
            ->where(['OrganizationUsers.organization_id' => $orgId])
            ->select(['OrganizationUsers.user_id'])
            ->all()
            ->extract('user_id')
            ->toArray();

        foreach ($rawRecipients as $address) {
            if (!is_string($address)) {
                continue;
            }

            $address = trim($address);
            if (empty($address)) {
                continue;
            }

            $userId = null;
            $username = $address;

            // Attempt reverse lookup
            if (!empty($orgUserIds)) {
                $isPhoneChannel = in_array($channelType, self::PHONE_CHANNELS, true);
                $field = $isPhoneChannel ? 'Users.phone_number' : 'Users.email';

                $user = $usersTable->find()
                    ->where([
                        $field => $address,
                        'Users.id IN' => $orgUserIds,
                    ])
                    ->first();

                if ($user !== null) {
                    $userId = (int)$user->id;
                    $username = $user->username ?? $user->email ?? $address;
                }
            }

            $resolved[] = new ResolvedRecipient(
                userId: $userId,
                address: $address,
                username: $username,
                channelType: $channelType,
            );
        }

        return $resolved;
    }

    /**
     * Get the appropriate contact address for a user based on channel type.
     *
     * @param \App\Model\Entity\User $user The user entity
     * @param string $channelType The channel type
     * @return string|null The contact address, or null if not available
     */
    private function getContactAddress(object $user, string $channelType): ?string
    {
        if (in_array($channelType, self::PHONE_CHANNELS, true)) {
            $phone = $user->phone_number ?? null;

            return !empty($phone) ? (string)$phone : null;
        }

        // Default to email for email channels
        $email = $user->email ?? null;

        return !empty($email) ? (string)$email : null;
    }
}
