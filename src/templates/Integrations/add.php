<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Integration $integration
 */
$this->assign('title', __('Nova Integracao'));
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
        background: #22c55e;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
    }

    .btn-submit:hover {
        background: #16a34a;
    }

    .config-section {
        background: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 20px;
        margin-top: 16px;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header">
    <div>
        <h2><?= __('Nova Integracao') ?></h2>
        <p style="color: #666;"><?= __('Configure uma nova integracao com sistema externo') ?></p>
    </div>
    <?= $this->Html->link(
        __('Voltar'),
        ['action' => 'index'],
        ['class' => 'btn btn-secondary']
    ) ?>
</div>

<div class="card">
    <?= $this->Form->create($integration) ?>

    <div class="form-section">
        <h3 class="form-section-title"><?= __('Informacoes Basicas') ?></h3>

        <div class="form-group">
            <label>
                <?= __('Nome') ?> *
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_name')]) ?>
            </label>
            <?= $this->Form->text('name', [
                'placeholder' => __('Ex: API de Monitoramento'),
                'required' => true,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <?= __('Tipo') ?> *
                    <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_type')]) ?>
                </label>
                <?= $this->Form->select('type', [
                    'ixc' => 'IXC Soft',
                    'zabbix' => 'Zabbix',
                    'rest_api' => 'REST API',
                ], [
                    'required' => true,
                    'class' => 'form-control',
                    'empty' => __('Selecione...'),
                    'id' => 'integration-type',
                ]) ?>
            </div>

            <div class="form-group">
                <label>
                    <?= __('Status') ?>
                    <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_active')]) ?>
                </label>
                <?= $this->Form->select('active', [
                    '1' => __('Ativa'),
                    '0' => __('Inativa'),
                ], [
                    'class' => 'form-control',
                    'default' => '1',
                ]) ?>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h3 class="form-section-title"><?= __('Configuracao de Conexao') ?></h3>

        <div class="config-section">
            <div class="form-group">
                <label>
                    <?= __('URL Base') ?> *
                    <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_url')]) ?>
                </label>
                <?= $this->Form->text('config_base_url', [
                    'placeholder' => 'https://api.example.com',
                    'required' => true,
                    'class' => 'form-control',
                ]) ?>
                <div class="form-help"><?= __('URL completa da API externa') ?></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?= __('Metodo HTTP') ?></label>
                    <?= $this->Form->select('config_method', [
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'DELETE' => 'DELETE',
                    ], [
                        'class' => 'form-control',
                        'default' => 'GET',
                    ]) ?>
                </div>

                <div class="form-group">
                    <label>
                        <?= __('Timeout (segundos)') ?>
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_sync_interval')]) ?>
                    </label>
                    <?= $this->Form->number('config_timeout', [
                        'placeholder' => '30',
                        'class' => 'form-control',
                        'min' => 1,
                        'max' => 120,
                        'value' => 30,
                    ]) ?>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <?= __('Tipo de Autenticacao') ?>
                </label>
                <?= $this->Form->select('config_auth_type', [
                    'none' => __('Nenhuma'),
                    'bearer' => 'Bearer Token',
                    'basic' => __('Basic Auth'),
                    'api_key' => 'API Key',
                ], [
                    'class' => 'form-control',
                    'default' => 'none',
                    'id' => 'auth-type',
                ]) ?>
            </div>

            <div id="auth-bearer" style="display:none;">
                <div class="form-group">
                    <label>
                        <?= __('Token / API Key') ?>
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_api_key')]) ?>
                    </label>
                    <?= $this->Form->text('config_api_key', [
                        'placeholder' => __('Seu token de autenticacao'),
                        'class' => 'form-control',
                        'type' => 'password',
                    ]) ?>
                </div>
            </div>

            <div id="auth-basic" style="display:none;">
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <?= __('Usuario') ?>
                            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_username')]) ?>
                        </label>
                        <?= $this->Form->text('config_username', [
                            'placeholder' => __('Usuario'),
                            'class' => 'form-control',
                        ]) ?>
                    </div>
                    <div class="form-group">
                        <label>
                            <?= __('Senha') ?>
                            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.integration_password')]) ?>
                        </label>
                        <?= $this->Form->text('config_password', [
                            'placeholder' => __('Senha'),
                            'class' => 'form-control',
                            'type' => 'password',
                        ]) ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label><?= __('Headers Customizados (JSON)') ?></label>
                <?= $this->Form->textarea('config_headers', [
                    'placeholder' => '{"Content-Type": "application/json"}',
                    'class' => 'form-control',
                    'rows' => 3,
                ]) ?>
                <div class="form-help"><?= __('Formato JSON. Exemplo: {"Authorization": "Custom value"}') ?></div>
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 8px; margin-top: 24px;">
        <?= $this->Form->button(__('Salvar'), ['type' => 'submit', 'class' => 'btn-submit']) ?>
        <?= $this->Html->link(__('Cancelar'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
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
