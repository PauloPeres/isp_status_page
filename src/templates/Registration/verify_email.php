<!DOCTYPE html>
<html lang="<?= h(str_replace('_', '-', \Cake\I18n\I18n::getLocale())) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Check Your Email') ?> - ISP Status</title>
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="verify-box">
        <img src="/img/icon_isp_status_page.png" alt="ISP Status" class="logo">

        <?= $this->Flash->render() ?>

        <span class="email-icon">&#9993;</span>

        <h1><?= __('Check Your Email') ?></h1>

        <p class="description">
            <?= __('We have sent a verification link to your email address. Please check your inbox and click the link to activate your account.') ?>
        </p>

        <?php if (!empty($email)): ?>
            <div class="email-address"><?= h($email) ?></div>
        <?php endif; ?>

        <div class="info-box">
            <?= __('The verification link will expire in 24 hours. If you do not see the email, please check your spam/junk folder.') ?>
        </div>

        <?php if (!empty($email)): ?>
        <div style="margin-bottom: 24px; text-align: center;">
            <p style="color: var(--color-gray-medium); font-size: 14px; margin-bottom: 12px;">
                <?= __("Didn't receive the email?") ?>
            </p>
            <a href="<?= $this->Url->build('/resend-verification?email=' . urlencode($email)) ?>"
               style="display: inline-block; padding: 12px 24px; background: var(--color-primary); color: var(--color-white); border-radius: var(--radius-lg); text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.3s ease;">
                <?= __('Resend Verification Email') ?>
            </a>
        </div>
        <?php endif; ?>

        <div class="links">
            <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>">
                <?= __('Back to Login') ?>
            </a>
        </div>
    </div>
</body>
</html>
