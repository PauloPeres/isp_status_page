<?php
/**
 * @var \App\View\AppView $this
 * @var array $settings
 */
$this->assign('title', 'Configurações do Sistema');

// Traduções dos labels das configurações
$labels = [
    // General
    'site_name' => 'Nome do Site',
    'site_url' => 'URL do Site',
    'status_page_title' => 'Título da Página de Status',
    'status_page_public' => 'Página de Status Pública',
    'status_page_cache_seconds' => 'Cache da Página (segundos)',

    // Email
    'smtp_host' => 'Servidor SMTP',
    'smtp_port' => 'Porta SMTP',
    'smtp_username' => 'Usuário SMTP',
    'smtp_password' => 'Senha SMTP',
    'email_from' => 'Email Remetente',
    'email_from_name' => 'Nome do Remetente',
    'smtp_encryption' => 'Criptografia SMTP',
    'smtp_timeout' => 'Timeout SMTP (segundos)',

    // Monitoring
    'monitor_default_interval' => 'Intervalo Padrão (segundos)',
    'monitor_default_timeout' => 'Timeout Padrão (segundos)',
    'monitor_max_retries' => 'Máximo de Tentativas',
    'monitor_auto_resolve' => 'Auto-resolver Incidentes',
    'check_interval' => 'Intervalo de Verificação (minutos)',
    'check_timeout' => 'Timeout de Verificação (segundos)',

    // Notifications
    'notification_email_on_incident_created' => 'Email ao Criar Incidente',
    'notification_email_on_incident_resolved' => 'Email ao Resolver Incidente',
    'notification_email_on_down' => 'Email ao Ficar Offline',
    'notification_email_on_up' => 'Email ao Voltar Online',
    'alert_throttle_minutes' => 'Intervalo Entre Alertas (minutos)',
];

/**
 * Get translated label for setting key
 */
function getLabel($key, $labels) {
    return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
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
        }

        .form-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }
    }
</style>

<div class="settings-header">
    <h2>Configurações do Sistema</h2>
</div>

<div class="tabs-container">
    <div class="tabs-nav">
        <button class="tab-button active" data-tab="general">Geral</button>
        <button class="tab-button" data-tab="email">Email</button>
        <button class="tab-button" data-tab="monitoring">Monitoramento</button>
        <button class="tab-button" data-tab="notifications">Notificações</button>
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

                    <?php if ($setting->type === 'boolean'): ?>
                        <div class="checkbox-label">
                            <?= $this->Form->checkbox("settings.{$setting->key}", [
                                'checked' => $setting->getTypedValue(),
                                'hiddenField' => false,
                            ]) ?>
                            <span><?= h($setting->description ?: 'Ativar esta opção') ?></span>
                        </div>
                    <?php elseif ($setting->type === 'integer'): ?>
                        <?= $this->Form->number("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                        ]) ?>
                        <?php if ($setting->description): ?>
                            <span class="help-text"><?= h($setting->description) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= $this->Form->text("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                        ]) ?>
                        <?php if ($setting->description): ?>
                            <span class="help-text"><?= h($setting->description) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <?= $this->Form->button('Salvar Configurações', [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
                <?= $this->Form->postLink(
                    'Restaurar Padrões',
                    ['action' => 'reset'],
                    [
                        'class' => 'btn btn-warning',
                        'data' => ['category' => 'general'],
                        'confirm' => 'Tem certeza que deseja restaurar as configurações para os valores padrão?'
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>
        <?php else: ?>
            <div class="empty-category">
                <p>Nenhuma configuração geral disponível.</p>
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

                    <?php if (str_contains($setting->key, 'password')): ?>
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

                    <?php if ($setting->description): ?>
                        <span class="help-text"><?= h($setting->description) ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <?= $this->Form->button('Salvar Configurações', [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
                <?= $this->Form->postLink(
                    'Testar Email',
                    ['action' => 'testEmail'],
                    [
                        'class' => 'btn btn-secondary',
                        'confirm' => 'Enviar email de teste para verificar as configurações?'
                    ]
                ) ?>
                <?= $this->Form->postLink(
                    'Restaurar Padrões',
                    ['action' => 'reset'],
                    [
                        'class' => 'btn btn-warning',
                        'data' => ['category' => 'email'],
                        'confirm' => 'Tem certeza que deseja restaurar as configurações para os valores padrão?'
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>
        <?php else: ?>
            <div class="empty-category">
                <p>Nenhuma configuração de email disponível.</p>
                <p style="font-size: 13px; margin-top: 8px;">Configure as configurações de email para enviar notificações.</p>
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
                                'hiddenField' => false,
                            ]) ?>
                            <span><?= h($setting->description ?: 'Ativar esta opção') ?></span>
                        </div>
                    <?php elseif ($setting->type === 'integer'): ?>
                        <?= $this->Form->number("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                        ]) ?>
                        <?php if ($setting->description): ?>
                            <span class="help-text"><?= h($setting->description) ?></span>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= $this->Form->text("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                        ]) ?>
                        <?php if ($setting->description): ?>
                            <span class="help-text"><?= h($setting->description) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <?= $this->Form->button('Salvar Configurações', [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
                <?= $this->Form->postLink(
                    'Restaurar Padrões',
                    ['action' => 'reset'],
                    [
                        'class' => 'btn btn-warning',
                        'data' => ['category' => 'monitoring'],
                        'confirm' => 'Tem certeza que deseja restaurar as configurações para os valores padrão?'
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>
        <?php else: ?>
            <div class="empty-category">
                <p>Nenhuma configuração de monitoramento disponível.</p>
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
                                'hiddenField' => false,
                            ]) ?>
                            <span><?= h($setting->description ?: 'Ativar esta opção') ?></span>
                        </div>
                    <?php else: ?>
                        <?= $this->Form->textarea("settings.{$setting->key}", [
                            'value' => $setting->getTypedValue(),
                            'class' => 'form-control',
                            'rows' => 4,
                        ]) ?>
                        <?php if ($setting->description): ?>
                            <span class="help-text"><?= h($setting->description) ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <?= $this->Form->button('Salvar Configurações', [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
                <?= $this->Form->postLink(
                    'Restaurar Padrões',
                    ['action' => 'reset'],
                    [
                        'class' => 'btn btn-warning',
                        'data' => ['category' => 'notifications'],
                        'confirm' => 'Tem certeza que deseja restaurar as configurações para os valores padrão?'
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>
        <?php else: ?>
            <div class="empty-category">
                <p>Nenhuma configuração de notificações disponível.</p>
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
</script>
