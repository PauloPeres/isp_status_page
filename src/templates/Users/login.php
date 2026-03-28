<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __d('users', 'Login') ?> - ISP Status</title>
    <style>
        :root {
            /* Cores Primárias */
            --color-primary: #1E88E5;
            --color-success: #43A047;
            --color-dark: #263238;
            --color-white: #FFFFFF;

            /* Cores Secundárias */
            --color-primary-light: #90CAF9;
            --color-warning: #FDD835;
            --color-error: #E53935;

            /* Tons Neutros */
            --color-gray-light: #ECEFF1;
            --color-gray-medium: #B0BEC5;

            /* Hover States */
            --color-primary-hover: #1976D2;
            --color-error-hover: #D32F2F;

            /* Espaçamento */
            --space-md: 16px;
            --space-lg: 24px;
            --space-xl: 32px;

            /* Border Radius */
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 20px;

            /* Sombras */
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

        .login-box {
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

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--color-gray-light);
            text-align: center;
        }

        .credentials {
            display: inline-block;
            background: var(--color-gray-light);
            padding: 10px 16px;
            border-radius: var(--radius-md);
            font-size: 13px;
            color: var(--color-gray-medium);
            font-family: 'Courier New', monospace;
        }

        .forgot-password {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: var(--color-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: var(--color-primary-hover);
            text-decoration: underline;
        }

        /* Mobile */
        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .login-box {
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

            .forgot-password {
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

            .login-box {
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

            .credentials {
                font-size: 12px;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo-container">
            <img src="/img/icon_isp_status_page.png" alt="ISP Status" class="logo">
            <h1>ISP Status</h1>
            <p class="subtitle"><?= __d('users', 'Sign in to your account') ?></p>
        </div>

        <?php
        // Show flash messages
        echo $this->Flash->render();

        // Show login error if authentication failed
        if (isset($result) && $result !== null && $this->request->is('post') && !$result->isValid()):
        ?>
            <div class="alert alert-error">
                ⚠️ <?= __d('users', 'Invalid username or password. Please try again.') ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/users/login">
            <?php if (isset($this->request)): ?>
                <input type="hidden" name="_csrfToken" value="<?= $this->request->getAttribute('csrfToken') ?>">
            <?php endif; ?>

            <div class="input-group">
                <label for="username"><?= __d('users', 'Username or Email') ?></label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="<?= __d('users', 'Enter your username or email') ?>"
                    required
                    autofocus
                    autocomplete="username"
                >
            </div>

            <div class="input-group">
                <label for="password"><?= __('Password') ?></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="<?= __d('users', 'Enter your password') ?>"
                    required
                    autocomplete="current-password"
                >
            </div>

            <div class="input-group" style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 400;">
                    <input type="checkbox" name="remember_me" value="1" style="width: auto; margin: 0;">
                    <span style="font-size: 14px; color: var(--color-dark);"><?= __d('users', 'Remember me') ?></span>
                </label>
            </div>

            <button type="submit" class="btn"><?= __d('users', 'Sign In') ?></button>
        </form>

    <script>
    (function() {
        var form = document.querySelector('form');
        form.addEventListener('submit', function() {
            var btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Please wait...';
        });
    })();
    </script>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--color-gray-light);">
            <p style="text-align: center; color: var(--color-gray-medium); font-size: 13px; margin-bottom: 16px;"><?= __('Or sign in with') ?></p>
            <div style="display: flex; gap: 12px;">
                <a href="/auth/google/redirect" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border: 2px solid var(--color-gray-light); border-radius: var(--radius-lg); text-decoration: none; color: var(--color-dark); font-size: 14px; font-weight: 500; transition: all 0.3s ease;">
                    <svg width="18" height="18" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    Google
                </a>
                <a href="/auth/github/redirect" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; border: 2px solid var(--color-gray-light); border-radius: var(--radius-lg); text-decoration: none; color: var(--color-dark); font-size: 14px; font-weight: 500; transition: all 0.3s ease;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="var(--color-dark)"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                    GitHub
                </a>
            </div>
        </div>

        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'forgotPassword']) ?>" class="forgot-password">
            <?= __('Esqueci minha senha') ?>
        </a>

        <a href="<?= $this->Url->build(['controller' => 'Registration', 'action' => 'register']) ?>" class="forgot-password">
            <?= __("Don't have an account? Register") ?>
        </a>

        <!-- Default credentials removed for security (TASK-AUTH-011) -->
    </div>
</body>
</html>
