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

<?= __d('emails', 'Este é um email automático. Por favor, não responda.') ?>

<?= str_repeat('=', 70) ?>
