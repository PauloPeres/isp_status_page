<!DOCTYPE html>
<html lang="<?= h(str_replace('_', '-', \Cake\I18n\I18n::getLocale())) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __d('users', 'Reset Password') ?> - ISP Status</title>
    <link rel="stylesheet" href="/css/auth.css">
</head>
<body>
    <div class="login-box">
        <div class="logo-container">
            <img src="/img/icon_isp_status_page.png" alt="ISP Status" class="logo">
            <h1><?= __d('users', 'Reset Password') ?></h1>
            <p class="subtitle multiline">
                <?= __d('users', 'Enter your new password below.') ?>
            </p>
        </div>

        <?= $this->Flash->render() ?>

        <form method="post" action="<?= $this->Url->build(['controller' => 'Users', 'action' => 'resetPassword', $token]) ?>">
            <?php if (isset($this->request)): ?>
                <input type="hidden" name="_csrfToken" value="<?= $this->request->getAttribute('csrfToken') ?>">
            <?php endif; ?>

            <div class="input-group">
                <label for="password"><?= __d('users', 'New Password') ?></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="<?= __d('users', 'Enter your new password') ?>"
                    required
                    autofocus
                    autocomplete="new-password"
                    minlength="8"
                >
            </div>

            <div class="input-group">
                <label for="confirm_password"><?= __d('users', 'Confirm New Password') ?></label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="<?= __d('users', 'Re-enter your new password') ?>"
                    required
                    autocomplete="new-password"
                    minlength="8"
                >
            </div>

            <div class="password-requirements">
                <strong><?= __d('users', 'Password requirements:') ?></strong>
                <ul>
                    <li><?= __d('users', 'Minimum 8 characters') ?></li>
                    <li><?= __d('users', 'Passwords must match') ?></li>
                </ul>
            </div>

            <button type="submit" class="btn"><?= __d('users', 'Reset Password') ?></button>
        </form>

        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>" class="back-link">
            &larr; <?= __d('users', 'Back to login') ?>
        </a>
    </div>
</body>
</html>
