<?php
/**
 * Super Admin - System Settings
 *
 * @var \App\View\AppView $this
 * @var array $settings
 * @var string $tab
 */
$this->assign('title', __('System Settings'));
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
        font-weight: 400;
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

    .section-divider {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e0e0e0;
    }

    .section-divider h4 {
        margin-bottom: 15px;
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
    <h2><?= __('System Settings') ?></h2>
    <p><?= __('Platform-wide configuration managed by super administrators. These settings apply to all organizations.') ?></p>
</div>

<div class="tabs-container">
    <div class="tabs-nav">
        <button class="tab-button<?= $tab === 'email' ? ' active' : '' ?>" data-tab="email"><?= __('Email') ?></button>
        <button class="tab-button<?= $tab === 'backup' ? ' active' : '' ?>" data-tab="backup"><?= __('Backup') ?></button>
        <button class="tab-button<?= $tab === 'system' ? ' active' : '' ?>" data-tab="system"><?= __('System') ?></button>
    </div>

    <!-- Email / SMTP Settings -->
    <div class="tab-content<?= $tab === 'email' ? ' active' : '' ?>" id="email">
        <div class="settings-form">
            <?= $this->Form->create(null, ['url' => ['action' => 'save']]) ?>
            <?= $this->Form->hidden('_tab', ['value' => 'email']) ?>

            <div class="form-group">
                <label for="smtp_host">SMTP <?= __('Host') ?></label>
                <input type="text" id="smtp_host" name="smtp_host"
                       value="<?= h($settings['smtp_host']) ?>"
                       placeholder="smtp.example.com">
                <span class="help-text"><?= __('SMTP server address (e.g., smtp.gmail.com, smtp.sendgrid.net)') ?></span>
            </div>

            <div class="form-group">
                <label for="smtp_port">SMTP <?= __('Port') ?></label>
                <input type="number" id="smtp_port" name="smtp_port"
                       value="<?= h($settings['smtp_port']) ?>"
                       placeholder="587" min="1" max="65535">
                <span class="help-text"><?= __('SMTP server port (usually 587 for TLS, 465 for SSL, 25 for unencrypted)') ?></span>
            </div>

            <div class="form-group">
                <label for="smtp_username">SMTP <?= __('Username') ?></label>
                <input type="text" id="smtp_username" name="smtp_username"
                       value="<?= h($settings['smtp_username']) ?>"
                       placeholder="user@example.com">
                <span class="help-text"><?= __('Username for SMTP authentication') ?></span>
            </div>

            <div class="form-group">
                <label for="smtp_password">SMTP <?= __('Password') ?></label>
                <input type="password" id="smtp_password" name="smtp_password"
                       value="<?= h($settings['smtp_password']) ?>"
                       autocomplete="new-password">
                <span class="help-text"><?= __('Password or app-specific password for SMTP authentication') ?></span>
            </div>

            <div class="form-group">
                <label for="smtp_encryption">SMTP <?= __('Encryption') ?></label>
                <select id="smtp_encryption" name="smtp_encryption">
                    <option value="tls" <?= ($settings['smtp_encryption'] === 'tls') ? 'selected' : '' ?>>TLS</option>
                    <option value="ssl" <?= ($settings['smtp_encryption'] === 'ssl') ? 'selected' : '' ?>>SSL</option>
                    <option value="" <?= (empty($settings['smtp_encryption'])) ? 'selected' : '' ?>><?= __('None') ?></option>
                </select>
                <span class="help-text"><?= __('Encryption type for SMTP connection (TLS recommended)') ?></span>
            </div>

            <div class="form-group">
                <label for="email_from_name"><?= __('Sender Name') ?></label>
                <input type="text" id="email_from_name" name="email_from_name"
                       value="<?= h($settings['email_from_name']) ?>"
                       placeholder="ISP Status">
                <span class="help-text"><?= __('Name displayed as the email sender') ?></span>
            </div>

            <div class="form-group">
                <label for="email_from_address"><?= __('Sender Email') ?></label>
                <input type="email" id="email_from_address" name="email_from_address"
                       value="<?= h($settings['email_from_address']) ?>"
                       placeholder="noreply@example.com">
                <span class="help-text"><?= __('Email address used as the sender for all platform emails') ?></span>
            </div>

            <div class="form-actions">
                <?= $this->Form->button(__('Save Email Settings'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary',
                ]) ?>
            </div>

            <?= $this->Form->end() ?>

            <!-- Test Email Section -->
            <div class="section-divider">
                <h4><?= __('Test Email Settings') ?></h4>
                <?= $this->Form->create(null, ['url' => ['action' => 'testEmail']]) ?>
                    <div class="form-group">
                        <label for="test_email"><?= __('Destination Email') ?></label>
                        <input type="email" id="test_email" name="test_email"
                               placeholder="<?= __('Enter email to receive the test') ?>"
                               required>
                        <span class="help-text"><?= __('A test email will be sent using the saved SMTP settings above.') ?></span>
                    </div>
                    <div class="form-actions">
                        <?= $this->Form->button(__('Send Test Email'), [
                            'type' => 'submit',
                            'class' => 'btn btn-secondary',
                        ]) ?>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>

    <!-- Backup / FTP Settings -->
    <div class="tab-content<?= $tab === 'backup' ? ' active' : '' ?>" id="backup">
        <div class="settings-form">
            <?= $this->Form->create(null, ['url' => ['action' => 'save']]) ?>
            <?= $this->Form->hidden('_tab', ['value' => 'backup']) ?>

            <div class="form-group">
                <div class="checkbox-label">
                    <input type="hidden" name="backup_ftp_enabled" value="0">
                    <input type="checkbox" id="backup_ftp_enabled" name="backup_ftp_enabled" value="1"
                           <?= filter_var($settings['backup_ftp_enabled'], FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' ?>>
                    <label for="backup_ftp_enabled"><?= __('Enable automatic backup upload via FTP/SFTP') ?></label>
                </div>
            </div>

            <div class="form-group">
                <label for="backup_ftp_type"><?= __('Protocol Type') ?></label>
                <select id="backup_ftp_type" name="backup_ftp_type">
                    <option value="ftp" <?= ($settings['backup_ftp_type'] === 'ftp') ? 'selected' : '' ?>>FTP</option>
                    <option value="sftp" <?= ($settings['backup_ftp_type'] === 'sftp') ? 'selected' : '' ?>>SFTP</option>
                </select>
                <span class="help-text"><?= __('Transfer protocol: FTP (port 21) or SFTP (port 22)') ?></span>
            </div>

            <div class="form-group">
                <label for="backup_ftp_host"><?= __('Server Host') ?></label>
                <input type="text" id="backup_ftp_host" name="backup_ftp_host"
                       value="<?= h($settings['backup_ftp_host']) ?>"
                       placeholder="ftp.example.com">
                <span class="help-text"><?= __('FTP/SFTP server address') ?></span>
            </div>

            <div class="form-group">
                <label for="backup_ftp_port"><?= __('Port') ?></label>
                <input type="number" id="backup_ftp_port" name="backup_ftp_port"
                       value="<?= h($settings['backup_ftp_port']) ?>"
                       placeholder="21" min="1" max="65535">
                <span class="help-text"><?= __('Server port (FTP: 21, SFTP: 22)') ?></span>
            </div>

            <div class="form-group">
                <label for="backup_ftp_username"><?= __('Username') ?></label>
                <input type="text" id="backup_ftp_username" name="backup_ftp_username"
                       value="<?= h($settings['backup_ftp_username']) ?>"
                       placeholder="backup_user">
                <span class="help-text"><?= __('Username for FTP/SFTP authentication') ?></span>
            </div>

            <div class="form-group">
                <label for="backup_ftp_password"><?= __('Password') ?></label>
                <input type="password" id="backup_ftp_password" name="backup_ftp_password"
                       value="<?= h($settings['backup_ftp_password']) ?>"
                       autocomplete="new-password">
                <span class="help-text"><?= __('Password for FTP/SFTP authentication') ?></span>
            </div>

            <div class="form-group">
                <label for="backup_ftp_path"><?= __('Remote Path') ?></label>
                <input type="text" id="backup_ftp_path" name="backup_ftp_path"
                       value="<?= h($settings['backup_ftp_path']) ?>"
                       placeholder="/backups">
                <span class="help-text"><?= __('Remote directory for storing backups') ?></span>
            </div>

            <div class="form-actions">
                <?= $this->Form->button(__('Save Backup Settings'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary',
                ]) ?>
            </div>

            <?= $this->Form->end() ?>

            <!-- Test FTP Connection -->
            <div class="section-divider">
                <h4><?= __('Test FTP/SFTP Connection') ?></h4>
                <?= $this->Form->create(null, ['url' => ['action' => 'testFtp']]) ?>
                    <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                        <?= __('Tests the connection using the currently saved backup settings above.') ?>
                    </p>
                    <div class="form-actions">
                        <?= $this->Form->button(__('Test Connection'), [
                            'type' => 'submit',
                            'class' => 'btn btn-secondary',
                        ]) ?>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>

    <!-- System Settings -->
    <div class="tab-content<?= $tab === 'system' ? ' active' : '' ?>" id="system">
        <div class="settings-form">
            <?= $this->Form->create(null, ['url' => ['action' => 'save']]) ?>
            <?= $this->Form->hidden('_tab', ['value' => 'system']) ?>

            <div class="form-group">
                <label for="site_name"><?= __('Site Name') ?></label>
                <input type="text" id="site_name" name="site_name"
                       value="<?= h($settings['site_name']) ?>"
                       placeholder="ISP Status Page">
                <span class="help-text"><?= __('Platform name displayed across the application') ?></span>
            </div>

            <div class="form-group">
                <label for="default_language"><?= __('Default Language') ?></label>
                <select id="default_language" name="default_language">
                    <option value="en" <?= ($settings['default_language'] === 'en') ? 'selected' : '' ?>>English</option>
                    <option value="pt_BR" <?= ($settings['default_language'] === 'pt_BR') ? 'selected' : '' ?>>Portuguese (Brazil)</option>
                    <option value="es" <?= ($settings['default_language'] === 'es') ? 'selected' : '' ?>>Espanol</option>
                </select>
                <span class="help-text"><?= __('Default system language for new organizations') ?></span>
            </div>

            <div class="form-group">
                <label for="system_announcement"><?= __('System Announcement') ?></label>
                <textarea id="system_announcement" name="system_announcement" rows="4"
                          placeholder="<?= __('Optional announcement displayed to all users...') ?>"><?= h($settings['system_announcement']) ?></textarea>
                <span class="help-text"><?= __('A message shown to all users across the platform (leave empty to hide)') ?></span>
            </div>

            <div class="form-actions">
                <?= $this->Form->button(__('Save System Settings'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary',
                ]) ?>
            </div>

            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var tabButtons = document.querySelectorAll('.tab-button');
    var tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var tabName = this.dataset.tab;

            tabButtons.forEach(function(btn) { btn.classList.remove('active'); });
            tabContents.forEach(function(content) { content.classList.remove('active'); });

            this.classList.add('active');
            var activeContent = document.getElementById(tabName);
            if (activeContent) {
                activeContent.classList.add('active');
            }

            // Update URL without reload
            var url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        });
    });
});
</script>
