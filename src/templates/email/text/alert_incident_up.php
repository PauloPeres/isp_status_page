<?php
/**
 * Alert - Incident Up/Resolved Email Template (Plain Text)
 *
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
        $parts[] = $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
    }
    if ($diff->h > 0) {
        $parts[] = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
    }
    if ($diff->i > 0) {
        $parts[] = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    }

    $duration = !empty($parts) ? implode(', ', $parts) : 'less than 1 minute';
}
?>
SERVICE RESTORED
================

Monitor "<?= h($monitor->name) ?>" is back ONLINE.

Monitor:   <?= h($monitor->name) ?>

Status:    UP
<?php if (!empty($duration)): ?>
Downtime:  <?= h($duration) ?>

<?php endif; ?>
Resolved:  <?php if ($incident->resolved_at): ?><?= $incident->resolved_at->nice() ?><?php else: ?><?= \Cake\I18n\DateTime::now()->nice() ?><?php endif; ?>


The service is operating normally. Thank you for your patience!

--
This alert was sent automatically by <?= h($siteName) ?>.
