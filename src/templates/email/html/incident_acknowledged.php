<?php
/**
 * Incident Acknowledged Email Template
 *
 * Sent to other alert recipients when someone acknowledges an incident.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 * @var \App\Model\Entity\Incident $incident
 * @var string $acknowledgedBy
 * @var string $acknowledgedAt
 * @var string $acknowledgedVia
 * @var string $siteName
 */
?>
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #1E88E5; color: #ffffff; padding: 20px; text-align: center; border-radius: 4px 4px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">Incidente Reconhecido</h1>
    </div>

    <div style="background-color: #ffffff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 4px 4px;">
        <p style="font-size: 16px; color: #333333;">
            O incidente do monitor <strong><?= h($monitor->name) ?></strong> foi reconhecido.
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555; width: 40%;">Monitor:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; color: #333;"><?= h($monitor->name) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Reconhecido por:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; color: #333;"><?= h($acknowledgedBy) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Data/Hora:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; color: #333;"><?= h($acknowledgedAt) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Via:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; color: #333; text-transform: capitalize;"><?= h($acknowledgedVia) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Severidade:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; text-transform: capitalize;">
                    <?= h($incident->severity) ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Inicio do Incidente:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">
                    <?= $incident->started_at->format('d/m/Y H:i:s') ?>
                </td>
            </tr>
        </table>

        <p style="font-size: 14px; color: #666;">
            Nenhuma acao adicional e necessaria no momento. A equipe esta ciente e trabalhando na resolucao.
        </p>

        <p style="font-size: 12px; color: #999; margin-top: 24px; border-top: 1px solid #eee; padding-top: 12px;">
            Este alerta foi enviado automaticamente por <?= h($siteName) ?>.
        </p>
    </div>
</div>
