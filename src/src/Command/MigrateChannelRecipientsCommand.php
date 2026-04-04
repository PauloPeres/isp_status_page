<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * MigrateChannelRecipientsCommand
 *
 * Migrates notification channel recipients from old format (plain strings)
 * to new format (user_id references).
 *
 * CLI: bin/cake migrate_channel_recipients
 *
 * For each person-to-person channel (email, sms, whatsapp, voice_call):
 * - Tries to match each email/phone to an organization member
 * - If matched: converts to {user_id, type: "member"} format
 * - If not matched: logs warning with channel name + unmatched address
 */
class MigrateChannelRecipientsCommand extends Command
{
    use LocatorAwareTrait;

    /**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser
            ->setDescription('Migrate notification channel recipients from plain strings to user_id references')
            ->addOption('dry-run', [
                'short' => 'd',
                'help' => 'Show what would be changed without actually saving',
                'boolean' => true,
                'default' => false,
            ]);

        return $parser;
    }

    /**
     * Execute the migration.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $dryRun = (bool)$args->getOption('dry-run');

        if ($dryRun) {
            $io->out('<warning>DRY RUN MODE — no changes will be saved</warning>');
            $io->hr();
        }

        $io->out('<info>Migrating notification channel recipients...</info>');
        $io->hr();

        $channelsTable = $this->fetchTable('NotificationChannels');
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $usersTable = $this->fetchTable('Users');

        $personToPersonTypes = ['email', 'sms', 'whatsapp', 'voice_call'];

        // Load all channels of person-to-person types
        $channels = $channelsTable->find()
            ->where(['NotificationChannels.type IN' => $personToPersonTypes])
            ->all();

        $totalChannels = 0;
        $migratedChannels = 0;
        $resolvedRecipients = 0;
        $unresolvedRecipients = 0;

        foreach ($channels as $channel) {
            $totalChannels++;
            $config = $channel->getRawConfiguration();
            $recipientKey = $channel->type === 'email' ? 'recipients' : 'phone_numbers';
            $recipients = $config[$recipientKey] ?? [];

            if (!is_array($recipients) || empty($recipients)) {
                continue;
            }

            // Check if already in new format (all entries are objects with user_id)
            $allNewFormat = true;
            $hasOldFormat = false;
            foreach ($recipients as $r) {
                if (is_string($r)) {
                    $allNewFormat = false;
                    $hasOldFormat = true;
                } elseif (is_array($r) && isset($r['user_id'])) {
                    // already new format
                } else {
                    $allNewFormat = false;
                }
            }

            if ($allNewFormat && !$hasOldFormat) {
                $io->verbose("  Channel #{$channel->id} '{$channel->name}' — already migrated, skipping.");
                continue;
            }

            // Load org members for this channel's organization
            $orgMembers = $orgUsersTable->find()
                ->contain(['Users'])
                ->where(['OrganizationUsers.organization_id' => $channel->organization_id])
                ->all()
                ->toArray();

            // Build lookup maps
            $emailToUser = [];
            $phoneToUser = [];
            foreach ($orgMembers as $membership) {
                if (!empty($membership->user->email)) {
                    $emailToUser[strtolower($membership->user->email)] = $membership->user;
                }
                if (!empty($membership->user->phone_number)) {
                    $phoneToUser[$membership->user->phone_number] = $membership->user;
                }
            }

            $newRecipients = [];
            $channelMigrated = false;

            foreach ($recipients as $recipient) {
                // Already in new format
                if (is_array($recipient) && isset($recipient['user_id'])) {
                    $newRecipients[] = $recipient;
                    continue;
                }

                // Old format — try to match
                if (!is_string($recipient)) {
                    continue;
                }

                $matched = false;

                if ($channel->type === 'email') {
                    $normalized = strtolower(trim($recipient));
                    if (isset($emailToUser[$normalized])) {
                        $user = $emailToUser[$normalized];
                        $newRecipients[] = ['user_id' => $user->id, 'type' => 'member'];
                        $resolvedRecipients++;
                        $matched = true;
                        $io->out("  Channel '{$channel->name}': matched {$recipient} -> user #{$user->id} ({$user->username})");
                    }
                } else {
                    // sms, whatsapp, voice_call — match by phone
                    $normalized = trim($recipient);
                    if (isset($phoneToUser[$normalized])) {
                        $user = $phoneToUser[$normalized];
                        $newRecipients[] = ['user_id' => $user->id, 'type' => 'member'];
                        $resolvedRecipients++;
                        $matched = true;
                        $io->out("  Channel '{$channel->name}': matched {$recipient} -> user #{$user->id} ({$user->username})");
                    }
                }

                if (!$matched) {
                    $unresolvedRecipients++;
                    $io->warning("  Channel '{$channel->name}' (#{$channel->id}): could not match '{$recipient}' to any org member");
                    // Keep the old format entry so we don't lose data
                    $newRecipients[] = $recipient;
                }

                $channelMigrated = true;
            }

            if ($channelMigrated) {
                $config[$recipientKey] = $newRecipients;

                if (!$dryRun) {
                    // Use direct field assignment to bypass the setter masking
                    $channel->set('configuration', $config);
                    if ($channelsTable->save($channel)) {
                        $io->success("  Saved channel '{$channel->name}' (#{$channel->id})");
                    } else {
                        $io->error("  Failed to save channel '{$channel->name}' (#{$channel->id})");
                        Log::error("MigrateChannelRecipients: Failed to save channel #{$channel->id}");
                    }
                } else {
                    $io->out("  [DRY RUN] Would save channel '{$channel->name}' (#{$channel->id})");
                }
                $migratedChannels++;
            }
        }

        $io->hr();
        $io->out('<info>Migration Summary:</info>');
        $io->out("  Total person-to-person channels scanned: {$totalChannels}");
        $io->out("  Channels migrated: {$migratedChannels}");
        $io->out("  Recipients resolved to users: {$resolvedRecipients}");
        $io->out("  Recipients unresolved (kept as-is): {$unresolvedRecipients}");

        if ($dryRun) {
            $io->out('');
            $io->warning('This was a dry run. Re-run without --dry-run to apply changes.');
        }

        return self::CODE_SUCCESS;
    }
}
