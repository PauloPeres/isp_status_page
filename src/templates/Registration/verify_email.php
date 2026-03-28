<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Check Your Email') ?> - ISP Status</title>
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

        .verify-box {
            background: var(--color-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 480px;
            padding: 50px 40px;
            text-align: center;
        }

        .logo {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
        }

        .email-icon {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
        }

        h1 {
            color: var(--color-dark);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .description {
            color: var(--color-gray-medium);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .email-address {
            display: inline-block;
            background: var(--color-gray-light);
            padding: 10px 20px;
            border-radius: var(--radius-md);
            font-size: 15px;
            font-weight: 600;
            color: var(--color-dark);
            margin-bottom: 30px;
            word-break: break-all;
        }

        .info-box {
            background: #E3F2FD;
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #1565C0;
            line-height: 1.5;
            text-align: left;
        }

        .links {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--color-gray-light);
        }

        .links a {
            color: var(--color-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: var(--color-primary-hover);
            text-decoration: underline;
        }

        /* CakePHP Flash Messages */
        .message {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
            text-align: left;
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

        /* Mobile */
        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .verify-box {
                padding: 40px 28px;
            }

            h1 {
                font-size: 24px;
            }

            .description {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 12px;
            }

            .verify-box {
                padding: 32px 20px;
                border-radius: 16px;
            }

            h1 {
                font-size: 22px;
            }
        }
    </style>
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
