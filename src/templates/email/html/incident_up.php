<?php
/**
 * Incident Up/Recovery Notification Email Template
 *
 * Generic template for incident recovery notifications.
 * Used by subscriber notification system and can be used by AlertService.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 * @var \App\Model\Entity\Incident $incident
 * @var string $siteName
 * @var string|null $statusUrl
 * @var string|null $unsubscribeUrl
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
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #43A047; color: #ffffff; padding: 20px; text-align: center; border-radius: 4px 4px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">Servico Restabelecido</h1>
    </div>

    <div style="background-color: #ffffff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 4px 4px;">
        <p style="font-size: 16px; color: #333333;">
            O monitor <strong><?= h($monitor->name ?? $incident->title) ?></strong> esta novamente <strong style="color: #43A047;">ONLINE</strong>.
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <?php if (isset($monitor->name)): ?>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555; width: 40%;">Monitor:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; color: #333;"><?= h($monitor->name) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Status:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">
                    <span style="color: #43A047; font-weight: bold;">ONLINE</span>
                </td>
            </tr>
            <?php if (!empty($duration)): ?>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Tempo de inatividade:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee;"><?= h($duration) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Resolvido em:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">
                    <?php if ($incident->resolved_at): ?>
                        <?= $incident->resolved_at->format('d/m/Y H:i:s') ?>
                    <?php else: ?>
                        <?= date('d/m/Y H:i:s') ?>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <p style="font-size: 14px; color: #666;">
            O servico esta operando normalmente. Obrigado pela paciencia!
        </p>

        <?php if (!empty($statusUrl)): ?>
        <div style="text-align: center; margin: 20px 0;">
            <a href="<?= $statusUrl ?>" style="display: inline-block; padding: 12px 24px; background-color: #43A047; color: #ffffff; text-decoration: none; border-radius: 4px;">
                Ver Pagina de Status
            </a>
        </div>
        <?php endif; ?>

        <p style="font-size: 12px; color: #999; margin-top: 24px; border-top: 1px solid #eee; padding-top: 12px;">
            Este alerta foi enviado automaticamente por <?= h($siteName ?? 'ISP Status') ?>.
        </p>

        <?php if (!empty($unsubscribeUrl)): ?>
        <p style="font-size: 12px; color: #999;">
            <a href="<?= $unsubscribeUrl ?>" style="color: #999;">Cancelar inscricao</a>
        </p>
        <?php endif; ?>
    </div>
</div>
