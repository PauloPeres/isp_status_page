<?php
/**
 * Test Email Template (Text Version)
 *
 * @var \App\View\AppView $this
 * @var string $siteName
 */
?>
<?= __d('emails', 'TEST EMAIL') ?>

<?= str_repeat('=', 60) ?>


<?= __d('emails', 'Hello!') ?>

<?= __d('emails', 'This is a test email from the {0} system.', $siteName) ?>

<?= __d('emails', 'Success!') ?>

<?= __d('emails', 'If you are receiving this email, it means the email sending system is configured correctly.') ?>


<?= __d('emails', 'Email Information:') ?>

- <?= __d('emails', 'Date/Time:') ?> <?= date('Y-m-d H:i:s') ?>

- <?= __d('emails', 'Timezone:') ?> <?= date_default_timezone_get() ?>

- <?= __d('emails', 'System:') ?> <?= $siteName ?>


<?= __d('emails', 'You can now start sending notifications to your users!') ?>

<?= str_repeat('=', 60) ?>


<?= __d('emails', 'This is an automatic test email. No reply is necessary.') ?>
