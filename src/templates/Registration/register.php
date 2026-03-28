<!DOCTYPE html>
<html lang="<?= h(str_replace('_', '-', \Cake\I18n\I18n::getLocale())) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Register') ?> - ISP Status</title>
    <link rel="stylesheet" href="/css/auth.css">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1E88E5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ISP Status">
    <link rel="apple-touch-icon" href="/img/icon_isp_status_page.png">
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

        <?php /* Per-field validation errors shown inline below each field (TASK-AUTH-016) */ ?>

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
                <?php if (!empty($user) && $user->getError('username')): ?>
                    <span class="field-error"><?= implode(', ', $user->getError('username')) ?></span>
                <?php endif; ?>
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
                <?php if (!empty($user) && $user->getError('email')): ?>
                    <span class="field-error"><?= implode(', ', $user->getError('email')) ?></span>
                <?php endif; ?>
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
                <?php if (!empty($user) && $user->getError('password')): ?>
                    <span class="field-error"><?= implode(', ', $user->getError('password')) ?></span>
                <?php endif; ?>
                <div class="password-requirements">
                    <small><?= __('Password must be at least 8 characters') ?></small>
                </div>
                <div class="strength-bar"><div class="strength-bar-fill" id="strengthBarFill"></div></div>
                <div class="strength-label" id="strengthLabel"></div>
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
                <?php if (!empty($user) && $user->getError('password_confirm')): ?>
                    <span class="field-error"><?= implode(', ', $user->getError('password_confirm')) ?></span>
                <?php endif; ?>
            </div>

            <div class="input-group" style="margin-bottom: 20px;">
                <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="agree_terms" required style="margin-top: 4px; width: auto;">
                    <span style="font-size: 13px; color: #666; font-weight: 400;">
                        <?= __('I agree to the') ?> <a href="/terms" target="_blank"><?= __('Terms of Service') ?></a>
                        <?= __('and') ?> <a href="/privacy" target="_blank"><?= __('Privacy Policy') ?></a>
                    </span>
                </label>
            </div>

            <button type="submit" class="btn"><?= __('Create Account') ?></button>
        </form>

    <script>
    (function() {
        // Password strength meter
        var passwordInput = document.getElementById('password');
        var strengthBarFill = document.getElementById('strengthBarFill');
        var strengthLabel = document.getElementById('strengthLabel');

        var labels = ['', '<?= __('Weak') ?>', '<?= __('Fair') ?>', '<?= __('Good') ?>', '<?= __('Strong') ?>', '<?= __('Very Strong') ?>'];
        var colors = ['', '#E53935', '#FF9800', '#FDD835', '#43A047', '#2E7D32'];

        passwordInput.addEventListener('input', function() {
            var strength = 0;
            var val = this.value;
            if (val.length >= 8) strength++;
            if (val.length >= 12) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            var pct = (strength / 5) * 100;
            strengthBarFill.style.width = pct + '%';
            strengthBarFill.style.backgroundColor = colors[strength] || '';
            strengthLabel.textContent = val.length > 0 ? labels[strength] : '';
            strengthLabel.style.color = colors[strength] || '';
        });

        // Form submission loading state
        var form = document.querySelector('form');
        form.addEventListener('submit', function() {
            var btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.textContent = '<?= __('Please wait...') ?>';
        });
    })();
    </script>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--color-gray-light);">
            <p style="text-align: center; color: var(--color-gray-medium); font-size: 13px; margin-bottom: 16px;"><?= __('Or sign up with') ?></p>
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

        <a href="<?= $this->Url->build(['controller' => 'Users', 'action' => 'login']) ?>" class="login-link">
            <?= __('Already have an account? Sign in') ?>
        </a>
    </div>
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
    </script>
</body>
</html>
