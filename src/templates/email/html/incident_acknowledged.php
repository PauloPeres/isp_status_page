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
        <h1 style="margin: 0; font-size: 24px;"><?= __d('emails', 'Incident Acknowledged') ?></h1>
    </div>

    <div style="background-color: #ffffff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 4px 4px;">
        <p style="font-size: 16px; color: #333333;">
            <?= __d('emails', 'The incident for monitor {0} has been acknowledged.', '<strong>' . h($monitor->name) . '</strong>') ?>
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555; width: 40%;"><?= __d('emails', 'Monitor:') ?></td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; color: #333;"><?= h($monitor->name) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;"><?= __d('emails', 'Acknowledged by:') ?></td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; color: #333;"><?= h($acknowledgedBy) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;"><?= __d('emails', 'Date/Time:') ?></td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; color: #333;"><?= h($acknowledgedAt) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;"><?= __d('emails', 'Via:') ?></td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; color: #333; text-transform: capitalize;"><?= h($acknowledgedVia) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;"><?= __d('emails', 'Severity:') ?></td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; text-transform: capitalize;">
                    <?= h($incident->severity) ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;"><?= __d('emails', 'Incident Started:') ?></td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">
                    <?= $incident->started_at->nice() ?>
                </td>
            </tr>
        </table>

        <p style="font-size: 14px; color: #666;">
            <?= __d('emails', 'No additional action is required at this time. The team is aware and working on a resolution.') ?>
        </p>

        <p style="font-size: 12px; color: #999; margin-top: 24px; border-top: 1px solid #eee; padding-top: 12px;">
            <?= __d('emails', 'This alert was sent automatically by {0}.', h($siteName)) ?>
        </p>
    </div>
</div>
