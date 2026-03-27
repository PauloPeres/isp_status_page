<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('All Set!') ?> - ISP Status</title>
    <style>
        :root {
            --color-primary: #1E88E5;
            --color-success: #43A047;
            --color-dark: #263238;
            --color-white: #FFFFFF;
            --color-primary-light: #90CAF9;
            --color-gray-light: #ECEFF1;
            --color-gray-medium: #B0BEC5;
            --color-primary-hover: #1976D2;
            --radius-lg: 12px;
            --radius-xl: 20px;
            --shadow-lg: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-hover) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .wizard-box {
            background: var(--color-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 500px;
            padding: 50px 40px;
            text-align: center;
        }

        .progress-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 40px;
        }

        .progress-step {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
        }

        .progress-step.completed { background: var(--color-success); color: var(--color-white); }
        .progress-line { width: 60px; height: 3px; }
        .progress-line.completed { background: var(--color-success); }

        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--color-success);
            color: var(--color-white);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 24px;
        }

        h1 { color: var(--color-dark); font-size: 28px; font-weight: 700; margin-bottom: 12px; }
        .subtitle { color: var(--color-gray-medium); font-size: 16px; margin-bottom: 32px; line-height: 1.6; }

        .btn {
            display: inline-block;
            padding: 16px 40px;
            background: var(--color-primary);
            color: var(--color-white);
            border: none;
            border-radius: var(--radius-lg);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(30, 136, 229, 0.3);
        }

        .btn:hover { background: var(--color-primary-hover); transform: translateY(-2px); color: var(--color-white); }

        @media (max-width: 768px) {
            body { padding: 16px; }
            .wizard-box { padding: 40px 28px; }
            h1 { font-size: 24px; }
            .btn { width: 100%; padding: 16px; min-height: 48px; }
        }
    </style>
</head>
<body>
    <div class="wizard-box">
        <div class="progress-bar">
            <div class="progress-step completed">&#10003;</div>
            <div class="progress-line completed"></div>
            <div class="progress-step completed">&#10003;</div>
            <div class="progress-line completed"></div>
            <div class="progress-step completed">&#10003;</div>
        </div>

        <div class="success-icon">&#10003;</div>

        <h1><?= __('All Set!') ?></h1>
        <p class="subtitle">
            <?= __('Your organization is ready to go. You can now manage your monitors, view your status page, and collaborate with your team.') ?>
        </p>

        <a href="<?= $this->Url->build(['controller' => 'Dashboard', 'action' => 'index']) ?>" class="btn">
            <?= __('Go to Dashboard') ?>
        </a>
    </div>
</body>
</html>
