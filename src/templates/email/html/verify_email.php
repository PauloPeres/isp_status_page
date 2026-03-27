<?php
/**
 * Email Verification Template
 *
 * @var \App\View\AppView $this
 * @var string $verifyLink
 * @var object $user
 * @var string $siteName
 */
$this->assign('title', 'Verify Your Email');
?>

<h2>Verify Your Email Address</h2>

<p>Hello <?= h($user->username) ?>!</p>

<p>
    Thank you for registering with <?= h($siteName) ?>. Please verify your email address to activate your account.
</p>

<!-- User Info Box -->
<div class="info-box" style="margin: 20px 0;">
    <p style="margin: 0;">
        <strong>Username:</strong> <?= h($user->username) ?><br>
        <strong>Email:</strong> <?= h($user->email) ?>
    </p>
</div>

<p>
    Click the button below to verify your email:
</p>

<!-- Verify Button -->
<p style="text-align: center; margin: 30px 0;">
    <a href="<?= $verifyLink ?>" class="button" style="display: inline-block; padding: 14px 32px; background-color: #1E88E5; color: #FFFFFF; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px;">
        Verify My Email
    </a>
</p>

<!-- Alternative Link -->
<div class="info-box">
    <p><strong>Alternative link:</strong></p>
    <p style="margin: 10px 0 0 0;">
        If the button above does not work, copy and paste the link below into your browser:
    </p>
    <p style="word-break: break-all; margin: 10px 0 0 0;">
        <a href="<?= $verifyLink ?>"><?= $verifyLink ?></a>
    </p>
</div>

<!-- Warning -->
<div class="warning-box" style="background: #FFF9E6; border-left: 4px solid #FDD835; padding: 14px 18px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0;">
        <strong>Important:</strong>
        This link expires in 24 hours for security.
    </p>
</div>

<p>
    If you did not create an account, you can safely ignore this email.
</p>
