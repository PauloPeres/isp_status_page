<?php
/**
 * Test Email Template
 *
 * @var \App\View\AppView $this
 * @var string $siteName
 */
?>

<h2><?= __d('emails', 'Test Email') ?></h2>

<p><?= __d('emails', 'Hello!') ?></p>

<p>
    <?= __d('emails', 'This is a test email from the {0} system.', '<strong>' . h($siteName) . '</strong>') ?>
</p>

<div class="success-box">
    <p style="margin: 0;">
        <strong>✅ <?= __d('emails', 'Success!') ?></strong><br/>
        <?= __d('emails', 'If you are receiving this email, it means the email sending system is configured correctly.') ?>
    </p>
</div>

<div class="info-box">
    <p style="margin: 0;"><strong><?= __d('emails', 'Email Information:') ?></strong></p>
    <ul style="margin: 8px 0; padding-left: 20px;">
        <li><?= __d('emails', 'Date/Time:') ?> <?= date('Y-m-d H:i:s') ?></li>
        <li><?= __d('emails', 'Timezone:') ?> <?= date_default_timezone_get() ?></li>
        <li><?= __d('emails', 'System:') ?> <?= h($siteName) ?></li>
    </ul>
</div>

<p>
    <?= __d('emails', 'You can now start sending notifications to your users!') ?>
</p>

<p style="font-size: 14px; color: #6c757d;">
    <?= __d('emails', 'This is an automatic test email. No reply is necessary.') ?>
</p>
