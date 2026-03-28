<?php
/**
 * Password Reset Email Template
 *
 * @var \App\View\AppView $this
 * @var string $resetLink
 * @var object $user
 */
$this->assign('title', __d('emails', 'Password Recovery'));
?>

<h2><?= __d('emails', 'Password Recovery') ?></h2>

<p><?= __d('emails', 'Hello!') ?></p>

<p>
    <?= __d('emails', 'You requested a password reset for your ISP Status Page account.') ?>
</p>

<!-- User Info Box -->
<div class="info-box" style="margin: 20px 0;">
    <p style="margin: 0;">
        <strong><?= __d('emails', 'Username:') ?></strong> <?= h($user->username) ?><br>
        <strong><?= __d('emails', 'Email:') ?></strong> <?= h($user->email) ?>
    </p>
</div>

<p>
    <?= __d('emails', 'To reset your password, click the button below:') ?>
</p>

<!-- Reset Button -->
<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $resetLink ?>" class="button">
        <?= __d('emails', 'Reset My Password') ?>
    </a>
</p>

<!-- Alternative Link -->
<div class="info-box">
    <p><strong><?= __d('emails', 'Alternative link:') ?></strong></p>
    <p style="margin: 10px 0 0 0;">
        <?= __d('emails', 'If the button above does not work, copy and paste the link below into your browser:') ?>
    </p>
    <p style="word-break: break-all; margin: 10px 0 0 0;">
        <a href="<?= $resetLink ?>"><?= $resetLink ?></a>
    </p>
</div>

<!-- Warning -->
<div class="warning-box">
    <p style="margin: 0;">
        <strong><?= __d('emails', 'Important:') ?></strong>
        <?= __d('emails', 'This link expires in 1 hour for security.') ?>
    </p>
</div>

<p>
    <?= __d('emails', 'If you did not request a password reset, ignore this email. Your password will remain unchanged.') ?>
</p>
