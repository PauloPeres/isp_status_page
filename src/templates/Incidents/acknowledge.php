<?php
/**
 * Public Incident Acknowledgement Page
 *
 * Displayed when a user clicks the acknowledge link from an email.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Incident|null $incident
 * @var string|null $error
 * @var bool|null $success
 * @var bool|null $alreadyAcknowledged
 */
$this->assign('title', __d('incidents', 'Incident Acknowledgement'));
?>

<style>
    .ack-container {
        max-width: 600px;
        margin: 40px auto;
        padding: 0 20px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .ack-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    .ack-header {
        padding: 24px;
        text-align: center;
        color: white;
        font-size: 20px;
        font-weight: 600;
    }

    .ack-header.success { background: #43A047; }
    .ack-header.error { background: #E53935; }
    .ack-header.warning { background: #FDD835; color: #333; }
    .ack-header.info { background: #2979FF; }

    .ack-body {
        padding: 32px 24px;
        text-align: center;
    }

    .ack-body p {
        font-size: 16px;
        color: #555;
        margin: 12px 0;
        line-height: 1.6;
    }

    .ack-incident-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 16px;
        margin: 20px 0;
        text-align: left;
    }

    .ack-incident-info dt {
        font-size: 12px;
        color: #888;
        text-transform: uppercase;
        font-weight: 600;
        margin-top: 12px;
    }

    .ack-incident-info dt:first-child {
        margin-top: 0;
    }

    .ack-incident-info dd {
        font-size: 15px;
        color: #333;
        margin: 4px 0 0 0;
        font-weight: 500;
    }
</style>

<div class="ack-container">
    <div class="ack-card">
        <?php if (!empty($error)): ?>
            <div class="ack-header error"><?= __d('incidents', 'Error') ?></div>
            <div class="ack-body">
                <p><?= h($error) ?></p>
            </div>
        <?php elseif (!empty($alreadyAcknowledged) && $incident): ?>
            <div class="ack-header warning"><?= __d('incidents', 'Already Acknowledged') ?></div>
            <div class="ack-body">
                <p><?= __d('incidents', 'This incident has already been acknowledged.') ?></p>
                <?php if ($incident->acknowledged_at): ?>
                    <p style="font-size: 14px; color: #888;">
                        <?= __d('incidents', 'Acknowledged at {0}', h($incident->acknowledged_at->nice())) ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php elseif (!empty($success) && $incident): ?>
            <div class="ack-header success"><?= __d('incidents', 'Incident Acknowledged') ?></div>
            <div class="ack-body">
                <p><?= __d('incidents', 'The incident has been successfully acknowledged. Thank you!') ?></p>
                <div class="ack-incident-info">
                    <dl>
                        <dt><?= __d('incidents', 'Monitor') ?></dt>
                        <dd><?= h($incident->monitor->name) ?></dd>

                        <dt><?= __d('incidents', 'Incident') ?></dt>
                        <dd><?= h($incident->title) ?></dd>

                        <dt><?= __d('incidents', 'Severity') ?></dt>
                        <dd><?= h(ucfirst($incident->severity)) ?></dd>

                        <dt><?= __d('incidents', 'Acknowledged at') ?></dt>
                        <dd><?= $incident->acknowledged_at ? h($incident->acknowledged_at->nice()) : '-' ?></dd>
                    </dl>
                </div>
                <p style="font-size: 14px; color: #888;">
                    <?= __d('incidents', 'Other team members have been notified.') ?>
                </p>
            </div>
        <?php else: ?>
            <div class="ack-header info"><?= __d('incidents', 'Incident Acknowledgement') ?></div>
            <div class="ack-body">
                <p><?= __d('incidents', 'No information available.') ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
