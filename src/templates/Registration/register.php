<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Register') ?> - ISP Status</title>
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
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-hover) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-box {
            background: var(--color-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 420px;
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
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
        }

        input::placeholder {
            color: var(--color-gray-medium);
        }

        .btn {
            width: 100%;
            padding: 16px;
            background: var(--color-primary);
            color: var(--color-white);
            border: none;
            border-radius: var(--radius-lg);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 8px;
            box-shadow: 0 4px 12px rgba(30, 136, 229, 0.3);
        }

        .btn:hover {
            background: var(--color-primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(30, 136, 229, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }

        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid var(--color-error);
        }

        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid var(--color-success);
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

        .message.info {
            background: #E3F2FD;
            color: #1565C0;
            border-left: 4px solid var(--color-primary);
        }

        .error-message {
            color: var(--color-error);
            font-size: 12px;
            margin-top: 4px;
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: var(--color-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link:hover {
            color: var(--color-primary-hover);
            text-decoration: underline;
        }

        /* Mobile */
        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .register-box {
                padding: 40px 28px;
            }

            .logo {
                width: 64px;
                height: 64px;
            }

            .logo-container {
                margin-bottom: 30px;
            }

            h1 {
                font-size: 24px;
            }

            .subtitle {
                font-size: 14px;
                margin-bottom: 30px;
            }

            input {
                padding: 14px 16px;
                font-size: 16px;
                min-height: 48px;
            }

            .btn {
                padding: 16px;
                font-size: 16px;
                min-height: 48px;
            }

            .login-link {
                padding: 10px 0;
                min-height: 44px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 100%;
            }
        }

        /* Small Mobile */
        @media (max-width: 480px) {
            body {
                padding: 12px;
            }

            .register-box {
                padding: 32px 20px;
                border-radius: 16px;
            }

            .logo {
                width: 56px;
                height: 56px;
            }

            h1 {
                font-size: 22px;
            }

            .subtitle {
                font-size: 13px;
                margin-bottom: 24px;
            }

            label {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="register-box">
        <div class="logo-container">
            <img src="/img/icon_isp_status_page.png" alt="ISP Status" class="logo">
            <h1>ISP Status</h1>
            <p class="subtitle"><?= __('Create your account') ?></p>
        </div>

        <?php
        // Show flash messages
        echo $this->Flash->render();
        ?>

        <?php if (isset($user) && $user->getErrors()): ?>
            <div class="alert alert-error">
                <?php foreach ($user->getErrors() as $field => $errors): ?>
                    <?php foreach ($errors as $error): ?>
                        <div><?= h($error) ?></div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= $this->Url->build(['controller' => 'Registration', 'action' => 'register']) ?>">
            <?php if (isset($this->request)): ?>
                <input type="hidden" name="_csrfToken" value="<?= $this->request->getAttribute('csrfToken') ?>">
            <?php endif; ?>

            <div class="input-group">
                <label for="username"><?= __('Username') ?></label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="<?= __('Choose a username') ?>"
                    value="<?= h($this->request->getData('username', '')) ?>"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>

            <div class="input-group">
                <label for="email"><?= __('Email') ?></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="<?= __('Enter your email address') ?>"
                    value="<?= h($this->request->getData('email', '')) ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="input-group">
                <label for="password"><?= __('Password') ?></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="<?= __('At least 8 characters') ?>"
                    required
                    autocomplete="new-password"
                    minlength="8"
                >
            </div>

            <div class="input-group">
                <label for="password_confirm"><?= __('Confirm Password') ?></label>
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    placeholder="<?= __('Repeat your password') ?>"
                    required
                    autocomplete="new-password"
                    minlength="8"
                >
            </div>

            <button type="submit" class="btn"><?= __('Create Account') ?></button>
        </form>

        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>" class="login-link">
            <?= __('Already have an account? Sign in') ?>
        </a>
    </div>
</body>
</html>
