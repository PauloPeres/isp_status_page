<?php
/**
 * User Invitation Email Template
 *
 * @var \App\View\AppView $this
 * @var object $user
 * @var string $password
 * @var string $loginUrl
 * @var string $siteName
 */
$this->assign('title', __d('emails', 'Access Invitation'));
?>

<h2><?= __d('emails', 'Welcome to {0}!', h($siteName)) ?></h2>

<p><?= __d('emails', 'Hello {0}!', '<strong>' . h($user->username) . '</strong>') ?></p>

<p>
    <?= __d('emails', 'An account has been created for you in the {0} system. Below are your access credentials:', '<strong>' . h($siteName) . '</strong>') ?>
</p>

<!-- Credentials Box -->
<div class="info-box" style="margin: 20px 0; background: #f0f9ff; border-left: 4px solid #1E88E5;">
    <p style="margin: 0;">
        <strong><?= __d('emails', 'Username:') ?></strong> <?= h($user->username) ?><br>
        <strong><?= __d('emails', 'Email:') ?></strong> <?= h($user->email) ?><br>
        <strong><?= __d('emails', 'Temporary Password:') ?></strong> <code style="background: #e0e0e0; padding: 2px 6px; border-radius: 3px;"><?= h($password) ?></code><br>
        <strong><?= __d('emails', 'Role:') ?></strong> <?= h(ucfirst($user->role)) ?>
    </p>
</div>

<p>
    <?= __d('emails', 'To access the system, click the button below:') ?>
</p>

<!-- Login Button -->
<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $loginUrl ?>" class="button">
        <?= __d('emails', 'Access the System') ?>
    </a>
</p>

<!-- Alternative Link -->
<div class="info-box">
    <p><strong><?= __d('emails', 'Alternative link:') ?></strong></p>
    <p style="margin: 10px 0 0 0;">
        <?= __d('emails', 'If the button above does not work, copy and paste the link below into your browser:') ?>
    </p>
    <p style="word-break: break-all; margin: 10px 0 0 0;">
        <a href="<?= $loginUrl ?>"><?= $loginUrl ?></a>
    </p>
</div>

<!-- Warning Box -->
<div class="warning-box">
    <p style="margin: 0;">
        <strong><?= __d('emails', 'Important - First Login:') ?></strong>
        <?= __d('emails', 'For security, you will be required to change your password on your first login. Please choose a secure password that only you know.') ?>
    </p>
</div>

<!-- Security Tips -->
<div class="info-box">
    <p style="margin: 0 0 10px 0;"><strong><?= __d('emails', 'Security Tips:') ?></strong></p>
    <ul style="margin: 0; padding-left: 20px;">
        <li><?= __d('emails', 'Use a password with at least 8 characters') ?></li>
        <li><?= __d('emails', 'Combine uppercase, lowercase, numbers and symbols') ?></li>
        <li><?= __d('emails', 'Do not share your password with anyone') ?></li>
        <li><?= __d('emails', 'Do not reuse passwords from other services') ?></li>
    </ul>
</div>

<p>
    <?= __d('emails', 'If you have any questions or did not request this account, please contact the system administrator.') ?>
</p>

<p>
    <?= __d('emails', 'Best regards,') ?><br>
    <strong><?= __d('emails', '{0} Team', h($siteName)) ?></strong>
</p>
