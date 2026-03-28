<?php
/**
 * Default Email Layout (Text)
 *
 * @var \App\View\AppView $this
 */
?>
<?= str_repeat('=', 70) ?>

<?= $this->fetch('title', 'ISP STATUS') ?>

<?= str_repeat('=', 70) ?>


<?= $this->fetch('content') ?>


<?= str_repeat('-', 70) ?>

<?= __d('emails', 'This is an automatic email. Please do not reply.') ?>

<?= str_repeat('=', 70) ?>
