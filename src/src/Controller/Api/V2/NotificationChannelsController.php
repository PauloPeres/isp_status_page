<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Model\Entity\NotificationChannel;
use App\Service\Alert\DiscordAlertChannel;
use App\Service\Alert\EmailAlertChannel;
use App\Service\Alert\OpsGenieAlertChannel;
use App\Service\Alert\PagerDutyAlertChannel;
use App\Service\Alert\SlackAlertChannel;
use App\Service\Alert\SmsAlertChannel;
use App\Service\Alert\TelegramAlertChannel;
use App\Service\Alert\VoiceCallAlertChannel;
use App\Service\Alert\WebhookAlertChannel;
use App\Service\Alert\WhatsAppAlertChannel;
use App\Service\PlanService;
use Cake\Log\Log;

/**
 * NotificationChannelsController
 *
 * CRUD + test for notification channels within the current organization.
 */
class NotificationChannelsController extends AppController
{
    /**
     * GET /api/v2/notification-channels
     *
     * List all notification channels for the current organization.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('NotificationChannels');
        $channels = $table->find()
            ->where(['NotificationChannels.organization_id' => $this->currentOrgId])
            ->orderBy(['NotificationChannels.name' => 'ASC'])
            ->all();

        $this->success(['notification_channels' => $channels->toArray()]);
    }

    /**
     * GET /api/v2/notification-channels/{id}
     *
     * Get a single notification channel.
     *
     * @param string $id Channel ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $channel = $this->resolveOrgEntity('NotificationChannels', $id);

        if (!$channel) {
            $this->error('Notification channel not found', 404);

            return;
        }

        $this->success(['notification_channel' => $channel]);
    }

    /**
     * POST /api/v2/notification-channels
     *
     * Create a new notification channel.
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        // Plan feature check for non-email channels
        $type = $this->request->getData('type') ?? 'email';
        if ($type !== 'email') {
            $planService = new PlanService();
            $featureMap = [
                'slack' => 'slack_alerts',
                'discord' => 'slack_alerts',
                'telegram' => 'all_alert_channels',
                'sms' => 'all_alert_channels',
                'whatsapp' => 'all_alert_channels',
                'pagerduty' => 'all_alert_channels',
                'opsgenie' => 'all_alert_channels',
                'webhook' => 'webhook_alerts',
                'voice_call' => 'voice_call_alerts',
            ];
            $requiredFeature = $featureMap[$type] ?? null;
            if ($requiredFeature) {
                $check = $planService->checkFeature($this->currentOrgId, $requiredFeature);
                if (!$check['allowed']) {
                    $this->planLimitError(
                        "{$type} channels are not available on your {$check['plan_name']} plan. Upgrade to use this channel type.",
                        $check
                    );

                    return;
                }
            }
        }

        // Validate person-to-person channel recipients
        $validationError = $this->validatePersonToPersonRecipients(
            $type,
            $this->request->getData('configuration') ?? []
        );
        if ($validationError) {
            $this->error($validationError, 422);

            return;
        }

        $table = $this->fetchTable('NotificationChannels');
        $channel = $table->newEntity($this->request->getData());
        $channel->set('organization_id', $this->currentOrgId);

        if (!$table->save($channel)) {
            $this->error('Validation failed', 422, $channel->getErrors());

            return;
        }

        $this->success(['notification_channel' => $channel], 201);
    }

    /**
     * PUT|PATCH /api/v2/notification-channels/{id}
     *
     * Update a notification channel.
     *
     * @param string $id Channel ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put', 'patch']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('NotificationChannels');
        $channel = $this->resolveOrgEntity('NotificationChannels', $id);

        if (!$channel) {
            $this->error('Notification channel not found', 404);

            return;
        }

        // Validate person-to-person channel recipients
        $editType = $this->request->getData('type') ?? $channel->type;
        $editConfig = $this->request->getData('configuration') ?? [];
        $validationError = $this->validatePersonToPersonRecipients($editType, $editConfig);
        if ($validationError) {
            $this->error($validationError, 422);

            return;
        }

        $channel = $table->patchEntity($channel, $this->request->getData());
        if (!$table->save($channel)) {
            $this->error('Validation failed', 422, $channel->getErrors());

            return;
        }

        $this->success(['notification_channel' => $channel]);
    }

    /**
     * DELETE /api/v2/notification-channels/{id}
     *
     * Delete a notification channel. Fails if the channel is used by any policy step.
     *
     * @param string $id Channel ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('NotificationChannels');
        $channel = $this->resolveOrgEntity('NotificationChannels', $id);

        if (!$channel) {
            $this->error('Notification channel not found', 404);

            return;
        }

        // Check if channel is used by any policy step
        $stepsTable = $this->fetchTable('NotificationPolicySteps');
        $usedCount = $stepsTable->find()
            ->where(['NotificationPolicySteps.notification_channel_id' => $channel->id])
            ->count();

        if ($usedCount > 0) {
            $this->error(
                "Cannot delete this channel because it is used by {$usedCount} notification policy step(s). Remove it from all policies first.",
                409
            );

            return;
        }

        if (!$table->delete($channel)) {
            $this->error('Failed to delete notification channel', 500);

            return;
        }

        $this->success(['message' => 'Notification channel deleted']);
    }

    /**
     * POST /api/v2/notification-channels/{id}/test
     *
     * Send a test notification through the channel.
     *
     * @param string $id Channel ID.
     * @return void
     */
    public function test(string $id): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $channel = $this->resolveOrgEntity('NotificationChannels', $id);

