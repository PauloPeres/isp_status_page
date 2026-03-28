<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Integration $integration
 */
$this->assign('title', __('Edit Integration'));

$config = $integration->getConfiguration();
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        border: none;
        cursor: pointer;
        display: inline-block;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .form-section {
        margin-bottom: 24px;
    }

    .form-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e0e0e0;
    }

    .form-group {
        margin-bottom: 16px;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #444;
        margin-bottom: 6px;
    }

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        font-size: 14px;
        background: white;
        box-sizing: border-box;
    }

    .form-control:focus {
        border-color: #3b82f6;
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-help {
        font-size: 13px;
        color: #666;
        margin-top: 4px;
    }

    .btn-submit {
        padding: 10px 24px;
        background: #f59e0b;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
    }

    .btn-submit:hover {
        background: #d97706;
    }

    .config-section {
        background: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 20px;
        margin-top: 16px;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .form-control,
        .form-group input,
        .form-group select,
        .form-group textarea {
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
    }
</style>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('Integrations'), 'url' => $this->Url->build(['controller' => 'Integrations', 'action' => 'index'])],
    ['title' => __('Edit'), 'url' => null],
]]) ?>

<div class="page-header">
    <div>
        <h2><?= __('Edit Integration') ?></h2>
        <p style="color: #666;"><?= h($integration->name) ?></p>
    </div>
    <?= $this->Html->link(
        __('Back'),
        ['action' => 'index'],
        ['class' => 'btn btn-secondary']
    ) ?>
</div>

<div class="card">
    <?= $this->Form->create($integration) ?>

    <div class="form-section">
        <h3 class="form-section-title"><?= __('Basic Information') ?></h3>

        <div class="form-group">
            <label>
                <?= __('Name') ?> *
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_name')]) ?>
            </label>
            <?= $this->Form->text('name', [
                'required' => true,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <?= __('Type') ?> *
                    <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_type')]) ?>
                </label>
                <?= $this->Form->select('type', [
                    'ixc' => 'IXC Soft',
                    'zabbix' => 'Zabbix',
                    'rest_api' => 'REST API',
                ], [
                    'required' => true,
                    'class' => 'form-control',
                    'id' => 'integration-type',
                ]) ?>
            </div>

            <div class="form-group">
                <label>
                    <?= __('Status') ?>
                    <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_active')]) ?>
                </label>
                <?= $this->Form->select('active', [
                    '1' => __('Active'),
                    '0' => __('Inactive'),
                ], [
                    'class' => 'form-control',
                ]) ?>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section-title"><?= __('Connection Settings') ?></h3>

        <div class="config-section">
            <div class="form-group">
                <label>
                    <?= __('Base URL') ?> *
                    <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_url')]) ?>
                </label>
                <?= $this->Form->text('config_base_url', [
                    'placeholder' => 'https://api.example.com',
                    'required' => true,
                    'class' => 'form-control',
                    'value' => $config['base_url'] ?? '',
                ]) ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?= __('HTTP Method') ?></label>
                    <?= $this->Form->select('config_method', [
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'DELETE' => 'DELETE',
                    ], [
                        'class' => 'form-control',
                        'value' => $config['method'] ?? 'GET',
                    ]) ?>
                </div>

                <div class="form-group">
                    <label>
                        <?= __('Timeout (seconds)') ?>
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_sync_interval')]) ?>
                    </label>
                    <?= $this->Form->number('config_timeout', [
                        'class' => 'form-control',
                        'min' => 1,
                        'max' => 120,
                        'value' => $config['timeout'] ?? 30,
                    ]) ?>
                </div>
            </div>

            <div class="form-group">
                <label><?= __('Authentication Type') ?></label>
                <?= $this->Form->select('config_auth_type', [
                    'none' => __('None'),
                    'bearer' => 'Bearer Token',
                    'basic' => __('Basic Auth'),
                    'api_key' => 'API Key',
                ], [
                    'class' => 'form-control',
                    'value' => $config['auth_type'] ?? 'none',
                    'id' => 'auth-type',
                ]) ?>
            </div>

            <div id="auth-bearer" style="display:<?= in_array($config['auth_type'] ?? '', ['bearer', 'api_key']) ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label>
                        <?= __('Token / API Key') ?>
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_api_key')]) ?>
                    </label>
                    <?= $this->Form->text('config_api_key', [
                        'placeholder' => __('Your authentication token'),
                        'class' => 'form-control',
                        'type' => 'password',
                        'value' => $config['api_key'] ?? '',
                    ]) ?>
                </div>
            </div>

            <div id="auth-basic" style="display:<?= ($config['auth_type'] ?? '') === 'basic' ? 'block' : 'none' ?>;">
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <?= __('Username') ?>
                            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_username')]) ?>
                        </label>
                        <?= $this->Form->text('config_username', [
                            'class' => 'form-control',
                            'value' => $config['username'] ?? '',
                        ]) ?>
                    </div>
                    <div class="form-group">
                        <label>
                            <?= __('Password') ?>
                            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_password')]) ?>
                        </label>
                        <?= $this->Form->text('config_password', [
                            'class' => 'form-control',
                            'type' => 'password',
                            'value' => $config['password'] ?? '',
                        ]) ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label><?= __('Custom Headers (JSON)') ?></label>
                <?= $this->Form->textarea('config_headers', [
                    'placeholder' => '{"Content-Type": "application/json"}',
                    'class' => 'form-control',
                    'rows' => 3,
                    'value' => isset($config['headers']) ? (is_array($config['headers']) ? json_encode($config['headers'], JSON_PRETTY_PRINT) : $config['headers']) : '',
                ]) ?>
                <div class="form-help"><?= __('JSON format. Example: {"Authorization": "Custom value"}') ?></div>
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 8px; margin-top: 24px;">
        <?= $this->Form->button(__('Save Changes'), ['type' => 'submit', 'class' => 'btn-submit']) ?>
        <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>

<script>
document.getElementById('auth-type')?.addEventListener('change', function() {
    document.getElementById('auth-bearer').style.display = 'none';
    document.getElementById('auth-basic').style.display = 'none';

    switch (this.value) {
        case 'bearer':
        case 'api_key':
            document.getElementById('auth-bearer').style.display = 'block';
            break;
        case 'basic':
            document.getElementById('auth-basic').style.display = 'block';
            break;
    }
});
</script>
