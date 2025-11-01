<?php
/**
 * Incident Down Notification Email Template
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Subscriber $subscriber
 * @var \App\Model\Entity\Incident $incident
 * @var string $statusUrl
 * @var string $unsubscribeUrl
 * @var string $siteName
 */
?>

<h2>⚠️ Novo Incidente Detectado</h2>

<p>Olá,</p>

<p>
    Detectamos um problema em um dos nossos serviços:
</p>

<div class="error-box">
    <h3 style="margin-top: 0; color: #dc3545;"><?= h($incident->title) ?></h3>

    <?php if (!empty($incident->description)): ?>
        <p style="margin: 8px 0;"><?= h($incident->description) ?></p>
    <?php endif; ?>

    <p style="margin: 8px 0; font-size: 14px;">
        <strong>Serviço:</strong>
        <?php if (isset($incident->monitor)): ?>
            <?= h($incident->monitor->name) ?>
        <?php else: ?>
            N/A
        <?php endif; ?>
    </p>

    <p style="margin: 8px 0; font-size: 14px;">
        <strong>Severidade:</strong>
        <span style="text-transform: capitalize;">
            <?= $incident->severity === 'critical' ? 'Crítica' : ($incident->severity === 'major' ? 'Alta' : ($incident->severity === 'minor' ? 'Baixa' : h($incident->severity))) ?>
        </span>
    </p>

    <p style="margin: 8px 0; font-size: 14px;">
        <strong>Início:</strong>
        <?= $incident->started_at->format('d/m/Y H:i:s') ?>
    </p>
</div>

<p>
    Nossa equipe já foi notificada e está trabalhando para resolver o problema o mais rápido possível.
    Você receberá uma nova notificação quando o incidente for resolvido.
</p>

<div style="text-align: center;">
    <a href="<?= $statusUrl ?>" class="button">
        Ver Página de Status
    </a>
</div>

<div class="info-box">
    <p style="margin: 0; font-size: 14px;">
        <strong>Dica:</strong> Adicione <?= h($statusUrl) ?> aos seus favoritos para acompanhar o status dos serviços em tempo real.
    </p>
</div>

<p style="font-size: 13px; color: #6c757d; margin-top: 30px;">
    Não deseja mais receber notificações?
    <a href="<?= $unsubscribeUrl ?>" style="color: #6c757d;">Cancelar inscrição</a>
</p>
