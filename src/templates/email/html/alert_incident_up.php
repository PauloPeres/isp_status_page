<?php
/**
 * Alert - Incident Up/Resolved Email Template
 *
 * Sent via AlertService/EmailAlertChannel when a monitor comes back UP.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 * @var \App\Model\Entity\Incident $incident
 * @var string $siteName
 */

// Calculate downtime duration
$duration = '';
if ($incident->started_at && $incident->resolved_at) {
    $diff = $incident->started_at->diff($incident->resolved_at);

    $parts = [];
    if ($diff->d > 0) {
        $parts[] = $diff->d . ' ' . ($diff->d > 1 ? __d('emails', 'days') : __d('emails', 'day'));
    }
    if ($diff->h > 0) {
        $parts[] = $diff->h . ' ' . ($diff->h > 1 ? __d('emails', 'hours') : __d('emails', 'hour'));
    }
    if ($diff->i > 0) {
        $parts[] = $diff->i . ' ' . ($diff->i > 1 ? __d('emails', 'minutes') : __d('emails', 'minute'));
    }

    $duration = !empty($parts) ? implode(', ', $parts) : __d('emails', 'less than 1 minute');
}
?>
<div style="font-family: Arial, Helvetica, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #43A047; color: #ffffff; padding: 20px; text-align: center; border-radius: 4px 4px 0 0;">
        <h1 style="margin: 0; font-size: 24px;"><?= __d('emails', 'Service Restored') ?></h1>
    </div>

    <div style="background-color: #ffffff; padding: 24px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 4px 4px;">
        <p style="font-size: 16px; color: #333333;">
            <?= __d('emails', 'The monitor {0} is back {1}.', '<strong>' . h($monitor->name) . '</strong>', '<strong style="color: #43A047;">' . __d('emails', 'ONLINE') . '</strong>') ?>
        </p>

        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555; width: 40%;"><?= __d('emails', 'Monitor:') ?></td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; color: #333;"><?= h($monitor->name) ?></td>
            </tr>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;"><?= __d('emails', 'Status:') ?></td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee;">
                    <span style="color: #43A047; font-weight: bold;">UP</span>
                </td>
            </tr>
            <?php if (!empty($duration)): ?>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;"><?= __d('emails', 'Downtime:') ?></td>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee;"><?= h($duration) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="padding: 8px 12px; border-bottom: 1px solid #eee; font-weight: bold; color: #555;"><?= __d('emails', 'Resolved at:') ?></td>
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
            <?= __d('emails', 'The service is operating normally. Thank you for your patience!') ?>
        </p>

        <p style="font-size: 12px; color: #999; margin-top: 24px; border-top: 1px solid #eee; padding-top: 12px;">
            <?= __d('emails', 'This alert was sent automatically by {0}.', h($siteName)) ?>
        </p>
    </div>
</div>
