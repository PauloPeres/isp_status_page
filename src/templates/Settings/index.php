<?php
/**
 * @var \App\View\AppView $this
 * @var array $settings
 */
$this->assign('title', __d('settings', 'Configurações do Sistema'));

// Traduções dos labels das configurações
$labels = [
    // General
    'site_name' => __d('settings', 'Nome do Site'),
    'site_url' => __d('settings', 'URL do Site'),
    'site_logo_url' => __d('settings', 'URL da Logo Personalizada'),
    'site_language' => __d('settings', 'Idioma do Sistema'),
    'status_page_title' => __d('settings', 'Título da Página de Status'),
    'status_page_public' => __d('settings', 'Página de Status Pública'),
    'status_page_cache_seconds' => __d('settings', 'Cache da Página (segundos)'),
    'support_email' => __d('settings', 'Email de Suporte'),

    // Email
    'smtp_host' => 'SMTP ' . __d('settings', 'Host'),
    'smtp_port' => 'SMTP ' . __d('settings', 'Port'),
    'smtp_username' => 'SMTP ' . __d('settings', 'Username'),
    'smtp_password' => 'SMTP ' . __d('settings', 'Password'),
    'email_from' => __d('settings', 'Email Remetente'),
    'email_from_name' => __d('settings', 'Nome do Remetente'),
    'smtp_encryption' => 'SMTP ' . __d('settings', 'Encryption'),
    'smtp_timeout' => 'SMTP ' . __d('settings', 'Timeout') . ' (' . __d('settings', 'segundos') . ')',

    // Monitoring
    'monitor_default_interval' => __d('settings', 'Intervalo Padrão (segundos)'),
    'monitor_default_timeout' => __d('settings', 'Timeout Padrão (segundos)'),
    'monitor_max_retries' => __d('settings', 'Máximo de Tentativas'),
    'monitor_auto_resolve' => __d('settings', 'Auto-resolver Incidentes'),
    'check_interval' => __d('settings', 'Intervalo de Verificação (minutos)'),
    'check_timeout' => __d('settings', 'Timeout de Verificação (segundos)'),

    // Notifications
    'notification_email_on_incident_created' => __d('settings', 'Email ao Criar Incidente'),
    'notification_email_on_incident_resolved' => __d('settings', 'Email ao Resolver Incidente'),
    'notification_email_on_down' => __d('settings', 'Email ao Ficar Offline'),
    'notification_email_on_up' => __d('settings', 'Email ao Voltar Online'),
    'alert_throttle_minutes' => __d('settings', 'Intervalo Entre Alertas (minutos)'),
    'enable_email_alerts' => __d('settings', 'Habilitar Alertas por Email'),
    'enable_whatsapp_alerts' => __d('settings', 'Habilitar Alertas por WhatsApp'),
    'enable_telegram_alerts' => __d('settings', 'Habilitar Alertas por Telegram'),
    'enable_sms_alerts' => __d('settings', 'Habilitar Alertas por SMS'),
];

