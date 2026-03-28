<?php
/**
 * @var \App\View\AppView $this
 * @var array $settings
 */
$this->assign('title', __d('settings', 'System Settings'));

// Setting label translations
$labels = [
    // General
    'site_name' => __d('settings', 'Site Name'),
    'site_url' => __d('settings', 'Site URL'),
    'site_logo_url' => __d('settings', 'Custom Logo URL'),
    'site_language' => __d('settings', 'System Language'),
    'status_page_title' => __d('settings', 'Status Page Title'),
    'status_page_public' => __d('settings', 'Public Status Page'),
    'status_page_cache_seconds' => __d('settings', 'Page Cache (seconds)'),
    'support_email' => __d('settings', 'Support Email'),

    // Email
    'smtp_host' => 'SMTP ' . __d('settings', 'Host'),
    'smtp_port' => 'SMTP ' . __d('settings', 'Port'),
    'smtp_username' => 'SMTP ' . __d('settings', 'Username'),
    'smtp_password' => 'SMTP ' . __d('settings', 'Password'),
    'email_from' => __d('settings', 'Sender Email'),
    'email_from_name' => __d('settings', 'Sender Name'),
    'smtp_encryption' => 'SMTP ' . __d('settings', 'Encryption'),
    'smtp_timeout' => 'SMTP ' . __d('settings', 'Timeout') . ' (' . __d('settings', 'seconds') . ')',

    // Monitoring
    'monitor_default_interval' => __d('settings', 'Default Interval (seconds)'),
    'monitor_default_timeout' => __d('settings', 'Default Timeout (seconds)'),
    'monitor_max_retries' => __d('settings', 'Max Retries'),
    'monitor_auto_resolve' => __d('settings', 'Auto-resolve Incidents'),
    'check_interval' => __d('settings', 'Check Interval (minutes)'),
    'check_timeout' => __d('settings', 'Check Timeout (seconds)'),

    // Notifications
    'notification_email_on_incident_created' => __d('settings', 'Email on Incident Created'),
    'notification_email_on_incident_resolved' => __d('settings', 'Email on Incident Resolved'),
    'notification_email_on_down' => __d('settings', 'Email on Monitor Down'),
    'notification_email_on_up' => __d('settings', 'Email on Monitor Up'),
    'alert_throttle_minutes' => __d('settings', 'Alert Throttle (minutes)'),
    'enable_email_alerts' => __d('settings', 'Enable Email Alerts'),
    'enable_whatsapp_alerts' => __d('settings', 'Enable WhatsApp Alerts'),
    'enable_telegram_alerts' => __d('settings', 'Enable Telegram Alerts'),
    'enable_sms_alerts' => __d('settings', 'Enable SMS Alerts'),

    // Backup
    'backup_ftp_enabled' => __d('settings', 'Enable FTP/SFTP Upload'),
    'backup_ftp_type' => __d('settings', 'Protocol Type'),
    'backup_ftp_host' => __d('settings', 'Server Host'),
    'backup_ftp_port' => __d('settings', 'Port'),
    'backup_ftp_username' => __d('settings', 'Username'),
    'backup_ftp_password' => __d('settings', 'Password'),
    'backup_ftp_path' => __d('settings', 'Remote Path'),
    'backup_ftp_passive' => __d('settings', 'Passive Mode (FTP)'),
];

