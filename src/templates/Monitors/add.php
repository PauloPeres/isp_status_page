<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */
$this->assign('title', __d('monitors', 'New Monitor'));
?>

<div class="monitors-form">
    <div class="page-header">
        <div>
            <h1>➕ <?= __d('monitors', 'New Monitor') ?></h1>
            <p><?= __d('monitors', 'Configure a new service for monitoring') ?></p>
        </div>
        <?= $this->Html->link(
            '← ' . __('Back'),
            ['action' => 'index'],
            ['class' => 'btn btn-secondary']
        ) ?>
    </div>

    <!-- Mode Toggle -->
    <div class="setup-mode-toggle">
        <button type="button" id="btn-quick-setup" class="mode-btn mode-btn-active" onclick="switchMode('quick')">
            Quick Setup
        </button>
        <button type="button" id="btn-advanced" class="mode-btn" onclick="switchMode('advanced')">
            Advanced
        </button>
    </div>

    <!-- Quick Setup Mode -->
    <div id="quick-setup-panel" class="card">
        <?= $this->Form->create($monitor, ['id' => 'quick-setup-form']) ?>

        <div class="quick-setup-intro">
            <h3><?= __d('monitors', 'What do you want to monitor?') ?></h3>
            <p class="form-help"><?= __d('monitors', 'Enter a URL, IP address, or hostname and we will figure out the rest.') ?></p>
        </div>

        <div class="form-group">
            <label for="quick-url"><?= __d('monitors', 'URL or Address') ?> *</label>
            <input type="text" id="quick-url" class="form-control form-control-lg" placeholder="https://example.com" required>
            <small class="form-help" id="quick-url-help"><?= __d('monitors', 'e.g. https://example.com, 192.168.1.1, mail.example.com:587') ?></small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="quick-name"><?= __d('monitors', 'Name') ?></label>
                <?= $this->Form->text('name', [
                    'id' => 'quick-name',
                    'placeholder' => __d('monitors', 'Auto-filled from URL'),
                    'required' => true,
                    'class' => 'form-control',
                ]) ?>
            </div>

            <div class="form-group">
                <label for="quick-interval"><?= __d('monitors', 'Check every') ?></label>
                <select id="quick-interval" class="form-control" name="check_interval">
                    <option value="60">1 minute</option>
                    <option value="300" selected>5 minutes</option>
                    <option value="900">15 minutes</option>
                </select>
            </div>
        </div>

        <!-- Hidden fields populated by JS -->
        <?= $this->Form->hidden('type', ['id' => 'quick-type', 'value' => 'http']) ?>
        <?= $this->Form->hidden('target', ['id' => 'quick-target']) ?>
        <?= $this->Form->hidden('active', ['value' => '1']) ?>
        <?= $this->Form->hidden('timeout', ['value' => '10']) ?>
        <?= $this->Form->hidden('expected_status_code', ['id' => 'quick-expected-status', 'value' => '200']) ?>

        <div class="form-group">
            <label><?= __d('monitors', 'Notify me by') ?></label>
            <div class="notify-checkboxes">
                <label class="notify-option">
                    <input type="checkbox" name="notify_email" value="1" checked>
                    <span>Email</span>
                </label>
                <label class="notify-option">
                    <input type="checkbox" name="notify_slack" value="1">
                    <span>Slack</span>
                </label>
                <label class="notify-option">
                    <input type="checkbox" name="notify_discord" value="1">
                    <span>Discord</span>
                </label>
                <label class="notify-option">
                    <input type="checkbox" name="notify_telegram" value="1">
                    <span>Telegram</span>
                </label>
                <label class="notify-option">
                    <input type="checkbox" name="notify_webhook" value="1">
                    <span>Webhook</span>
                </label>
            </div>
        </div>

        <div class="quick-type-indicator" id="quick-type-indicator" style="display: none;">
            <span class="type-badge" id="quick-type-badge">HTTP</span>
            <span class="type-label" id="quick-type-label"><?= __d('monitors', 'Auto-detected monitor type') ?></span>
        </div>

        <div class="form-actions">
            <?= $this->Form->button(__d('monitors', 'Start Monitoring'), [
                'class' => 'btn btn-success btn-lg btn-start-monitoring',
                'id' => 'quick-submit-btn',
            ]) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>

    <!-- Advanced Mode (original full form) -->
    <div id="advanced-panel" class="card" style="display: none;">
        <?= $this->Form->create($monitor, ['id' => 'advanced-form']) ?>

        <div class="form-section">
            <h3 class="form-section-title"><?= __d('monitors', 'Basic Information') ?></h3>

            <div class="form-group">
                <label>
                    <?= __d('monitors', 'Monitor Name') ?> *
                    <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.monitor_name')]) ?>
                </label>
                <?= $this->Form->text('name', [
                    'placeholder' => __d('monitors', 'e.g. Main Website'),
                    'required' => true,
                    'class' => 'form-control',
                    'id' => 'advanced-name',
                ]) ?>
            </div>

            <?= $this->Form->control('description', [
                'label' => __('Description'),
                'placeholder' => __d('monitors', 'Brief description of what is being monitored'),
                'type' => 'textarea',
                'rows' => 3,
                'class' => 'form-control',
            ]) ?>

            <div class="form-group">
                <label>
                    <?= __d('monitors', 'Tags') ?>
                </label>
                <?= $this->Form->text('tags', [
                    'placeholder' => __d('monitors', 'e.g. production, api, critical'),
                    'class' => 'form-control',
                    'value' => '',
                ]) ?>
                <small class="form-help"><?= __d('monitors', 'Comma-separated list of tags for grouping and filtering') ?></small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        <?= __d('monitors', 'Monitor Type') ?> *
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.monitor_type')]) ?>
                    </label>
                    <?= $this->Form->select('type', [
                        'http' => 'HTTP/HTTPS',
                        'ping' => 'Ping (ICMP)',
                        'port' => __d('monitors', 'Port (TCP/UDP)'),
                        'heartbeat' => __d('monitors', 'Heartbeat'),
                        'keyword' => __d('monitors', 'Keyword'),
                        'ssl' => __d('monitors', 'SSL Certificate'),
                    ], [
                        'required' => true,
                        'class' => 'form-control',
                        'id' => 'monitor-type',
                    ]) ?>
                </div>

                <div class="form-group">
                    <label>
                        <?= $this->Form->checkbox('active', ['checked' => true]) ?>
                        <?= __d('monitors', 'Active') ?>
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.active')]) ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title"><?= __d('monitors', 'Target Configuration') ?></h3>
            <p class="form-help"><?= __d('monitors', 'Configuration depends on selected monitor type') ?></p>

            <!-- HTTP/HTTPS Fields -->
            <?= $this->element('monitor/form_http', ['monitor' => $monitor]) ?>

            <!-- Ping/ICMP Fields -->
            <?= $this->element('monitor/form_ping', ['monitor' => $monitor]) ?>

            <!-- Port/TCP/UDP Fields -->
            <?= $this->element('monitor/form_port', ['monitor' => $monitor]) ?>

            <!-- Heartbeat Fields -->
            <?= $this->element('monitor/form_heartbeat', ['monitor' => $monitor]) ?>

            <!-- SSL Certificate Fields -->
            <?= $this->element('monitor/form_ssl', ['monitor' => $monitor]) ?>
        </div>

        <div class="form-section">
            <h3 class="form-section-title"><?= __d('monitors', 'Check Settings') ?></h3>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        <?= __d('monitors', 'Interval (seconds)') ?> *
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.check_interval')]) ?>
                    </label>
                    <?= $this->Form->number('check_interval', [
                        'value' => 30,
                        'min' => 10,
                        'max' => 3600,
                        'required' => true,
                        'class' => 'form-control',
                    ]) ?>
                    <small class="form-help"><?= __d('monitors', 'Check frequency (minimum 10s)') ?></small>
                </div>

                <div class="form-group">
                    <label>
                        <?= __d('monitors', 'Timeout (seconds)') ?> *
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.timeout')]) ?>
                    </label>
                    <?= $this->Form->number('timeout', [
                        'value' => 10,
                        'min' => 1,
                        'max' => 60,
                        'required' => true,
                        'class' => 'form-control',
                    ]) ?>
                    <small class="form-help"><?= __d('monitors', 'Maximum wait time') ?></small>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <?= $this->Form->button('💾 ' . __d('monitors', 'Save Monitor'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(
                __('Cancel'),
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>

<?= $this->Html->script('monitor-form', ['block' => true]) ?>

<style>
.monitors-form {
    max-width: 800px;
}

/* Mode Toggle */
.setup-mode-toggle {
    display: flex;
    background: var(--color-gray-light);
    border-radius: var(--radius-md);
    padding: 4px;
    margin-bottom: 24px;
    max-width: 300px;
}

.mode-btn {
    flex: 1;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    background: transparent;
    color: #546E7A;
    transition: all 0.2s ease;
}

.mode-btn-active {
    background: var(--color-white);
    color: var(--color-primary);
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

/* Quick Setup Styles */
.quick-setup-intro {
    margin-bottom: 24px;
}

.quick-setup-intro h3 {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-dark);
    margin-bottom: 4px;
}

.form-control-lg {
    padding: 16px;
    font-size: 17px;
}

.notify-checkboxes {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 8px;
}

.notify-option {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: var(--color-gray-light);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
    font-size: 14px;
}

.notify-option:hover {
    background: #E3F2FD;
}

.notify-option input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.notify-option input[type="checkbox"]:checked + span {
    color: var(--color-primary);
}

.quick-type-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: #E3F2FD;
    border-radius: var(--radius-md);
    margin-bottom: 20px;
}

.type-badge {
    display: inline-block;
    padding: 4px 12px;
    background: var(--color-primary);
    color: var(--color-white);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.type-label {
    font-size: 14px;
    color: #1565C0;
}

.btn-success {
    background: #43A047;
    color: #fff;
    border: none;
}

.btn-success:hover {
    background: #388E3C;
}

.btn-lg {
    padding: 16px 32px;
    font-size: 17px;
}

.btn-start-monitoring {
    width: 100%;
    box-shadow: 0 4px 12px rgba(67, 160, 71, 0.3);
}

.btn-start-monitoring:hover {
    box-shadow: 0 6px 16px rgba(67, 160, 71, 0.4);
    transform: translateY(-1px);
}

/* Advanced Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: var(--color-dark);
}

.form-help {
    display: block;
    margin-top: 6px;
    font-size: 13px;
    color: var(--color-gray-medium);
    line-height: 1.4;
}

.form-section {
    margin-bottom: 32px;
    padding-bottom: 32px;
    border-bottom: 1px solid var(--color-gray-light);
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-dark);
    margin-bottom: 16px;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--color-gray-light);
    border-radius: var(--radius-md);
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
}

.form-control[type="checkbox"] {
    width: auto;
    margin-top: 8px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-actions {
    display: flex;
    gap: 16px;
    padding-top: 24px;
}

.monitor-type-fields {
    margin-top: 16px;
    padding: 16px;
    background: var(--color-gray-light);
    border-radius: var(--radius-md);
}

@media (max-width: 768px) {
    .monitors-form {
        max-width: 100%;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .form-control {
        font-size: 16px;
        min-height: 44px;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
        min-height: 44px;
    }

    .form-section {
        margin-bottom: 20px;
        padding-bottom: 20px;
    }

    .setup-mode-toggle {
        max-width: 100%;
    }

    .notify-checkboxes {
        flex-direction: column;
    }
}
</style>
