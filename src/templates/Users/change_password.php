<!DOCTYPE html>
<html lang="<?= h(str_replace('_', '-', \Cake\I18n\I18n::getLocale())) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __d('users', 'Change Password') ?> - ISP Status</title>
    <link rel="stylesheet" href="/css/auth.css">
    <style>
        /* Page-specific: warning-themed gradient for mandatory password change */
        body {
            background: linear-gradient(135deg, var(--color-warning) 0%, #F9A825 100%);
        }
        .login-box {
            max-width: 480px;
        }
        input:focus {
            border-color: var(--color-warning);
            box-shadow: 0 0 0 3px rgba(253, 216, 53, 0.1);
        }
        .btn {
            background: var(--color-warning);
            color: var(--color-dark);
            box-shadow: 0 4px 12px rgba(253, 216, 53, 0.3);
        }
        .btn:hover {
            background: #F9A825;
            box-shadow: 0 6px 16px rgba(253, 216, 53, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo-container">
            <img src="/img/icon_isp_status_page.png" alt="ISP Status" class="logo">
            <h1><?= __d('users', 'Change Password') ?></h1>
            <p class="subtitle multiline">
                <?= __d('users', 'For security, you must change your password before continuing.') ?>
            </p>
        </div>

        <div class="warning-banner">
            <p>
                <strong><?= __d('users', 'Mandatory Password Change:') ?></strong>
                <?= __d('users', 'This is your first time accessing the system or you are using a temporary password. Please set a new secure password.') ?>
            </p>
        </div>

        <?= $this->Flash->render() ?>

        <form method="post" action="<?= $this->Url->build(['controller' => 'Users', 'action' => 'changePassword']) ?>">
            <?php if (isset($this->request)): ?>
                <input type="hidden" name="_csrfToken" value="<?= $this->request->getAttribute('csrfToken') ?>">
            <?php endif; ?>

            <div class="input-group">
                <label for="current_password"><?= __d('users', 'Current Password') ?></label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    placeholder="<?= __d('users', 'Enter your current password') ?>"
                    required
                    autofocus
                    autocomplete="current-password"
                >
            </div>

            <div class="input-group">
                <label for="new_password"><?= __d('users', 'New Password') ?></label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    placeholder="<?= __d('users', 'Enter your new password') ?>"
                    required
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
                    <li><?= __d('users', 'Must be different from current password') ?></li>
                    <li><?= __d('users', 'Passwords must match') ?></li>
                </ul>
            </div>

            <button type="submit" class="btn"><?= __d('users', 'Change Password') ?></button>
        </form>
    </div>
</body>
</html>
