<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Two-Factor Authentication') ?> - ISP Status</title>
    <style>
        :root {
            --color-primary: #1E88E5;
            --color-success: #43A047;
            --color-dark: #263238;
            --color-white: #FFFFFF;
            --color-primary-light: #90CAF9;
            --color-warning: #FDD835;
            --color-error: #E53935;
            --color-gray-light: #ECEFF1;
            --color-gray-medium: #B0BEC5;
            --color-primary-hover: #1976D2;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 20px;
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

        .verify-box {
            background: var(--color-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 420px;
            padding: 50px 40px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-xl);
            margin-bottom: 16px;
        }

        h1 {
            color: var(--color-dark);
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }

        .subtitle {
            color: var(--color-gray-medium);
            font-size: 14px;
            text-align: center;
            margin-bottom: 32px;
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

        input[type="text"] {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--color-gray-light);
            border-radius: var(--radius-lg);
            font-size: 20px;
            letter-spacing: 6px;
            text-align: center;
            color: var(--color-dark);
            background: var(--color-white);
            transition: all 0.3s ease;
            font-family: 'Courier New', monospace;
        }

        input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
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

        .alert-error {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid var(--color-error);
        }

        .toggle-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: var(--color-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
        }

        .toggle-link:hover {
            color: var(--color-primary-hover);
            text-decoration: underline;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--color-gray-light);
            color: var(--color-gray-medium);
            text-decoration: none;
            font-size: 13px;
        }

        .back-link:hover {
            color: var(--color-dark);
        }

        .shield-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        @media (max-width: 480px) {
            .verify-box {
                padding: 32px 20px;
            }

            input[type="text"] {
                font-size: 18px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="verify-box">
        <div class="logo-container">
            <div class="shield-icon">&#128274;</div>
            <h1><?= __('Two-Factor Authentication') ?></h1>
            <p class="subtitle" id="subtitle-totp"><?= __('Enter the 6-digit code from your authenticator app.') ?></p>
            <p class="subtitle" id="subtitle-recovery" style="display: none;"><?= __('Enter one of your recovery codes.') ?></p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert-error"><?= h($error) ?></div>
        <?php endif; ?>

        <?php
        // Show flash messages
        echo $this->Flash->render();
        ?>

        <form method="post" action="/two-factor/verify" id="verify-form">
            <?php if (isset($this->request)): ?>
                <input type="hidden" name="_csrfToken" value="<?= $this->request->getAttribute('csrfToken') ?>">
            <?php endif; ?>
            <input type="hidden" name="use_recovery" id="use_recovery" value="0">

            <div class="input-group" id="totp-group">
                <label for="code"><?= __('Verification Code') ?></label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    required
                    autofocus
                    placeholder="000000"
                >
            </div>

            <div class="input-group" id="recovery-group" style="display: none;">
                <label for="recovery-code"><?= __('Recovery Code') ?></label>
                <input
                    type="text"
                    id="recovery-code"
                    name="code"
                    maxlength="17"
                    autocomplete="off"
                    placeholder="XXXXXXXX-XXXXXXXX"
                    style="letter-spacing: 2px; font-size: 16px;"
                    disabled
                >
            </div>

            <button type="submit" class="btn"><?= __('Verify') ?></button>
        </form>

        <a class="toggle-link" id="toggle-recovery" onclick="toggleRecovery()"><?= __('Use a recovery code') ?></a>

        <a href="/users/login" class="back-link"><?= __('Back to login') ?></a>
    </div>

    <script>
    var isRecovery = false;

    function toggleRecovery() {
        isRecovery = !isRecovery;
        var totpGroup = document.getElementById('totp-group');
        var recoveryGroup = document.getElementById('recovery-group');
        var useRecovery = document.getElementById('use_recovery');
        var toggleLink = document.getElementById('toggle-recovery');
        var subtitleTotp = document.getElementById('subtitle-totp');
        var subtitleRecovery = document.getElementById('subtitle-recovery');
        var totpInput = document.getElementById('code');
        var recoveryInput = document.getElementById('recovery-code');

        if (isRecovery) {
            totpGroup.style.display = 'none';
            recoveryGroup.style.display = 'block';
            useRecovery.value = '1';
            toggleLink.textContent = <?= json_encode(__('Use authenticator code')) ?>;
            subtitleTotp.style.display = 'none';
            subtitleRecovery.style.display = 'block';
            totpInput.disabled = true;
            totpInput.removeAttribute('required');
            recoveryInput.disabled = false;
            recoveryInput.setAttribute('required', 'required');
            recoveryInput.focus();
        } else {
            totpGroup.style.display = 'block';
            recoveryGroup.style.display = 'none';
            useRecovery.value = '0';
            toggleLink.textContent = <?= json_encode(__('Use a recovery code')) ?>;
            subtitleTotp.style.display = 'block';
            subtitleRecovery.style.display = 'none';
            totpInput.disabled = false;
            totpInput.setAttribute('required', 'required');
            recoveryInput.disabled = true;
            recoveryInput.removeAttribute('required');
            totpInput.focus();
        }
    }
    </script>
</body>
</html>
