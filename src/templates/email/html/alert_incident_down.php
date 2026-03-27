<?php
/**
 * Alert - Incident Down Email Template
 *
 * Sent via AlertService/EmailAlertChannel when a monitor goes DOWN.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 * @var \App\Model\Entity\Incident $incident
 * @var string $siteName
 */
?>
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #E53935; color: #ffffff; padding: 20px; text-align: center; border-radius: 4px 4px 0 0;">
        <h1 style="margin: 0; font-size: 24px;">Servico Fora do Ar</h1>
    </div>

    <div style="background-color: #ffffff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 4px 4px;">
        <p style="font-size: 16px; color: #333333;">
            O monitor <strong><?= h($monitor->name) ?></strong> foi detectado como <strong style="color: #E53935;">FORA DO AR</strong>.
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555; width: 40%;">Monitor:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; color: #333;"><?= h($monitor->name) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Status:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">
                    <span style="color: #E53935; font-weight: bold;">DOWN</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Severidade:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; text-transform: capitalize;">
                    <?= h($incident->severity) ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Inicio:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">
                    <?= $incident->started_at->format('d/m/Y H:i:s') ?>
                </td>
            </tr>
            <?php if (!empty($incident->description)): ?>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">Descricao:</td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee;"><?= h($incident->description) ?></td>
            </tr>
            <?php endif; ?>
        </table>

        <p style="font-size: 14px; color: #666;">
            A equipe foi notificada e esta trabalhando para resolver o problema.
            Voce recebera uma notificacao quando o servico for restabelecido.
        </p>

        <?php if (!empty($acknowledgeUrl)): ?>
        <div style="text-align: center; margin: 24px 0;">
            <a href="<?= h($acknowledgeUrl) ?>"
               style="display: inline-block; padding: 12px 32px; background-color: #1E88E5; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 16px; font-weight: bold;">
                Reconhecer Incidente
            </a>
            <p style="font-size: 12px; color: #999; margin-top: 8px;">
                Clique para confirmar que voce esta ciente deste incidente. O link expira em 24 horas.
            </p>
        </div>
        <?php endif; ?>

        <p style="font-size: 12px; color: #999; margin-top: 24px; border-top: 1px solid #eee; padding-top: 12px;">
            Este alerta foi enviado automaticamente por <?= h($siteName) ?>.
        </p>
    </div>
</div>
