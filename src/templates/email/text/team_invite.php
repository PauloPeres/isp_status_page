<?php
/**
 * Team Invitation Email Template (Plain Text)
 *
 * @var string $acceptUrl
 * @var string $orgName
 * @var string $inviterName
 * @var \App\Model\Entity\Invitation $invitation
 */
?>
Team Invitation
===============

Hi there,

<?= h($inviterName) ?> has invited you to join <?= h($orgName) ?> as a <?= h(ucfirst($invitation->role)) ?>.

Accept the invitation by visiting:
<?= $acceptUrl ?>

This invitation will expire on <?= h($invitation->expires_at->format('F j, Y')) ?>.

If you did not expect this invitation, you can safely ignore this email.

--
Sent by <?= \Cake\Core\Configure::read('Brand.fullName', 'ISP Status Page') ?>
