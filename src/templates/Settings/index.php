<?php
/**
 * @var \App\View\AppView $this
 * @var array $settings
 */
$this->assign('title', __d('settings', 'Organization Settings'));

// Setting label translations — only org-level settings
$labels = [
    // General
    'site_name' => __d('settings', 'Organization Name'),
    'site_logo_url' => __d('settings', 'Custom Logo URL'),
    'site_language' => __d('settings', 'Language'),
    'site_timezone' => __d('settings', 'Timezone'),
    'status_page_title' => __d('settings', 'Status Page Title'),
    'support_email' => __d('settings', 'Support Email'),

    // Notifications
    'enable_email_alerts' => __d('settings', 'Enable Email Alerts'),
    'notification_email_on_incident_created' => __d('settings', 'Email on Incident Created'),
    'notification_email_on_incident_resolved' => __d('settings', 'Email on Incident Resolved'),
    'notification_email_on_down' => __d('settings', 'Email on Monitor Down'),
    'notification_email_on_up' => __d('settings', 'Email on Monitor Up'),
    'alert_throttle_minutes' => __d('settings', 'Alert Throttle (minutes)'),
    'notification_default_cooldown' => __d('settings', 'Default Cooldown (minutes)'),
];

// Description translations (help text)
$descriptions = [
    // General
    'site_name' => __d('settings', 'Your organization display name shown on the status page'),
    'site_logo_url' => __d('settings', 'Full URL of logo image (PNG, JPG, SVG). Leave empty for default logo.'),
    'site_language' => __d('settings', 'Preferred language for your organization'),
    'site_timezone' => __d('settings', 'Timezone for displaying dates and times'),
    'status_page_title' => __d('settings', 'Title displayed on the public status page'),
    'support_email' => __d('settings', 'Support email shown in public page footer'),

    // Notifications
    'enable_email_alerts' => __d('settings', 'Enable sending email alerts to subscribers'),
    'notification_email_on_incident_created' => __d('settings', 'Send email when a new incident is created'),
    'notification_email_on_incident_resolved' => __d('settings', 'Send email when an incident is resolved'),
    'notification_email_on_down' => __d('settings', 'Send email when a monitor goes offline'),
    'notification_email_on_up' => __d('settings', 'Send email when a monitor comes back online'),
    'alert_throttle_minutes' => __d('settings', 'Minimum interval in minutes between alerts for the same monitor'),
    'notification_default_cooldown' => __d('settings', 'Default cooldown period in minutes before re-sending notifications'),
];

/**
 * Get translated label for setting key
 */
function getLabel($key, $labels) {
    return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
}

/**
 * Get translated description for setting key
 */
function getDescription($key, $descriptions, $fallback = '') {
    return $descriptions[$key] ?? $fallback;
}
?>

