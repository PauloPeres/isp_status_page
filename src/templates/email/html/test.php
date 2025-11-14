<?php
/**
 * Test Email Template
 *
 * @var \App\View\AppView $this
 * @var string $siteName
 */
?>

<h2><?= __d('emails', 'Email de Teste') ?></h2>

<p><?= __d('emails', 'Olá!') ?></p>

<p>
    <?= __d('emails', 'Este é um email de teste do sistema {0}.', '<strong>' . h($siteName) . '</strong>') ?>
</p>

<div class="success-box">
    <p style="margin: 0;">
        <strong>✅ <?= __d('emails', 'Sucesso!') ?></strong><br/>
        <?= __d('emails', 'Se você está recebendo este email, significa que o sistema de envio de emails está configurado corretamente.') ?>
    </p>
</div>

<div class="info-box">
    <p style="margin: 0;"><strong><?= __d('emails', 'Informações do Email:') ?></strong></p>
    <ul style="margin: 8px 0; padding-left: 20px;">
        <li><?= __d('emails', 'Data/Hora:') ?> <?= date('d/m/Y H:i:s') ?></li>
        <li><?= __d('emails', 'Fuso horário:') ?> <?= date_default_timezone_get() ?></li>
        <li><?= __d('emails', 'Sistema:') ?> <?= h($siteName) ?></li>
    </ul>
</div>

<p>
    <?= __d('emails', 'Agora você pode começar a enviar notificações aos seus usuários!') ?>
</p>

<p style="font-size: 14px; color: #6c757d;">
    <?= __d('emails', 'Este é um email automático de teste. Não é necessário responder.') ?>
</p>
