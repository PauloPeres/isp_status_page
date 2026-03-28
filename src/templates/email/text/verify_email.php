<?php
/**
 * Email Verification Template (Plain Text)
 *
 * @var string $verifyLink
 * @var object $user
 * @var string $siteName
 */
?>
Verify Your Email Address
=========================

Hello <?= h($user->username) ?>!

Thank you for registering with <?= h($siteName) ?>. Please verify your email address to activate your account.

Username: <?= h($user->username) ?>

Email: <?= h($user->email) ?>

Verify your email by visiting:
<?= $verifyLink ?>

Important: This link expires in 24 hours for security.

If you did not create an account, you can safely ignore this email.

--
<?= h($siteName) ?>
