<!DOCTYPE html>
<html lang="<?= h(str_replace('_', '-', \Cake\I18n\I18n::getLocale())) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __d('users', 'Change Password') ?> - ISP Status</title>
    <style>
        :root {
            /* Primary Colors */
            --color-primary: #1E88E5;
            --color-success: #43A047;
            --color-dark: #263238;
            --color-white: #FFFFFF;

            /* Secondary Colors */
            --color-primary-light: #90CAF9;
            --color-warning: #FDD835;
            --color-error: #E53935;

            /* Neutral Tones */
            --color-gray-light: #ECEFF1;
            --color-gray-medium: #B0BEC5;

            /* Hover States */
            --color-primary-hover: #1976D2;
            --color-error-hover: #D32F2F;

            /* Spacing */
            --space-md: 16px;
            --space-lg: 24px;
            --space-xl: 32px;

            /* Border Radius */
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 20px;

            /* Shadows */
            --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--color-warning) 0%, #F9A825 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-box {
            background: var(--color-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 480px;
            padding: 50px 40px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
        }

        h1 {
            color: var(--color-dark);
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }

        .subtitle {
            color: var(--color-gray-medium);
            font-size: 15px;
            text-align: center;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .warning-banner {
            background: #FFF3CD;
            border-left: 4px solid var(--color-warning);
            padding: 16px;
            border-radius: var(--radius-md);
            margin-bottom: 30px;
        }

        .warning-banner p {
            margin: 0;
            color: #856404;
            font-size: 14px;
            line-height: 1.5;
        }

        .input-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            color: var(--color-dark);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--color-gray-light);
            border-radius: var(--radius-lg);
            font-size: 15px;
            color: var(--color-dark);
            background: var(--color-white);
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: var(--color-warning);
            box-shadow: 0 0 0 3px rgba(253, 216, 53, 0.1);
        }

        input::placeholder {
            color: var(--color-gray-medium);
        }

        .btn {
            width: 100%;
            padding: 16px;
            background: var(--color-warning);
            color: var(--color-dark);
            border: none;
            border-radius: var(--radius-lg);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
            box-shadow: 0 4px 12px rgba(253, 216, 53, 0.3);
        }

        .btn:hover {
            background: #F9A825;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(253, 216, 53, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* CakePHP Flash Messages */
        .message {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .message.error {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid var(--color-error);
        }

        .message.success {
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid var(--color-success);
        }

        .message.warning {
            background: #FFF9E6;
            color: #F57C00;
            border-left: 4px solid var(--color-warning);
        }

        .password-requirements {
            margin-top: 12px;
            padding: 12px;
            background: var(--color-gray-light);
            border-radius: var(--radius-md);
            font-size: 13px;
            color: var(--color-gray-medium);
        }

        .password-requirements ul {
            margin: 8px 0 0 0;
            padding-left: 20px;
        }

        .password-requirements li {
            margin: 4px 0;
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 40px 30px;
            }

            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo-container">
            <img src="/img/icon_isp_status_page.png" alt="ISP Status" class="logo">
            <h1><?= __d('users', 'Change Password') ?></h1>
            <p class="subtitle">
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
