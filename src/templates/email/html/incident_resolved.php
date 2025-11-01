<?php
/**
 * Incident Resolved Notification Email Template
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Subscriber $subscriber
 * @var \App\Model\Entity\Incident $incident
 * @var string $statusUrl
 * @var string $unsubscribeUrl
 * @var string $siteName
 */

// Calculate downtime duration
$duration = '';
if ($incident->started_at && $incident->resolved_at) {
    $diff = $incident->started_at->diff($incident->resolved_at);

    $parts = [];
    if ($diff->d > 0) {
        $parts[] = $diff->d . ' dia' . ($diff->d > 1 ? 's' : '');
    }
    if ($diff->h > 0) {
        $parts[] = $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
    }
    if ($diff->i > 0) {
        $parts[] = $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
    }

    $duration = !empty($parts) ? implode(', ', $parts) : 'menos de 1 minuto';
}
?>

<h2>✅ Incidente Resolvido</h2>

<p>Olá,</p>

<p>
    Temos boas notícias! O incidente foi resolvido:
</p>

<div class="success-box">
    <h3 style="margin-top: 0; color: #28a745;"><?= h($incident->title) ?></h3>

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

    <?php if (!empty($duration)): ?>
        <p style="margin: 8px 0; font-size: 14px;">
            <strong>Tempo de inatividade:</strong> <?= h($duration) ?>
        </p>
    <?php endif; ?>

    <p style="margin: 8px 0; font-size: 14px;">
        <strong>Resolvido em:</strong>
        <?= $incident->resolved_at->format('d/m/Y H:i:s') ?>
    </p>
</div>

<?php if (!empty($incident->resolution_notes)): ?>
    <div class="info-box">
        <p style="margin: 0; font-size: 14px;">
            <strong>Notas de resolução:</strong><br/>
            <?= nl2br(h($incident->resolution_notes)) ?>
        </p>
    </div>
<?php endif; ?>

<p>
    O serviço está operando normalmente novamente. Obrigado pela sua paciência!
</p>

<div style="text-align: center;">
    <a href="<?= $statusUrl ?>" class="button">
        Ver Página de Status
    </a>
</div>

<p style="font-size: 14px; color: #6c757d; margin-top: 20px;">
    Continuaremos monitorando nossos serviços 24/7 e você será notificado imediatamente se houver novos problemas.
</p>

<p style="font-size: 13px; color: #6c757d; margin-top: 30px;">
    Não deseja mais receber notificações?
    <a href="<?= $unsubscribeUrl ?>" style="color: #6c757d;">Cancelar inscrição</a>
</p>
