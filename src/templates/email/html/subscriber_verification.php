<?php
/**
 * Subscriber Verification Email Template
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Subscriber $subscriber
 * @var string $verifyUrl
 * @var string $siteName
 */
?>

<h2>Bem-vindo!</h2>

<p>Olá!</p>

<p>
    Obrigado por se inscrever para receber notificações de status em <strong><?= h($siteName) ?></strong>.
</p>

<p>
    Para confirmar seu email <strong><?= h($subscriber->email) ?></strong> e começar a receber notificações,
    por favor clique no botão abaixo:
</p>

<div style="text-align: center;">
    <a href="<?= $verifyUrl ?>" class="button">
        Verificar Meu Email
    </a>
</div>

<div class="info-box">
    <p style="margin: 0;"><strong>O que você receberá:</strong></p>
    <ul style="margin: 8px 0; padding-left: 20px;">
        <li>Notificações quando serviços ficarem offline</li>
        <li>Alertas sobre incidentes em andamento</li>
        <li>Confirmações quando problemas forem resolvidos</li>
    </ul>
</div>

<p style="font-size: 14px; color: #6c757d;">
    Se você não se inscreveu para receber notificações, por favor ignore este email.
</p>

<p style="font-size: 14px; color: #6c757d;">
    Link não funciona? Copie e cole este endereço no seu navegador:<br/>
    <span style="word-break: break-all;"><?= h($verifyUrl) ?></span>
</p>
