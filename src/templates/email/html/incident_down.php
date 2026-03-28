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

<h2><?= __d('emails', 'New Incident Detected') ?></h2>

<p><?= __d('emails', 'Hello,') ?></p>

<p>
    <?= __d('emails', 'We detected a problem with one of our services:') ?>
</p>

<div class="error-box">
    <h3 style="margin-top: 0; color: #dc3545;"><?= h($incident->title) ?></h3>

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

    <p style="margin: 8px 0; font-size: 14px;">
        <strong><?= __d('emails', 'Severity:') ?></strong>
        <span style="text-transform: capitalize;">
            <?= $incident->severity === 'critical' ? __d('emails', 'Critical') : ($incident->severity === 'major' ? __d('emails', 'Major') : ($incident->severity === 'minor' ? __d('emails', 'Minor') : h($incident->severity))) ?>
        </span>
    </p>

    <p style="margin: 8px 0; font-size: 14px;">
        <strong><?= __d('emails', 'Started at:') ?></strong>
        <?= $incident->started_at->format('d/m/Y H:i:s') ?>
    </p>
</div>

<p>
    <?= __d('emails', 'Our team has been notified and is working to resolve the issue as quickly as possible. You will receive a new notification when the incident is resolved.') ?>
</p>

<div style="text-align: center;">
    <a href="<?= $statusUrl ?>" class="button">
        <?= __d('emails', 'View Status Page') ?>
    </a>
</div>

<div class="info-box">
    <p style="margin: 0; font-size: 14px;">
        <strong><?= __d('emails', 'Tip:') ?></strong> <?= __d('emails', 'Bookmark {0} to follow the service status in real time.', h($statusUrl)) ?>
    </p>
</div>

<p style="font-size: 13px; color: #6c757d; margin-top: 30px;">
    <?= __d('emails', 'No longer wish to receive notifications?') ?>
    <a href="<?= $unsubscribeUrl ?>" style="color: #6c757d;"><?= __d('emails', 'Unsubscribe') ?></a>
</p>
