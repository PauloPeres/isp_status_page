<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Invitation $invitation
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= __('Accept Invitation') ?> - <?= __('ISP Status') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }
        .invite-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        .invite-card h1 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .invite-card .org-name {
            font-size: 1.25rem;
            color: #1E88E5;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .invite-card .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #e3f2fd;
            color: #1E88E5;
            border-radius: 20px;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }
        .invite-card p {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        .btn-accept {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #43A047;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-accept:hover {
            background: #388E3C;
        }
        .expires {
            font-size: 0.8rem;
            color: #999;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="invite-card">
        <h1><?= __('You\'ve been invited to join') ?></h1>
        <div class="org-name"><?= h($invitation->organization->name) ?></div>
        <div class="role-badge"><?= __('Role: {0}', ucfirst(h($invitation->role))) ?></div>
        <p><?= __('Click the button below to accept the invitation and join the team.') ?></p>

        <?= $this->Form->create(null, ['url' => ['action' => 'accept', $invitation->token]]) ?>
        <?= $this->Form->button(__('Accept Invitation'), ['class' => 'btn-accept']) ?>
        <?= $this->Form->end() ?>

        <p class="expires">
            <?= __('This invitation expires on {0}.', h($invitation->expires_at->format('F j, Y'))) ?>
        </p>
    </div>
</body>
</html>
