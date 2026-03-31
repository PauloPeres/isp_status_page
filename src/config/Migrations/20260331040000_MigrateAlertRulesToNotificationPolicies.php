<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * MigrateAlertRulesToNotificationPolicies Migration
 *
 * Data migration that converts existing alert_rules into the new
 * notification_channels + notification_policies + notification_policy_steps system.
 *
 * For each unique (organization, channel, recipients) combination, a NotificationChannel is created.
 * For each alert_rule, a NotificationPolicy is created with one step pointing to the channel.
 * If the alert_rule's monitor has an escalation_policy_id, the escalation steps are added
 * as additional policy steps.
 * Finally, the monitor's notification_policy_id is set.
 */
class MigrateAlertRulesToNotificationPolicies extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        try {
            $alertRules = $this->fetchAll("SELECT * FROM alert_rules ORDER BY id");
        } catch (\Exception $e) {
            // alert_rules table may not exist or be empty
            echo "  [WARN] Could not read alert_rules: " . $e->getMessage() . "\n";
            return;
        }

        if (empty($alertRules)) {
            echo "  [INFO] No alert_rules found — nothing to migrate.\n";
            return;
        }

        $channelCache = []; // key: "orgId:type:md5(recipients)" => channel_id
        $now = date('Y-m-d H:i:s');

        foreach ($alertRules as $rule) {
            try {
                $orgId = (int)($rule['organization_id'] ?? 0);
                $monitorId = (int)($rule['monitor_id'] ?? 0);
                $channel = $rule['channel'] ?? 'email';
                $recipients = $rule['recipients'] ?? '[]';
                $triggerOn = $rule['trigger_on'] ?? 'on_down';
                $throttleMinutes = (int)($rule['throttle_minutes'] ?? 5);
                $active = (bool)($rule['active'] ?? true);

                if ($orgId === 0 || $monitorId === 0) {
                    echo "  [WARN] Skipping alert_rule id={$rule['id']}: missing organization_id or monitor_id.\n";
                    continue;
                }

                // Map trigger_on to trigger_type
                $triggerType = match ($triggerOn) {
                    'on_down' => 'down',
                    'on_up' => 'up',
                    'on_degraded' => 'degraded',
                    'on_change', 'on_any' => 'any',
                    default => 'down',
                };

                // --- Create or reuse NotificationChannel ---
                $cacheKey = "{$orgId}:{$channel}:" . md5($recipients);

                if (!isset($channelCache[$cacheKey])) {
                    $recipientsList = json_decode($recipients, true) ?: [];
                    $config = json_encode(['recipients' => $recipientsList]);
                    $channelName = ucfirst($channel) . ' — ' . implode(', ', array_slice($recipientsList, 0, 3));
                    if (count($recipientsList) > 3) {
                        $channelName .= ' +' . (count($recipientsList) - 3) . ' more';
                    }
                    if (empty($recipientsList)) {
                        $channelName = ucfirst($channel) . ' (migrated)';
                    }

                    $this->execute(
                        "INSERT INTO notification_channels (organization_id, name, type, configuration, active, created, modified) " .
                        "VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [$orgId, $channelName, $channel, $config, true, $now, $now]
                    );

                    // Fetch the inserted channel id
                    $row = $this->fetchRow(
                        "SELECT id FROM notification_channels WHERE organization_id = ? AND type = ? AND configuration = ? ORDER BY id DESC LIMIT 1",
                        [$orgId, $channel, $config]
                    );

                    if (!$row) {
                        echo "  [WARN] Could not retrieve inserted notification_channel for alert_rule id={$rule['id']}.\n";
                        continue;
                    }

                    $channelId = (int)$row['id'];
                    $channelCache[$cacheKey] = $channelId;
                } else {
                    $channelId = $channelCache[$cacheKey];
                }

                // --- Create NotificationPolicy ---
                $monitorRow = $this->fetchRow(
                    "SELECT name, escalation_policy_id FROM monitors WHERE id = ?",
                    [$monitorId]
                );

                $monitorName = $monitorRow['name'] ?? "Monitor #{$monitorId}";
                $escalationPolicyId = !empty($monitorRow['escalation_policy_id']) ? (int)$monitorRow['escalation_policy_id'] : null;

                $policyName = "Policy for {$monitorName} ({$triggerType})";

                $this->execute(
                    "INSERT INTO notification_policies (organization_id, name, description, trigger_type, repeat_interval_minutes, active, created, modified) " .
                    "VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$orgId, $policyName, "Migrated from alert_rule id={$rule['id']}", $triggerType, $throttleMinutes, $active, $now, $now]
                );

                $policyRow = $this->fetchRow(
                    "SELECT id FROM notification_policies WHERE organization_id = ? AND name = ? ORDER BY id DESC LIMIT 1",
                    [$orgId, $policyName]
                );

                if (!$policyRow) {
                    echo "  [WARN] Could not retrieve inserted notification_policy for alert_rule id={$rule['id']}.\n";
                    continue;
                }

                $policyId = (int)$policyRow['id'];

                // --- Create step 1 (immediate) ---
                $stepOrder = 1;
                $this->execute(
                    "INSERT INTO notification_policy_steps (notification_policy_id, step_order, delay_minutes, notification_channel_id, notify_on_resolve, created) " .
                    "VALUES (?, ?, ?, ?, ?, ?)",
                    [$policyId, $stepOrder, 0, $channelId, true, $now]
                );

                // --- If escalation_policy_id exists, add escalation steps ---
                if ($escalationPolicyId !== null) {
                    try {
                        $escalationSteps = $this->fetchAll(
                            "SELECT * FROM escalation_steps WHERE escalation_policy_id = ? ORDER BY step_number ASC",
                            [$escalationPolicyId]
                        );

                        foreach ($escalationSteps as $escStep) {
                            $stepOrder++;
                            $escChannel = $escStep['channel'] ?? 'email';
                            $escRecipients = $escStep['recipients'] ?? '[]';
                            $escWaitMinutes = (int)($escStep['wait_minutes'] ?? 5);

                            // Create or reuse channel for escalation step
                            $escCacheKey = "{$orgId}:{$escChannel}:" . md5($escRecipients);

                            if (!isset($channelCache[$escCacheKey])) {
                                $escRecipientsList = json_decode($escRecipients, true) ?: [];
                                $escConfig = json_encode(['recipients' => $escRecipientsList]);
                                $escChannelName = ucfirst($escChannel) . ' (escalation) — ' . implode(', ', array_slice($escRecipientsList, 0, 3));
                                if (empty($escRecipientsList)) {
                                    $escChannelName = ucfirst($escChannel) . ' escalation (migrated)';
                                }

                                $this->execute(
                                    "INSERT INTO notification_channels (organization_id, name, type, configuration, active, created, modified) " .
                                    "VALUES (?, ?, ?, ?, ?, ?, ?)",
                                    [$orgId, $escChannelName, $escChannel, $escConfig, true, $now, $now]
                                );

                                $escChannelRow = $this->fetchRow(
                                    "SELECT id FROM notification_channels WHERE organization_id = ? AND type = ? AND configuration = ? ORDER BY id DESC LIMIT 1",
                                    [$orgId, $escChannel, $escConfig]
                                );

                                if (!$escChannelRow) {
                                    echo "  [WARN] Could not retrieve inserted escalation notification_channel for step_number={$escStep['step_number']}.\n";
                                    continue;
                                }

                                $escChannelId = (int)$escChannelRow['id'];
                                $channelCache[$escCacheKey] = $escChannelId;
                            } else {
                                $escChannelId = $channelCache[$escCacheKey];
                            }

                            $this->execute(
                                "INSERT INTO notification_policy_steps (notification_policy_id, step_order, delay_minutes, notification_channel_id, notify_on_resolve, created) " .
                                "VALUES (?, ?, ?, ?, ?, ?)",
                                [$policyId, $stepOrder, $escWaitMinutes, $escChannelId, true, $now]
                            );
                        }
                    } catch (\Exception $e) {
                        echo "  [WARN] Could not read escalation_steps for policy_id={$escalationPolicyId}: {$e->getMessage()}\n";
                    }
                }

                // --- Update monitor to point to the new notification policy ---
                $this->execute(
                    "UPDATE monitors SET notification_policy_id = ? WHERE id = ?",
                    [$policyId, $monitorId]
                );

                echo "  [OK] Migrated alert_rule id={$rule['id']} -> notification_policy id={$policyId} for monitor id={$monitorId}\n";

            } catch (\Exception $e) {
                echo "  [WARN] Failed to migrate alert_rule id={$rule['id']}: {$e->getMessage()}\n";
                continue;
            }
        }
    }

    /**
     * Down Method.
     *
     * Data migration is not reversible — the new notification data would be
     * removed by rolling back the schema migrations that created the tables.
     *
     * @return void
     */
    public function down(): void
    {
        // Clear notification_policy_id from monitors that were set by this migration
        try {
            $this->execute("UPDATE monitors SET notification_policy_id = NULL WHERE notification_policy_id IS NOT NULL");
        } catch (\Exception $e) {
            echo "  [WARN] Could not clear notification_policy_id from monitors: {$e->getMessage()}\n";
        }

        // The notification_channels, notification_policies, and notification_policy_steps
        // rows created by this migration will be removed when those tables are dropped
        // by their respective schema migration rollbacks.
    }
}
