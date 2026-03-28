<?php
/**
 * Subscriber Verification Email Template
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Subscriber $subscriber
 * @var string $verifyUrl
 * @var string $siteName
 */
?>

<h2><?= __d('emails', 'Welcome!') ?></h2>

<p><?= __d('emails', 'Hello!') ?></p>

<p>
    <?= __d('emails', 'Thank you for subscribing to receive status notifications from {0}.', '<strong>' . h($siteName) . '</strong>') ?>
</p>

<p>
    <?= __d('emails', 'To confirm your email {0} and start receiving notifications, please click the button below:', '<strong>' . h($subscriber->email) . '</strong>') ?>
</p>

<div style="text-align: center;">
    <a href="<?= $verifyUrl ?>" class="button">
        <?= __d('emails', 'Verify My Email') ?>
    </a>
</div>

<div class="info-box">
    <p style="margin: 0;"><strong><?= __d('emails', 'What you will receive:') ?></strong></p>
    <ul style="margin: 8px 0; padding-left: 20px;">
        <li><?= __d('emails', 'Notifications when services go offline') ?></li>
        <li><?= __d('emails', 'Alerts about ongoing incidents') ?></li>
        <li><?= __d('emails', 'Confirmations when issues are resolved') ?></li>
    </ul>
</div>

<p style="font-size: 14px; color: #6c757d;">
    <?= __d('emails', 'If you did not subscribe to receive notifications, please ignore this email.') ?>
</p>

<p style="font-size: 14px; color: #6c757d;">
    <?= __d('emails', 'Link not working? Copy and paste this address into your browser:') ?><br/>
    <span style="word-break: break-all;"><?= h($verifyUrl) ?></span>
</p>
