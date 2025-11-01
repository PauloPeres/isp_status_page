<?php
/**
 * Test Email Template
 *
 * @var \App\View\AppView $this
 * @var string $siteName
 */
?>

<h2>Email de Teste</h2>

<p>Olá!</p>

<p>
    Este é um email de teste do sistema <strong><?= h($siteName) ?></strong>.
</p>

<div class="success-box">
    <p style="margin: 0;">
        <strong>✅ Sucesso!</strong><br/>
        Se você está recebendo este email, significa que o sistema de envio de emails está configurado corretamente.
    </p>
</div>

<div class="info-box">
    <p style="margin: 0;"><strong>Informações do Email:</strong></p>
    <ul style="margin: 8px 0; padding-left: 20px;">
        <li>Data/Hora: <?= date('d/m/Y H:i:s') ?></li>
        <li>Fuso horário: <?= date_default_timezone_get() ?></li>
        <li>Sistema: <?= h($siteName) ?></li>
    </ul>
</div>

<p>
    Agora você pode começar a enviar notificações aos seus usuários!
</p>

<p style="font-size: 14px; color: #6c757d;">
    Este é um email automático de teste. Não é necessário responder.
</p>
