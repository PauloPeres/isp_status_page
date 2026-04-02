<?php
/**
 * Team Invitation Email Template
 *
 * @var string $acceptUrl
 * @var string $orgName
 * @var string $inviterName
 * @var \App\Model\Entity\Invitation $invitation
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; background: #f5f7fa; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 8px; padding: 40px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #2979FF; font-size: 24px; margin: 0; }
        .content { color: #333; line-height: 1.6; }
        .org-name { color: #2979FF; font-weight: 600; }
        .role { display: inline-block; padding: 4px 12px; background: #E3F2FD; color: #2979FF; border-radius: 12px; font-size: 14px; }
        .btn { display: inline-block; padding: 12px 32px; background: #43A047; color: white; text-decoration: none; border-radius: 8px; font-size: 16px; margin: 20px 0; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1><?= __('Team Invitation') ?></h1>
            </div>
            <div class="content">
                <p><?= __('Hi there,') ?></p>
                <p>
                    <?= __('<strong>{0}</strong> has invited you to join <span class="org-name">{1}</span> as a <span class="role">{2}</span>.', h($inviterName), h($orgName), h(ucfirst($invitation->role))) ?>
                </p>
                <p style="text-align: center;">
                    <a href="<?= h($acceptUrl) ?>" class="btn"><?= __('Accept Invitation') ?></a>
                </p>
                <p style="font-size: 14px; color: #666;">
                    <?= __('This invitation will expire on {0}.', h($invitation->expires_at->format('F j, Y'))) ?>
                </p>
                <p style="font-size: 12px; color: #999;">
                    <?= __('If you did not expect this invitation, you can safely ignore this email.') ?>
                </p>
            </div>
        </div>
        <div class="footer">
            <p><?= __('Sent by {0}', \Cake\Core\Configure::read('Brand.fullName', 'ISP Status Page')) ?></p>
        </div>
    </div>
</body>
</html>
