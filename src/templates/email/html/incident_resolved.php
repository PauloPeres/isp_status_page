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

<h2><?= __d('emails', 'Incident Resolved') ?></h2>

<p><?= __d('emails', 'Hello,') ?></p>

<p>
    <?= __d('emails', 'We have good news! The incident has been resolved:') ?>
</p>

<div class="success-box">
    <h3 style="margin-top: 0; color: #28a745;"><?= h($incident->title) ?></h3>

    <?php if (!empty($incident->description)): ?>
        <p style="margin: 8px 0;"><?= h($incident->description) ?></p>
    <?php endif; ?>

    <p style="margin: 8px 0; font-size: 14px;">
        <strong><?= __d('emails', 'Service:') ?></strong>
        <?php if (isset($incident->monitor)): ?>
            <?= h($incident->monitor->name) ?>
        <?php else: ?>
            N/A
        <?php endif; ?>
    </p>

    <?php if (!empty($duration)): ?>
        <p style="margin: 8px 0; font-size: 14px;">
            <strong><?= __d('emails', 'Downtime:') ?></strong> <?= h($duration) ?>
        </p>
    <?php endif; ?>

    <p style="margin: 8px 0; font-size: 14px;">
        <strong><?= __d('emails', 'Resolved at:') ?></strong>
        <?= $incident->resolved_at->format('d/m/Y H:i:s') ?>
    </p>
</div>

<?php if (!empty($incident->resolution_notes)): ?>
    <div class="info-box">
        <p style="margin: 0; font-size: 14px;">
            <strong><?= __d('emails', 'Resolution notes:') ?></strong><br/>
            <?= nl2br(h($incident->resolution_notes)) ?>
        </p>
    </div>
<?php endif; ?>

<p>
    <?= __d('emails', 'The service is operating normally again. Thank you for your patience!') ?>
</p>

<div style="text-align: center;">
    <a href="<?= $statusUrl ?>" class="button">
        <?= __d('emails', 'View Status Page') ?>
    </a>
</div>

<p style="font-size: 14px; color: #6c757d; margin-top: 20px;">
    <?= __d('emails', 'We will continue monitoring our services 24/7 and you will be notified immediately if there are new issues.') ?>
</p>

<p style="font-size: 13px; color: #6c757d; margin-top: 30px;">
    <?= __d('emails', 'No longer wish to receive notifications?') ?>
    <a href="<?= $unsubscribeUrl ?>" style="color: #6c757d;"><?= __d('emails', 'Unsubscribe') ?></a>
</p>