<style>
    .settings-header {
        margin-bottom: 24px;
    }

    .settings-header h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
    }

    .settings-header p {
        margin: 8px 0 0;
        color: #666;
        font-size: 14px;
    }

    .tabs-container {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .tabs-nav {
        display: flex;
        border-bottom: 2px solid #e0e0e0;
        background: #f8f9fa;
    }

    .tab-button {
        padding: 16px 24px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        color: #666;
        transition: all 0.2s;
    }

    .tab-button:hover {
        background: #e9ecef;
        color: #333;
    }

    .tab-button.active {
        color: #3b82f6;
        border-bottom-color: #3b82f6;
        background: white;
    }

    .tab-content {
        display: none;
        padding: 24px;
    }

    .tab-content.active {
        display: block;
    }

    .settings-form {
        max-width: 800px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 6px;
    }

    .form-group .help-text {
        display: block;
        font-size: 13px;
        color: #666;
        margin-top: 4px;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="number"],
    .form-group input[type="password"],
    .form-group input[type="url"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        font-size: 14px;
    }

    .form-group input[type="checkbox"] {
        width: auto;
        margin-right: 8px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        font-weight: 400;
    }

    .checkbox-label label {
        cursor: pointer;
        user-select: none;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
        margin-top: 24px;
    }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #3b82f6;
        color: white;
    }

    .btn-primary:hover {
        background: #2563eb;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .btn-warning {
        background: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background: #d97706;
    }

    .empty-category {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    @media (max-width: 768px) {
        .tabs-nav {
            flex-direction: column;
        }

        .tab-button {
            text-align: left;
            min-height: 44px;
            padding: 12px 16px;
        }

        .tab-content {
            padding: 16px;
        }

        .settings-form {
            max-width: 100%;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group input[type="password"],
        .form-group input[type="url"],
        .form-group select,
        .form-group textarea {
            font-size: 16px;
            min-height: 44px;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
            min-height: 44px;
        }
    }
</style>

<div class="settings-header">
    <h2><?= __d('settings', 'Organization Settings') ?></h2>
    <p><?= __d('settings', 'Configure your organization preferences, notification settings, and notification channels.') ?></p>
</div>

<div class="tabs-container">
    <div class="tabs-nav">
        <button class="tab-button active" data-tab="general"><?= __d('settings', 'General') ?></button>
        <button class="tab-button" data-tab="notifications"><?= __d('settings', 'Notifications') ?></button>
        <button class="tab-button" data-tab="channels"><?= __d('settings', 'Channels') ?></button>
    </div>

    <!-- General Settings -->
    <div class="tab-content active" id="general">
        <?php if (count($settings['general']) > 0): ?>
            <?= $this->Form->create(null, ['url' => ['action' => 'save'], 'class' => 'settings-form']) ?>
            <?= $this->Form->hidden('category', ['value' => 'general']) ?>

            <?php foreach ($settings['general'] as $setting): ?>
                <div class="form-group">
                    <label for="<?= h($setting->key) ?>">
                        <?= h(getLabel($setting->key, $labels)) ?>
                    </label>

                    <?php if ($setting->key === 'site_language'): ?>
                        <?= $this->Form->select("settings.{$setting->key}", [
                            'pt_BR' => __d('settings', 'Portuguese (Brazil)'),
                            'en' => __d('settings', 'English'),
                            'es' => __d('settings', 'Español'),
                        ], [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                        ]) ?>
                        <?php
                            $desc = getDescription($setting->key, $descriptions);
                            if ($desc):
                        ?>
                            <span class="help-text"><?= h($desc) ?></span>
                        <?php endif; ?>
                    <?php elseif ($setting->key === 'site_timezone'): ?>
                        <?= $this->Form->select("settings.{$setting->key}",
                            array_combine(
                                \DateTimeZone::listIdentifiers(),
                                \DateTimeZone::listIdentifiers()
                            ), [
                            'value' => $setting->getTypedValue() ?: 'America/Sao_Paulo',
                            'class' => 'form-control',
                        ]) ?>
                        <?php
                            $desc = getDescription($setting->key, $descriptions);
                            if ($desc):
                        ?>
                            <span class="help-text"><?= h($desc) ?></span>
                        <?php endif; ?>
                    <?php elseif ($setting->type === 'boolean'): ?>
                        <div class="checkbox-label">
                            <?= $this->Form->checkbox("settings.{$setting->key}", [
                                'checked' => $setting->getTypedValue(),
                                'hiddenField' => true,
                                'id' => 'setting-' . h($setting->key),
                            ]) ?>
                            <label for="setting-<?= h($setting->key) ?>">
                                <?= h(getDescription($setting->key, $descriptions) ?: __d('settings', 'Enable this option')) ?>
                            </label>
                        </div>
                    <?php elseif ($setting->type === 'integer'): ?>
                        <?= $this->Form->number("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                        ]) ?>
                        <?php
                            $desc = getDescription($setting->key, $descriptions);
                            if ($desc):
                        ?>
                            <span class="help-text"><?= h($desc) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= $this->Form->text("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                        ]) ?>
                        <?php
                            $desc = getDescription($setting->key, $descriptions);
                            if ($desc):
                        ?>
                            <span class="help-text"><?= h($desc) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <?= $this->Form->button(__d('settings', 'Save Settings'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
                <?= $this->Form->postLink(
                    __d('settings', 'Restore Defaults'),
                    ['action' => 'reset'],
                    [
                        'class' => 'btn btn-warning',
                        'data' => ['category' => 'general'],
                        'confirm' => __d('settings', 'Are you sure you want to restore settings to default values?')
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>
        <?php else: ?>
            <div class="empty-category">
                <p><?= __d('settings', 'No general settings available.') ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Notifications Settings -->
    <div class="tab-content" id="notifications">
        <?php if (count($settings['notifications']) > 0): ?>
            <?= $this->Form->create(null, ['url' => ['action' => 'save'], 'class' => 'settings-form']) ?>
            <?= $this->Form->hidden('category', ['value' => 'notifications']) ?>

            <?php foreach ($settings['notifications'] as $setting): ?>
                <div class="form-group">
                    <label for="<?= h($setting->key) ?>">
                        <?= h(getLabel($setting->key, $labels)) ?>
                    </label>

                    <?php if ($setting->type === 'boolean'): ?>
                        <div class="checkbox-label">
                            <?= $this->Form->checkbox("settings.{$setting->key}", [
                                'checked' => $setting->getTypedValue(),
                                'hiddenField' => true,
                                'id' => 'setting-' . h($setting->key),
                            ]) ?>
                            <label for="setting-<?= h($setting->key) ?>">
                                <?= h(getDescription($setting->key, $descriptions) ?: __d('settings', 'Enable this option')) ?>
                            </label>
                        </div>
                    <?php elseif ($setting->type === 'integer'): ?>
                        <?= $this->Form->number("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                            'min' => 0,
                        ]) ?>
                        <?php
                            $desc = getDescription($setting->key, $descriptions);
                            if ($desc):
                        ?>
                            <span class="help-text"><?= h($desc) ?></span>
                        <?php endif; ?>
                    <?php elseif ($setting->type === 'json'): ?>
                        <?= $this->Form->textarea("settings.{$setting->key}", [
                            'value' => is_array($setting->getTypedValue())
                                ? json_encode($setting->getTypedValue(), JSON_PRETTY_PRINT)
                                : $setting->getTypedValue(),
                            'class' => 'form-control',
                            'rows' => 6,
                        ]) ?>
                        <?php
                            $desc = getDescription($setting->key, $descriptions);
                            if ($desc):
                        ?>
                            <span class="help-text"><?= h($desc) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= $this->Form->text("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                        ]) ?>
                        <?php
                            $desc = getDescription($setting->key, $descriptions);
                            if ($desc):
                        ?>
                            <span class="help-text"><?= h($desc) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <?= $this->Form->button(__d('settings', 'Save Settings'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
                <?= $this->Form->postLink(
                    __d('settings', 'Restore Defaults'),
                    ['action' => 'reset'],
                    [
                        'class' => 'btn btn-warning',
                        'data' => ['category' => 'notifications'],
                        'confirm' => __d('settings', 'Are you sure you want to restore settings to default values?')
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>
        <?php else: ?>
            <div class="empty-category">
                <p><?= __d('settings', 'No notification settings available.') ?></p>
            </div>
        <?php endif; ?>

        <!-- Quiet Hours Settings (P4-008) -->
        <div class="settings-form" style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e0e0e0;">
            <h3 style="margin: 0 0 8px; font-size: 18px; font-weight: 600;"><?= __d('settings', 'Quiet Hours') ?></h3>
            <p style="margin: 0 0 20px; color: #666; font-size: 14px;">
                <?= __d('settings', 'During quiet hours, non-critical alert notifications will be suppressed. Critical alerts (e.g., complete outages) will still be delivered unless you choose to suppress all alerts. This helps reduce noise during off-hours without missing important events.') ?>
            </p>

            <?= $this->Form->create(null, ['url' => ['action' => 'saveQuietHours'], 'class' => 'settings-form']) ?>

            <div class="form-group">
                <div class="checkbox-label">
                    <?= $this->Form->checkbox('quiet_hours_enabled', [
                        'checked' => !empty($quietHours['enabled']),
                        'hiddenField' => true,
                        'id' => 'quiet-hours-enabled',
                    ]) ?>
                    <label for="quiet-hours-enabled">
                        <?= __d('settings', 'Enable quiet hours for this organization') ?>
                    </label>
                </div>
            </div>

            <div class="form-group" style="display: flex; gap: 16px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 150px;">
                    <label for="quiet-hours-start"><?= __d('settings', 'Start Time') ?></label>
                    <input type="time" id="quiet-hours-start" name="quiet_hours_start"
                           value="<?= h($quietHours['start'] ?? '22:00') ?>"
                           class="form-control" style="width: 100%; padding: 10px 12px; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 14px;">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label for="quiet-hours-end"><?= __d('settings', 'End Time') ?></label>
                    <input type="time" id="quiet-hours-end" name="quiet_hours_end"
                           value="<?= h($quietHours['end'] ?? '08:00') ?>"
                           class="form-control" style="width: 100%; padding: 10px 12px; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 14px;">
                </div>
            </div>

            <div class="form-group">
                <label for="quiet-hours-timezone"><?= __d('settings', 'Timezone') ?></label>
                <?= $this->Form->select('quiet_hours_timezone',
                    array_combine(
                        \DateTimeZone::listIdentifiers(),
                        \DateTimeZone::listIdentifiers()
                    ), [
                    'value' => $quietHours['timezone'] ?? 'UTC',
                    'class' => 'form-control',
                    'id' => 'quiet-hours-timezone',
                ]) ?>
                <span class="help-text"><?= __d('settings', 'The timezone used to determine when quiet hours are active.') ?></span>
            </div>

            <div class="form-group">
                <label for="quiet-hours-suppress-level"><?= __d('settings', 'Suppress Level') ?></label>
                <?= $this->Form->select('quiet_hours_suppress_level', [
                    'non_critical' => __d('settings', 'Non-critical only — critical alerts still delivered'),
                    'all' => __d('settings', 'All alerts — suppress everything during quiet hours'),
                    'none' => __d('settings', 'None — quiet hours enabled but no suppression'),
                ], [
                    'value' => $quietHours['suppress_level'] ?? 'non_critical',
                    'class' => 'form-control',
                    'id' => 'quiet-hours-suppress-level',
                ]) ?>
                <span class="help-text"><?= __d('settings', 'Choose which alerts to suppress during quiet hours. "Non-critical only" is recommended for most teams.') ?></span>
            </div>

            <div class="form-actions">
                <?= $this->Form->button(__d('settings', 'Save Quiet Hours'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
            </div>

            <?= $this->Form->end() ?>
        </div>
    </div>

    <!-- Notification Channels -->
    <div class="tab-content" id="channels">
        <div class="settings-form">
            <p style="margin-bottom: 20px; color: #666; font-size: 14px;">
                <?= __d('settings', 'Configure notification channels to receive alerts when monitors go down or recover.') ?>
            </p>

            <?= $this->Form->create(null, ['url' => ['action' => 'saveChannels'], 'id' => 'channels-form']) ?>

            <!-- Email Channel -->
            <div class="channel-card">
                <div class="channel-header">
                    <span class="channel-icon">&#9993;</span>
                    <h4><?= __d('settings', 'Email') ?></h4>
                    <span class="badge badge-success"><?= __d('settings', 'Managed by platform') ?></span>
                </div>
                <div class="channel-body">
                    <p style="color: #666; font-size: 13px;">
                        <?= __d('settings', 'Email delivery is managed by the platform. Enable or disable email alerts in the Notifications tab.') ?>
                    </p>
                </div>
            </div>

            <!-- Slack Channel -->
            <div class="channel-card">
                <div class="channel-header">
                    <span class="channel-icon">&#128172;</span>
                    <h4>Slack</h4>
                    <?php
                    $slackUrl = $settings['channels']['channel_slack_webhook_url'] ?? '';
                    ?>
                    <?php if (!empty($slackUrl)): ?>
                        <span class="badge badge-success"><?= __d('settings', 'Connected') ?></span>
                    <?php else: ?>
                        <span class="badge badge-secondary"><?= __d('settings', 'Not configured') ?></span>
                    <?php endif; ?>
                </div>
                <div class="channel-body">
                    <label><?= __d('settings', 'Webhook URL') ?></label>
                    <input type="url" name="channel_slack_webhook_url"
                           value="<?= h($slackUrl) ?>"
                           placeholder="https://hooks.slack.com/services/...">
                    <button type="button" class="btn-sm btn-test" onclick="testChannel('slack')"><?= __d('settings', 'Test') ?></button>
                    <div id="test-result-slack" class="test-result"></div>
                </div>
            </div>

            <!-- Discord Channel -->
            <div class="channel-card">
                <div class="channel-header">
                    <span class="channel-icon">&#127918;</span>
                    <h4>Discord</h4>
                    <?php
                    $discordUrl = $settings['channels']['channel_discord_webhook_url'] ?? '';
                    ?>
                    <?php if (!empty($discordUrl)): ?>
                        <span class="badge badge-success"><?= __d('settings', 'Connected') ?></span>
                    <?php else: ?>
                        <span class="badge badge-secondary"><?= __d('settings', 'Not configured') ?></span>
                    <?php endif; ?>
                </div>
                <div class="channel-body">
                    <label><?= __d('settings', 'Webhook URL') ?></label>
                    <input type="url" name="channel_discord_webhook_url"
                           value="<?= h($discordUrl) ?>"
                           placeholder="https://discord.com/api/webhooks/...">
                    <button type="button" class="btn-sm btn-test" onclick="testChannel('discord')"><?= __d('settings', 'Test') ?></button>
                    <div id="test-result-discord" class="test-result"></div>
                </div>
            </div>

            <!-- Telegram Channel -->
            <div class="channel-card">
                <div class="channel-header">
                    <span class="channel-icon">&#9992;</span>
                    <h4>Telegram</h4>
                    <?php
                    $telegramToken = $settings['channels']['channel_telegram_bot_token'] ?? '';
                    $telegramChatId = $settings['channels']['channel_telegram_chat_id'] ?? '';
                    ?>
                    <?php if (!empty($telegramToken) && !empty($telegramChatId)): ?>
                        <span class="badge badge-success"><?= __d('settings', 'Connected') ?></span>
                    <?php else: ?>
                        <span class="badge badge-secondary"><?= __d('settings', 'Not configured') ?></span>
                    <?php endif; ?>
                </div>
                <div class="channel-body">
                    <label><?= __d('settings', 'Bot Token') ?></label>
                    <input type="password" name="channel_telegram_bot_token"
                           value="<?= h($telegramToken) ?>"
                           placeholder="123456:ABC-DEF..."
                           autocomplete="new-password">
                    <label><?= __d('settings', 'Chat ID') ?></label>
                    <input type="text" name="channel_telegram_chat_id"
                           value="<?= h($telegramChatId) ?>"
                           placeholder="-1001234567890">
                    <button type="button" class="btn-sm btn-test" onclick="testChannel('telegram')"><?= __d('settings', 'Test') ?></button>
                    <div id="test-result-telegram" class="test-result"></div>
                </div>
            </div>

            <!-- Custom Webhook Channel -->
            <div class="channel-card">
                <div class="channel-header">
                    <span class="channel-icon">&#128279;</span>
                    <h4><?= __d('settings', 'Custom Webhook') ?></h4>
                    <?php
                    $webhookUrl = $settings['channels']['channel_webhook_url'] ?? '';
                    $webhookSecret = $settings['channels']['channel_webhook_secret'] ?? '';
                    ?>
                    <?php if (!empty($webhookUrl)): ?>
                        <span class="badge badge-success"><?= __d('settings', 'Connected') ?></span>
                    <?php else: ?>
                        <span class="badge badge-secondary"><?= __d('settings', 'Not configured') ?></span>
                    <?php endif; ?>
                </div>
                <div class="channel-body">
                    <label><?= __d('settings', 'Webhook URL') ?></label>
                    <input type="url" name="channel_webhook_url"
                           value="<?= h($webhookUrl) ?>"
                           placeholder="https://your-server.com/webhook">
                    <label><?= __d('settings', 'Secret (for HMAC signature)') ?></label>
                    <input type="password" name="channel_webhook_secret"
                           value="<?= h($webhookSecret) ?>"
                           placeholder="<?= __d('settings', 'Optional shared secret') ?>"
                           autocomplete="new-password">
                    <button type="button" class="btn-sm btn-test" onclick="testChannel('webhook')"><?= __d('settings', 'Test') ?></button>
                    <div id="test-result-webhook" class="test-result"></div>
                </div>
            </div>

            <div class="form-actions">
                <?= $this->Form->button(__d('settings', 'Save Channel Settings'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
            </div>

            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<script>
// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    // Handle hash navigation
    const hash = window.location.hash.substring(1);
    if (hash) {
        switchTab(hash);
    }

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            switchTab(tabName);
            window.location.hash = tabName;
        });
    });

    function switchTab(tabName) {
        // Remove active class from all buttons and contents
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(content => content.classList.remove('active'));

        // Add active class to selected button and content
        const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
        const activeContent = document.getElementById(tabName);

        if (activeButton && activeContent) {
            activeButton.classList.add('active');
            activeContent.classList.add('active');
        }
    }
});

// Test notification channel
function testChannel(channel) {
    var resultDiv = document.getElementById('test-result-' + channel);
    resultDiv.className = 'test-result';
    resultDiv.style.display = 'block';
    resultDiv.textContent = 'Testing...';
    resultDiv.style.background = '#f3f4f6';
    resultDiv.style.color = '#374151';

    // Gather channel-specific data from the form
    var form = document.getElementById('channels-form');
    var formData = new FormData(form);
    formData.append('channel', channel);

    fetch('<?= $this->Url->build(['action' => 'testNotificationChannel']) ?>', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-Token': document.querySelector('input[name="_csrfToken"]')?.value || ''
        },
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            resultDiv.className = 'test-result success';
            resultDiv.textContent = data.message || 'Test message sent successfully!';
        } else {
            resultDiv.className = 'test-result error';
            resultDiv.textContent = data.message || 'Test failed.';
        }
    })
    .catch(function(err) {
        resultDiv.className = 'test-result error';
        resultDiv.textContent = 'Network error: ' + err.message;
    });
}
</script>
