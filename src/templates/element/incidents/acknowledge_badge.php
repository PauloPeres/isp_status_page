<?php
/**
 * Acknowledge Badge Element
 *
 * Displays acknowledged status badge for an incident.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Incident $incident
 */
?>
<?php if ($incident->isAcknowledged()): ?>
    <span class="badge badge-info" title="<?= __d('incidents', 'Acknowledged at {0} via {1}',
        $incident->acknowledged_at ? h($incident->acknowledged_at->nice()) : '',
        h($incident->acknowledged_via ?? '')) ?>">
        &#x2714; <?= __d('incidents', 'Acknowledged') ?>
    </span>
<?php elseif ($incident->isOngoing()): ?>
    <span class="badge badge-warning" title="<?= __d('incidents', 'Awaiting acknowledgement') ?>">
        &#x23F3; <?= __d('incidents', 'Not acknowledged') ?>
    </span>
<?php endif; ?>