// Description translations (help text)
$descriptions = [
    // General
    'site_name' => __d('settings', 'Site name displayed on the status page'),
    'site_url' => __d('settings', 'Full URL where the system is hosted'),
    'site_logo_url' => __d('settings', 'Full URL of logo image (PNG, JPG, SVG). Leave empty for default logo.'),
    'site_language' => __d('settings', 'System interface language'),
    'status_page_title' => __d('settings', 'Title displayed on the status page'),
    'status_page_public' => __d('settings', 'Status page is publicly accessible'),
    'status_page_cache_seconds' => __d('settings', 'Status page cache time in seconds'),
    'support_email' => __d('settings', 'Support email shown in public page footer'),

    // Email
    'smtp_host' => __d('settings', 'SMTP server address'),
    'smtp_port' => __d('settings', 'SMTP server port (usually 587 or 465)'),
    'smtp_username' => __d('settings', 'Username for SMTP authentication'),
    'smtp_password' => __d('settings', 'Password for SMTP authentication'),
    'smtp_encryption' => __d('settings', 'SMTP encryption type (TLS, SSL or none)'),
    'email_from' => __d('settings', 'Sender email address'),
    'email_from_name' => __d('settings', 'Name displayed as email sender'),
    'smtp_timeout' => __d('settings', 'SMTP connection timeout in seconds'),

    // Monitoring
    'monitor_default_interval' => __d('settings', 'Default interval between checks in seconds'),
    'monitor_default_timeout' => __d('settings', 'Default timeout for checks in seconds'),
    'monitor_max_retries' => __d('settings', 'Maximum retries before marking as failed'),
    'monitor_auto_resolve' => __d('settings', 'Automatically resolve incidents when monitor comes back online'),
    'check_interval' => __d('settings', 'Interval between check command runs in minutes'),
    'check_timeout' => __d('settings', 'Maximum execution time for a check in seconds'),

    // Notifications
    'notification_email_on_incident_created' => __d('settings', 'Send email when a new incident is created'),
    'notification_email_on_incident_resolved' => __d('settings', 'Send email when an incident is resolved'),
    'notification_email_on_down' => __d('settings', 'Send email when a monitor goes offline'),
    'notification_email_on_up' => __d('settings', 'Send email when a monitor comes back online'),
    'alert_throttle_minutes' => __d('settings', 'Minimum interval in minutes between alerts for the same monitor'),
    'enable_email_alerts' => __d('settings', 'Enable sending email alerts to subscribers'),
    'enable_whatsapp_alerts' => __d('settings', 'Enable WhatsApp alerts (future feature)'),
    'enable_telegram_alerts' => __d('settings', 'Enable Telegram alerts (future feature)'),
    'enable_sms_alerts' => __d('settings', 'Enable SMS alerts (future feature)'),

    // Backup
    'backup_ftp_enabled' => __d('settings', 'Enable automatic backup upload via FTP or SFTP'),
    'backup_ftp_type' => __d('settings', 'Transfer protocol: ftp or sftp'),
    'backup_ftp_host' => __d('settings', 'FTP/SFTP server address'),
    'backup_ftp_port' => __d('settings', 'Server port (FTP: 21, SFTP: 22)'),
    'backup_ftp_username' => __d('settings', 'Username for authentication'),
    'backup_ftp_password' => __d('settings', 'Password for authentication'),
    'backup_ftp_path' => __d('settings', 'Remote directory for storing backups'),
    'backup_ftp_passive' => __d('settings', 'Use passive mode for FTP connections'),
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
    <h2><?= __d('settings', 'System Settings') ?></h2>
</div>

<div class="tabs-container">
    <div class="tabs-nav">
        <button class="tab-button active" data-tab="general"><?= __d('settings', 'General') ?></button>
        <button class="tab-button" data-tab="email"><?= __d('settings', 'Email') ?></button>
        <button class="tab-button" data-tab="monitoring"><?= __d('settings', 'Monitoring') ?></button>
        <button class="tab-button" data-tab="notifications"><?= __d('settings', 'Notifications') ?></button>
        <button class="tab-button" data-tab="channels"><?= __d('settings', 'Channels') ?></button>
        <button class="tab-button" data-tab="backup"><?= __d('settings', 'Backup') ?></button>
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
                    <?php elseif ($setting->key === 'smtp_encryption'): ?>
                        <?= $this->Form->select("settings.{$setting->key}", [
                            '' => __d('settings', 'None'),
                            'tls' => 'TLS',
                            'ssl' => 'SSL',
                        ], [
                            'value' => strtolower($setting->getTypedValue()),
                            'class' => 'form-control',
                            'empty' => false,
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

    <!-- Email Settings -->
    <div class="tab-content" id="email">
        <?php if (count($settings['email']) > 0): ?>
            <?= $this->Form->create(null, ['url' => ['action' => 'save'], 'class' => 'settings-form']) ?>
            <?= $this->Form->hidden('category', ['value' => 'email']) ?>

            <?php foreach ($settings['email'] as $setting): ?>
                <div class="form-group">
                    <label for="<?= h($setting->key) ?>">
                        <?= h(getLabel($setting->key, $labels)) ?>
                    </label>

                    <?php if ($setting->key === 'smtp_encryption'): ?>
                        <?= $this->Form->select("settings.{$setting->key}", [
                            '' => __d('settings', 'None'),
                            'tls' => 'TLS',
                            'ssl' => 'SSL',
                        ], [
                            'value' => strtolower($setting->getTypedValue()),
                            'class' => 'form-control',
                            'empty' => false,
                        ]) ?>
                    <?php elseif (str_contains($setting->key, 'password')): ?>
                        <?= $this->Form->password("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                            'autocomplete' => 'new-password',
                        ]) ?>
                    <?php elseif ($setting->type === 'integer'): ?>
                        <?= $this->Form->number("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                        ]) ?>
                    <?php else: ?>
                        <?= $this->Form->text("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                        ]) ?>
                    <?php endif; ?>

                    <?php
                        $desc = getDescription($setting->key, $descriptions);
                        if ($desc):
                    ?>
                        <span class="help-text"><?= h($desc) ?></span>
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
                        'data' => ['category' => 'email'],
                        'confirm' => __d('settings', 'Are you sure you want to restore settings to default values?')
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>

            <!-- Test Email Form -->
            <div class="test-email-section" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <h4 style="margin-bottom: 15px;"><?= __d('settings', 'Test Email Settings') ?></h4>
                <?= $this->Form->create(null, ['url' => ['action' => 'testEmail']]) ?>
                    <div class="form-group">
                        <label for="test_email"><?= __d('settings', 'Destination Email') ?></label>
                        <?= $this->Form->email('test_email', [
                            'class' => 'form-control',
                            'placeholder' => __d('settings', 'Enter email to receive the test'),
                            'required' => true,
                            'value' => $this->Identity->get('email') ?? ''
                        ]) ?>
                        <span class="help-text"><?= __d('settings', 'Enter the email address where you want to receive the test email.') ?></span>
                    </div>
                    <div class="form-actions">
                        <?= $this->Form->button(__d('settings', 'Send Test Email'), [
                            'type' => 'submit',
                            'class' => 'btn btn-secondary'
                        ]) ?>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        <?php else: ?>
            <div class="empty-category">
                <p><?= __d('settings', 'No email settings available.') ?></p>
                <p style="font-size: 13px; margin-top: 8px;"><?= __d('settings', 'Configure email settings to send notifications.') ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Monitoring Settings -->
    <div class="tab-content" id="monitoring">
        <?php if (count($settings['monitoring']) > 0): ?>
            <?= $this->Form->create(null, ['url' => ['action' => 'save'], 'class' => 'settings-form']) ?>
            <?= $this->Form->hidden('category', ['value' => 'monitoring']) ?>

            <?php foreach ($settings['monitoring'] as $setting): ?>
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
                        'data' => ['category' => 'monitoring'],
                        'confirm' => __d('settings', 'Are you sure you want to restore settings to default values?')
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>
        <?php else: ?>
            <div class="empty-category">
                <p><?= __d('settings', 'No monitoring settings available.') ?></p>
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
                    <span class="badge badge-success"><?= __d('settings', 'Configured via SMTP') ?></span>
                </div>
                <div class="channel-body">
                    <p style="color: #666; font-size: 13px;">
                        <?= __d('settings', 'Email notifications are configured through the Email tab. SMTP settings are already available.') ?>
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

    <!-- Backup Settings -->
    <div class="tab-content" id="backup">
        <?php if (isset($settings['backup']) && count($settings['backup']) > 0): ?>
            <?= $this->Form->create(null, ['url' => ['action' => 'save'], 'class' => 'settings-form']) ?>
            <?= $this->Form->hidden('category', ['value' => 'backup']) ?>

            <?php foreach ($settings['backup'] as $setting): ?>
                <div class="form-group">
                    <label for="<?= h($setting->key) ?>">
                        <?= h(getLabel($setting->key, $labels)) ?>
                    </label>

                    <?php if ($setting->key === 'backup_ftp_type'): ?>
                        <?= $this->Form->select("settings.{$setting->key}", [
                            'ftp' => 'FTP',
                            'sftp' => 'SFTP',
                        ], [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                            'empty' => false,
                        ]) ?>
                    <?php elseif (str_contains($setting->key, 'password')): ?>
                        <?= $this->Form->password("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                            'autocomplete' => 'new-password',
                        ]) ?>
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
                    <?php else: ?>
                        <?= $this->Form->text("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                        ]) ?>
                    <?php endif; ?>

                    <?php
                        $desc = getDescription($setting->key, $descriptions);
                        if ($desc && $setting->type !== 'boolean'):
                    ?>
                        <span class="help-text"><?= h($desc) ?></span>
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
                        'data' => ['category' => 'backup'],
                        'confirm' => __d('settings', 'Are you sure you want to restore settings to default values?')
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>

            <!-- Test FTP Connection -->
            <div class="test-ftp-section" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <h4 style="margin-bottom: 15px;"><?= __d('settings', 'Test FTP/SFTP Connection') ?></h4>
                <?= $this->Form->create(null, ['url' => ['action' => 'testFtpConnection']]) ?>
                    <div class="form-actions">
                        <?= $this->Form->button(__d('settings', 'Test Connection'), [
                            'type' => 'submit',
                            'class' => 'btn btn-secondary'
                        ]) ?>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        <?php else: ?>
            <div class="empty-category">
                <p><?= __d('settings', 'No backup settings available.') ?></p>
                <p style="font-size: 13px; margin-top: 8px;"><?= __d('settings', 'Run migrations to add FTP/SFTP backup settings.') ?></p>
            </div>
        <?php endif; ?>
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