// Traduções das descrições (help text)
$descriptions = [
    // General
    'site_name' => __d('settings', 'Nome do site exibido na página de status'),
    'site_url' => __d('settings', 'URL completa onde o sistema está hospedado'),
    'site_logo_url' => __d('settings', 'URL completa da imagem da logo (PNG, JPG, SVG). Deixe vazio para usar o logo padrão.'),
    'site_language' => __d('settings', 'Idioma da interface do sistema'),
    'status_page_title' => __d('settings', 'Título exibido na página de status'),
    'status_page_public' => __d('settings', 'A página de status é acessível publicamente'),
    'status_page_cache_seconds' => __d('settings', 'Tempo de cache da página de status em segundos'),
    'support_email' => __d('settings', 'Email de suporte exibido no rodapé da página pública'),

    // Email
    'smtp_host' => __d('settings', 'Endereço do servidor SMTP'),
    'smtp_port' => __d('settings', 'Porta do servidor SMTP (geralmente 587 ou 465)'),
    'smtp_username' => __d('settings', 'Nome de usuário para autenticação SMTP'),
    'smtp_password' => __d('settings', 'Senha para autenticação SMTP'),
    'smtp_encryption' => __d('settings', 'Tipo de criptografia SMTP (TLS, SSL ou nenhuma)'),
    'email_from' => __d('settings', 'Endereço de email do remetente'),
    'email_from_name' => __d('settings', 'Nome exibido como remetente dos emails'),
    'smtp_timeout' => __d('settings', 'Tempo limite para conexão SMTP em segundos'),

    // Monitoring
    'monitor_default_interval' => __d('settings', 'Intervalo padrão entre verificações em segundos'),
    'monitor_default_timeout' => __d('settings', 'Tempo limite padrão para verificações em segundos'),
    'monitor_max_retries' => __d('settings', 'Número máximo de tentativas antes de marcar como falha'),
    'monitor_auto_resolve' => __d('settings', 'Resolver automaticamente incidentes quando monitor volta online'),
    'check_interval' => __d('settings', 'Intervalo entre execuções do comando de verificação em minutos'),
    'check_timeout' => __d('settings', 'Tempo máximo de execução de uma verificação em segundos'),

    // Notifications
    'notification_email_on_incident_created' => __d('settings', 'Enviar email quando um novo incidente é criado'),
    'notification_email_on_incident_resolved' => __d('settings', 'Enviar email quando um incidente é resolvido'),
    'notification_email_on_down' => __d('settings', 'Enviar email quando um monitor fica offline'),
    'notification_email_on_up' => __d('settings', 'Enviar email quando um monitor volta online'),
    'alert_throttle_minutes' => __d('settings', 'Intervalo mínimo em minutos entre alertas do mesmo monitor'),
    'enable_email_alerts' => __d('settings', 'Ativar envio de alertas por email para assinantes'),
    'enable_whatsapp_alerts' => __d('settings', 'Ativar envio de alertas via WhatsApp (funcionalidade futura)'),
    'enable_telegram_alerts' => __d('settings', 'Ativar envio de alertas via Telegram (funcionalidade futura)'),
    'enable_sms_alerts' => __d('settings', 'Ativar envio de alertas via SMS (funcionalidade futura)'),
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
    <h2><?= __d('settings', 'Configurações do Sistema') ?></h2>
</div>

<div class="tabs-container">
    <div class="tabs-nav">
        <button class="tab-button active" data-tab="general"><?= __d('settings', 'Geral') ?></button>
        <button class="tab-button" data-tab="email"><?= __d('settings', 'Email') ?></button>
        <button class="tab-button" data-tab="monitoring"><?= __d('settings', 'Monitoramento') ?></button>
        <button class="tab-button" data-tab="notifications"><?= __d('settings', 'Notificações') ?></button>
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
                            'pt_BR' => __d('settings', 'Português (Brasil)'),
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
                            '' => __d('settings', 'Nenhuma'),
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
                                <?= h(getDescription($setting->key, $descriptions) ?: __d('settings', 'Ativar esta opção')) ?>
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
                <?= $this->Form->button(__d('settings', 'Salvar Configurações'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
                <?= $this->Form->postLink(
                    __d('settings', 'Restaurar Padrões'),
                    ['action' => 'reset'],
                    [
                        'class' => 'btn btn-warning',
                        'data' => ['category' => 'general'],
                        'confirm' => __d('settings', 'Tem certeza que deseja restaurar as configurações para os valores padrão?')
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>
        <?php else: ?>
            <div class="empty-category">
                <p><?= __d('settings', 'Nenhuma configuração geral disponível.') ?></p>
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
                            '' => __d('settings', 'Nenhuma'),
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
                <?= $this->Form->button(__d('settings', 'Salvar Configurações'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
                <?= $this->Form->postLink(
                    __d('settings', 'Restaurar Padrões'),
                    ['action' => 'reset'],
                    [
                        'class' => 'btn btn-warning',
                        'data' => ['category' => 'email'],
                        'confirm' => __d('settings', 'Tem certeza que deseja restaurar as configurações para os valores padrão?')
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>

            <!-- Test Email Form -->
            <div class="test-email-section" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                <h4 style="margin-bottom: 15px;"><?= __d('settings', 'Testar Configurações de Email') ?></h4>
                <?= $this->Form->create(null, ['url' => ['action' => 'testEmail']]) ?>
                    <div class="form-group">
                        <label for="test_email"><?= __d('settings', 'Email de Destino') ?></label>
                        <?= $this->Form->email('test_email', [
                            'class' => 'form-control',
                            'placeholder' => __d('settings', 'Digite o email para receber o teste'),
                            'required' => true,
                            'value' => $this->Identity->get('email') ?? ''
                        ]) ?>
                        <span class="help-text"><?= __d('settings', 'Digite o endereço de email onde deseja receber o email de teste.') ?></span>
                    </div>
                    <div class="form-actions">
                        <?= $this->Form->button(__d('settings', 'Enviar Email de Teste'), [
                            'type' => 'submit',
                            'class' => 'btn btn-secondary'
                        ]) ?>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        <?php else: ?>
            <div class="empty-category">
                <p><?= __d('settings', 'Nenhuma configuração de email disponível.') ?></p>
                <p style="font-size: 13px; margin-top: 8px;"><?= __d('settings', 'Configure as configurações de email para enviar notificações.') ?></p>
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
                                <?= h(getDescription($setting->key, $descriptions) ?: __d('settings', 'Ativar esta opção')) ?>
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
                <?= $this->Form->button(__d('settings', 'Salvar Configurações'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
                <?= $this->Form->postLink(
                    __d('settings', 'Restaurar Padrões'),
                    ['action' => 'reset'],
                    [
                        'class' => 'btn btn-warning',
                        'data' => ['category' => 'monitoring'],
                        'confirm' => __d('settings', 'Tem certeza que deseja restaurar as configurações para os valores padrão?')
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>
        <?php else: ?>
            <div class="empty-category">
                <p><?= __d('settings', 'Nenhuma configuração de monitoramento disponível.') ?></p>
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
                                <?= h(getDescription($setting->key, $descriptions) ?: __d('settings', 'Ativar esta opção')) ?>
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
                <?= $this->Form->button(__d('settings', 'Salvar Configurações'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary'
                ]) ?>
                <?= $this->Form->postLink(
                    __d('settings', 'Restaurar Padrões'),
                    ['action' => 'reset'],
                    [
                        'class' => 'btn btn-warning',
                        'data' => ['category' => 'notifications'],
                        'confirm' => __d('settings', 'Tem certeza que deseja restaurar as configurações para os valores padrão?')
                    ]
                ) ?>
            </div>

            <?= $this->Form->end() ?>
        <?php else: ?>
            <div class="empty-category">
                <p><?= __d('settings', 'Nenhuma configuração de notificações disponível.') ?></p>
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
