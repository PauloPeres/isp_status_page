<?php
/**
 * Alert - Incident Down Email Template (Plain Text)
 *
 * @var \App\Model\Entity\Monitor $monitor
 * @var \App\Model\Entity\Incident $incident
 * @var string $siteName
 * @var string|null $acknowledgeUrl
 */
?>
SERVICE DOWN
============

Monitor "<?= h($monitor->name) ?>" has been detected as DOWN.

Monitor:    <?= h($monitor->name) ?>

Status:     DOWN
Severity:   <?= h($incident->severity) ?>

Started at: <?= $incident->started_at->nice() ?>

<?php if (!empty($incident->description)): ?>
Description: <?= h($incident->description) ?>

<?php endif; ?>
The team has been notified and is working to resolve the issue.
You will receive a notification when the service is restored.
<?php if (!empty($acknowledgeUrl)): ?>

Acknowledge this incident:
<?= $acknowledgeUrl ?>

(Link expires in 24 hours)
<?php endif; ?>

--
This alert was sent automatically by <?= h($siteName) ?>.
