<?php
/**
 * Incident Acknowledged Email Template (Plain Text)
 *
 * @var \App\Model\Entity\Monitor $monitor
 * @var \App\Model\Entity\Incident $incident
 * @var string $acknowledgedBy
 * @var string $acknowledgedAt
 * @var string $acknowledgedVia
 * @var string $siteName
 */
?>
Incident Acknowledged
=====================

The incident for monitor "<?= h($monitor->name) ?>" has been acknowledged.

Monitor:          <?= h($monitor->name) ?>

Acknowledged by:  <?= h($acknowledgedBy) ?>

Date/Time:        <?= h($acknowledgedAt) ?>

Via:              <?= h($acknowledgedVia) ?>

Severity:         <?= h($incident->severity) ?>

Incident start:   <?= $incident->started_at->nice() ?>


No additional action is required at this time. The team is aware and working on a resolution.

--
This alert was sent automatically by <?= h($siteName) ?>.
