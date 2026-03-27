<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Invite Your Team') ?> - ISP Status</title>
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
            color: var(--color-gray-medium);
            background: var(--color-gray-light);
        }

        .progress-step.active { background: var(--color-primary); color: var(--color-white); }
        .progress-step.completed { background: var(--color-success); color: var(--color-white); }
        .progress-line { width: 60px; height: 3px; background: var(--color-gray-light); }
        .progress-line.completed { background: var(--color-success); }

        h1 { color: var(--color-dark); font-size: 24px; font-weight: 700; text-align: center; margin-bottom: 8px; }
        .subtitle { color: var(--color-gray-medium); font-size: 15px; text-align: center; margin-bottom: 32px; }

        .invite-row {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            align-items: flex-end;
        }

        .invite-row .email-field { flex: 1; }
        .invite-row .role-field { width: 130px; }

        label {
            display: block;
            color: var(--color-dark);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input, select {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid var(--color-gray-light);
            border-radius: var(--radius-lg);
            font-size: 15px;
            color: var(--color-dark);
            background: var(--color-white);
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
        }

        .add-more {
            background: none;
            border: 2px dashed var(--color-gray-light);
            border-radius: var(--radius-lg);
            padding: 12px;
            width: 100%;
            color: var(--color-primary);
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }

        .add-more:hover { border-color: var(--color-primary); background: rgba(30, 136, 229, 0.05); }

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

        .btn:hover { background: var(--color-primary-hover); transform: translateY(-2px); }

        .skip-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: var(--color-gray-medium);
            text-decoration: none;
            font-size: 14px;
        }

        .skip-link:hover { color: var(--color-primary); text-decoration: underline; }

        .message { padding: 14px 18px; border-radius: var(--radius-md); margin-bottom: 20px; font-size: 14px; }
        .message.success { background: #E8F5E9; color: #2E7D32; border-left: 4px solid var(--color-success); }

        @media (max-width: 768px) {
            body { padding: 16px; }
            .wizard-box { padding: 40px 28px; }
            h1 { font-size: 22px; }
            .invite-row { flex-direction: column; gap: 8px; }
            .invite-row .role-field { width: 100%; }
            input, select { padding: 14px 16px; font-size: 16px; min-height: 48px; }
            .btn { padding: 16px; font-size: 16px; min-height: 48px; }
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
            <div class="progress-step active">3</div>
        </div>

        <h1><?= __('Invite Your Team') ?></h1>
        <p class="subtitle"><?= __('Collaborate with your team members (optional)') ?></p>

        <?= $this->Flash->render() ?>

        <form method="post" action="<?= $this->Url->build(['controller' => 'Onboarding', 'action' => 'step3']) ?>" id="inviteForm">
            <?php if (isset($this->request)): ?>
                <input type="hidden" name="_csrfToken" value="<?= $this->request->getAttribute('csrfToken') ?>">
            <?php endif; ?>

            <div id="inviteRows">
                <div class="invite-row">
                    <div class="email-field">
                        <label for="email_0"><?= __('Email Address') ?></label>
                        <input type="email" id="email_0" name="emails[0]" placeholder="colleague@example.com">
                    </div>
                    <div class="role-field">
                        <label for="role_0"><?= __('Role') ?></label>
                        <select id="role_0" name="roles[0]">
                            <option value="admin"><?= __('Admin') ?></option>
                            <option value="member" selected><?= __('Member') ?></option>
                            <option value="viewer"><?= __('Viewer') ?></option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="button" class="add-more" onclick="addInviteRow()">
                + <?= __('Add another team member') ?>
            </button>

            <button type="submit" class="btn"><?= __('Send Invitations & Finish') ?></button>
        </form>

        <a href="<?= $this->Url->build(['controller' => 'Onboarding', 'action' => 'complete']) ?>" class="skip-link">
            <?= __('Skip, I will invite later') ?>
        </a>
    </div>

    <script>
    var inviteCount = 1;
    function addInviteRow() {
        var container = document.getElementById('inviteRows');
        var row = document.createElement('div');
        row.className = 'invite-row';
        row.innerHTML =
            '<div class="email-field">' +
            '<input type="email" name="emails[' + inviteCount + ']" placeholder="colleague@example.com">' +
            '</div>' +
            '<div class="role-field">' +
            '<select name="roles[' + inviteCount + ']">' +
            '<option value="admin"><?= __('Admin') ?></option>' +
            '<option value="member" selected><?= __('Member') ?></option>' +
            '<option value="viewer"><?= __('Viewer') ?></option>' +
            '</select>' +
            '</div>';
        container.appendChild(row);
        inviteCount++;
    }
    </script>
</body>
</html>