        if (!$channel) {
            $this->error('Notification channel not found', 404);

            return;
        }

        try {
            $result = $this->sendTestNotification($channel);

            if ($result['success']) {
                $this->success(['message' => 'Test notification sent successfully']);
            } else {
                $this->error('Test notification failed: ' . ($result['error'] ?? 'Unknown error'), 422);
            }
        } catch (\Exception $e) {
            Log::error("Test notification failed for channel {$id}: {$e->getMessage()}");
            $this->error('Test notification failed. Check logs for details.', 500);
        }
    }

    /**
     * Validate person-to-person channel recipients.
     *
     * For email/sms/whatsapp/voice_call channels with new-format recipients
     * (user_id objects), verify that all user_ids belong to the current org
     * and have the required contact info.
     *
     * @param string $type Channel type.
     * @param array|string $configuration Channel configuration.
     * @return string|null Error message, or null if valid.
     */
    private function validatePersonToPersonRecipients(string $type, array|string $configuration): ?string
    {
        $personToPersonTypes = ['email', 'sms', 'whatsapp', 'voice_call'];
        if (!in_array($type, $personToPersonTypes, true)) {
            return null;
        }

        if (is_string($configuration)) {
            $configuration = json_decode($configuration, true) ?: [];
        }

        // Determine the recipient key
        $recipientKey = $type === 'email' ? 'recipients' : 'phone_numbers';
        $recipients = $configuration[$recipientKey] ?? [];

        if (!is_array($recipients)) {
            return null;
        }

        // Check if any recipients use the new user_id format
        $userIds = [];
        foreach ($recipients as $recipient) {
            if (is_array($recipient) && isset($recipient['user_id'])) {
                $userIds[] = (int)$recipient['user_id'];
            }
            // Old format (plain strings) — skip validation, backward compatible
        }

        if (empty($userIds)) {
            return null;
        }

        // Verify all user_ids belong to the current organization
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $usersTable = $this->fetchTable('Users');

        foreach ($userIds as $userId) {
            $membership = $orgUsersTable->find()
                ->where([
                    'OrganizationUsers.user_id' => $userId,
                    'OrganizationUsers.organization_id' => $this->currentOrgId,
                ])
                ->first();

            if (!$membership) {
                return "User ID {$userId} does not belong to your organization.";
            }

            // Verify user has required contact info
            try {
                $user = $usersTable->get($userId);
            } catch (\Exception $e) {
                return "User ID {$userId} not found.";
            }

            if ($type === 'email' && empty($user->email)) {
                return "User '{$user->username}' does not have an email address configured.";
            }

            if (in_array($type, ['sms', 'whatsapp', 'voice_call'], true) && empty($user->phone_number)) {
                return "User '{$user->username}' does not have a phone number configured.";
            }
        }

        return null;
    }

    /**
     * Send a test notification through the given channel.
     *
     * Creates a mock AlertRule, Monitor, and Incident to use with
     * the existing ChannelInterface implementations.
     *
     * @param \App\Model\Entity\NotificationChannel $channel The notification channel.
     * @return array Result with 'success' and optional 'error' keys.
     */
    private function sendTestNotification(NotificationChannel $channel): array
    {
        $config = $channel->getRawConfiguration();

        // Build a mock AlertRule with the channel's configuration as recipients
        $alertRule = $this->fetchTable('AlertRules')->newEmptyEntity();
        $alertRule->set('channel', $channel->type);
        $alertRule->set('name', 'Test Notification');

        // Map channel config to alert rule recipients format
        $recipients = $this->mapConfigToRecipients($channel->type, $config);
        $alertRule->set('recipients', json_encode($recipients));

        // Build a mock Monitor — try to use a real monitor from the org
        $monitorsTable = $this->fetchTable('Monitors');
        $monitor = $monitorsTable->find()
            ->where(['Monitors.organization_id' => $this->currentOrgId])
            ->first();

        if (!$monitor) {
            // No monitors exist — build a minimal in-memory mock
            $monitor = $monitorsTable->newEmptyEntity();
            $monitor->set('id', 0);
            $monitor->set('name', 'Test Monitor');
            $monitor->set('type', 'http');
            $monitor->set('status', 'down');
            $monitor->set('configuration', json_encode(['url' => 'https://example.com']));
            $monitor->set('organization_id', $this->currentOrgId);
        }

        // Build a mock Incident with a valid monitor_id
        $incident = $this->fetchTable('Incidents')->newEmptyEntity();
        $incident->set('monitor_id', $monitor->id);
        $incident->set('title', 'Test Notification');
        $incident->set('status', 'investigating');
        $incident->set('started_at', new \Cake\I18n\DateTime());

        // Get the appropriate alert channel implementation
        $alertChannel = $this->getAlertChannel($channel->type);
        if (!$alertChannel) {
            return ['success' => false, 'error' => "No implementation available for channel type: {$channel->type}"];
        }

        $result = $alertChannel->send($alertRule, $monitor, $incident);

        return $result;
    }

    /**
     * Map notification channel configuration to alert rule recipients format.
     *
     * @param string $type Channel type.
     * @param array $config Channel configuration.
     * @return array Recipients array.
     */
    private function mapConfigToRecipients(string $type, array $config): array
    {
        return match ($type) {
            'email' => $config['recipients'] ?? [],
            'slack' => [$config['webhook_url'] ?? ''],
            'discord' => [$config['webhook_url'] ?? ''],
            'telegram' => [$config['chat_id'] ?? ''],
            'sms' => $config['phone_numbers'] ?? [],
            'whatsapp' => $config['phone_numbers'] ?? [],
            'pagerduty' => [$config['routing_key'] ?? ''],
            'opsgenie' => [$config['api_key'] ?? ''],
            'webhook' => [$config['url'] ?? ''],
            'voice_call' => $config['phone_numbers'] ?? [],
            default => [],
        };
    }

    /**
     * Get the alert channel implementation for a given type.
     *
     * @param string $type Channel type.
     * @return \App\Service\Alert\ChannelInterface|null
     */
    private function getAlertChannel(string $type): ?\App\Service\Alert\ChannelInterface
    {
        return match ($type) {
            'email' => new EmailAlertChannel(),
            'slack' => new SlackAlertChannel(),
            'discord' => new DiscordAlertChannel(),
            'telegram' => new TelegramAlertChannel(),
            'sms' => new SmsAlertChannel(),
            'whatsapp' => new WhatsAppAlertChannel(),
            'pagerduty' => new PagerDutyAlertChannel(),
            'opsgenie' => new OpsGenieAlertChannel(),
            'webhook' => new WebhookAlertChannel(),
            'voice_call' => new VoiceCallAlertChannel(),
            default => null,
        };
    }
}
