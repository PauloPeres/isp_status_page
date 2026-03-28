<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Incident $incident
 * @var array $timeline
 * @var \Cake\Collection\CollectionInterface $recentChecks
 */
$this->assign('title', __d('incidents', 'Incident Details'));
?>

<style>
    .incident-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .back-link {
        color: #3b82f6;
        text-decoration: none;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    .header-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        border: none;
        cursor: pointer;
        display: inline-block;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .btn-primary {
        background: #f59e0b;
        color: white;
    }

    .btn-primary:hover {
        background: #d97706;
    }

    .btn-success {
        background: #22c55e;
        color: white;
    }

    .btn-success:hover {
        background: #16a34a;
    }

    .status-banner {
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 16px;
        font-weight: 600;
    }

    .status-banner.success {
        background: #dcfce7;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }

    .status-banner.danger {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .incident-details {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .incident-details h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: #333;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .detail-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        font-weight: 600;
    }

    .detail-value {
        font-size: 16px;
        color: #333;
        font-weight: 500;
    }

    .detail-value a {
        color: #3b82f6;
        text-decoration: none;
    }

    .detail-value a:hover {
        text-decoration: underline;
    }

    .monitor-target {
        font-size: 13px;
        color: #666;
        margin-top: 4px;
        font-family: 'Courier New', monospace;
    }

    .time-ago {
        font-size: 13px;
        color: #999;
        margin-top: 4px;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .badge-lg {
        font-size: 14px;
        padding: 8px 16px;
    }

    .badge-success {
        background: #dcfce7;
        color: #16a34a;
    }

    .badge-danger {
        background: #fee2e2;
        color: #dc2626;
    }

    .badge-warning {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-info {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .badge-secondary {
        background: #f3f4f6;
        color: #6b7280;
    }

    .description-section {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid #e0e0e0;
    }

    .description-box {
        background: #f8f9fa;
        padding: 16px;
        border-radius: 6px;
        margin-top: 8px;
        white-space: pre-wrap;
        font-size: 14px;
        line-height: 1.6;
        color: #333;
        border-left: 4px solid #3b82f6;
    }

    .timeline-section {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .timeline-section h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: #333;
    }

    .timeline {
        position: relative;
        padding-left: 40px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e0e0e0;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 32px;
    }

    .timeline-item:last-child {
        padding-bottom: 0;
    }

    .timeline-marker {
        position: absolute;
        left: -40px;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        border: 3px solid #999;
        z-index: 1;
    }

    .timeline-danger .timeline-marker {
        border-color: #dc2626;
        background: #fee2e2;
    }

    .timeline-warning .timeline-marker {
        border-color: #f59e0b;
        background: #fef3c7;
    }

    .timeline-success .timeline-marker {
        border-color: #22c55e;
        background: #dcfce7;
    }

    .timeline-info .timeline-marker {
        border-color: #1E88E5;
        background: #dbeafe;
    }

    .timeline-icon {
        font-size: 16px;
    }

    .timeline-content {
        background: white;
        padding: 16px;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }

    .timeline-time {
        font-size: 13px;
        color: #666;
        margin-bottom: 8px;
    }

    .timeline-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }

    .timeline-description {
        font-size: 14px;
        color: #666;
    }

    .checks-section {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .checks-section h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: #333;
    }

    .checks-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 12px;
    }

    .check-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 12px;
        border-radius: 8px;
        border: 2px solid #e0e0e0;
        background: white;
        transition: transform 0.2s;
    }

    .check-item:hover {
        transform: translateY(-2px);
    }

    .check-item.check-success {
        border-color: #bbf7d0;
        background: #dcfce7;
    }

    .check-item.check-failed {
        border-color: #fecaca;
        background: #fee2e2;
    }

    .check-status {
        font-size: 24px;
        margin-bottom: 8px;
    }

    .check-details {
        text-align: center;
    }

    .check-time {
        font-size: 13px;
        font-weight: 600;
        color: #333;
    }

    .check-response-time {
        font-size: 12px;
        color: #666;
        margin-top: 4px;
        font-family: 'Courier New', monospace;
    }

    @media (max-width: 768px) {
        .details-grid {
            grid-template-columns: 1fr;
        }

        .incident-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .header-actions {
            flex-direction: column;
            width: 100%;
        }

        .header-actions .btn {
            width: 100%;
            min-height: 44px;
            text-align: center;
        }

        .checks-grid {
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        }
    }
</style>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('Incidents'), 'url' => $this->Url->build(['controller' => 'Incidents', 'action' => 'index'])],
    ['title' => h($incident->title), 'url' => null],
]]) ?>

<div class="incident-header">
    <div>
        <h2>🚨 <?= h($incident->title) ?></h2>
        <?= $this->Html->link(__d('incidents', '← Back to Incidents'), ['action' => 'index'], ['class' => 'back-link']) ?>
    </div>
    <div class="header-actions">
        <?php if (!$incident->isResolved()): ?>
            <?php if (!$incident->isAcknowledged()): ?>
                <?= $this->Form->postLink(
                    __d('incidents', 'Acknowledge'),
                    ['action' => 'acknowledgeAdmin', $incident->id],
                    [
                        'class' => 'btn btn-primary',
                        'confirm' => __d('incidents', 'Acknowledge this incident?')
                    ]
                ) ?>
            <?php endif; ?>
            <?= $this->Html->link(__d('incidents', 'Edit'), ['action' => 'edit', $incident->id], ['class' => 'btn btn-primary']) ?>
            <?= $this->Form->postLink(
                __d('incidents', 'Resolve'),
                ['action' => 'resolve', $incident->id],
                [
                    'class' => 'btn btn-success',
                    'confirm' => __d('incidents', 'Are you sure you want to resolve this incident?')
                ]
            ) ?>
        <?php endif; ?>
    </div>
</div>

<!-- Status Banner -->
<div class="status-banner <?= $incident->isResolved() ? 'success' : 'danger' ?>">
    <span style="font-size: 24px;"><?= $incident->isResolved() ? '✅' : '⚠️' ?></span>
    <span>
        <?= $incident->isResolved() ? __d('incidents', 'Incident resolved') : __d('incidents', 'Incident active') ?>
        - <?= h($incident->getStatusName()) ?>
    </span>
</div>

<!-- Main Information -->
<div class="incident-details">
    <h3>📋 <?= __d('incidents', 'Main Information') ?></h3>

    <div class="details-grid">
        <div class="detail-item">
            <span class="detail-label"><?= __d('incidents', 'Status') ?></span>
            <span class="detail-value">
                <span class="badge badge-<?= $incident->isResolved() ? 'success' : 'danger' ?> badge-lg">
                    <?= $incident->isResolved() ? '✅' : '⚠️' ?>
                    <?= h($incident->getStatusName()) ?>
                </span>
            </span>
        </div>

        <div class="detail-item">
            <span class="detail-label"><?= __d('incidents', 'Severity') ?></span>
            <span class="detail-value">
                <span class="badge badge-<?= $incident->getSeverityBadgeClass() ?> badge-lg">
                    <?= h(ucfirst($incident->severity)) ?>
                </span>
            </span>
        </div>

        <div class="detail-item">
            <span class="detail-label"><?= __d('incidents', 'Affected Monitor') ?></span>
            <span class="detail-value">
                <?= $this->Html->link(
                    h($incident->monitor->name),
                    ['controller' => 'Monitors', 'action' => 'view', $incident->monitor->id]
                ) ?>
                <div class="monitor-target"><?= h($incident->monitor->target) ?></div>
            </span>
        </div>

        <div class="detail-item">
            <span class="detail-label"><?= __d('incidents', 'Creation') ?></span>
            <span class="detail-value">
                <?= $incident->auto_created ? __d('incidents', '🤖 Auto-created') : __d('incidents', '👤 Manual') ?>
            </span>
        </div>

        <div class="detail-item">
            <span class="detail-label"><?= __d('incidents', 'Started at') ?></span>
            <span class="detail-value">
                <?= h($incident->started_at->nice()) ?>
                <div class="time-ago">(<?= h($incident->started_at->timeAgoInWords()) ?>)</div>
            </span>
        </div>

        <?php if ($incident->identified_at): ?>
            <div class="detail-item">
                <span class="detail-label"><?= __d('incidents', 'Identified at') ?></span>
                <span class="detail-value">
                    <?= h($incident->identified_at->nice()) ?>
                    <div class="time-ago">(<?= h($incident->identified_at->timeAgoInWords()) ?>)</div>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($incident->resolved_at): ?>
            <div class="detail-item">
                <span class="detail-label"><?= __d('incidents', 'Resolved at') ?></span>
                <span class="detail-value">
                    <?= h($incident->resolved_at->nice()) ?>
                    <div class="time-ago">(<?= h($incident->resolved_at->timeAgoInWords()) ?>)</div>
                </span>
            </div>
        <?php endif; ?>

        <div class="detail-item">
            <span class="detail-label"><?= __d('incidents', 'Duration') ?></span>
            <span class="detail-value">
                <?php if ($incident->duration !== null): ?>
                    <strong style="font-family: 'Courier New', monospace; color: #333;">
                        <?php
                        $duration = $incident->duration;
                        if ($duration < 60) {
                            echo "{$duration} " . __d('incidents', 'seconds');
                        } elseif ($duration < 3600) {
                            $minutes = floor($duration / 60);
                            $seconds = $duration % 60;
                            echo $seconds > 0 ? "{$minutes}m {$seconds}s" : "{$minutes} " . __d('incidents', 'minutes');
                        } else {
                            $hours = floor($duration / 3600);
                            $minutes = floor(($duration % 3600) / 60);
                            if ($hours < 24) {
                                echo $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours} " . __d('incidents', 'hours');
                            } else {
                                $days = floor($hours / 24);
                                $remainingHours = $hours % 24;
                                echo $remainingHours > 0 ? "{$days}d {$remainingHours}h" : "{$days} " . __d('incidents', 'days');
                            }
                        }
                        ?>
                    </strong>
                <?php else: ?>
                    <span style="color: #999;">
                        <?= $incident->isResolved() ? __d('incidents', 'N/A') : __d('incidents', '⏱️ In progress...') ?>
                    </span>
                <?php endif; ?>
            </span>
        </div>
        <div class="detail-item">
            <span class="detail-label"><?= __d('incidents', 'Acknowledgement') ?></span>
            <span class="detail-value">
                <?php if ($incident->isAcknowledged()): ?>
                    <?= $this->element('incidents/acknowledge_badge', ['incident' => $incident]) ?>
                    <?php if ($incident->acknowledged_by_user): ?>
                        <div style="font-size: 13px; color: #666; margin-top: 4px;">
                            <?= __d('incidents', 'By: {0}', h($incident->acknowledged_by_user->username)) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($incident->acknowledged_at): ?>
                        <div class="time-ago">
                            <?= h($incident->acknowledged_at->nice()) ?>
                            (<?= h($incident->acknowledged_at->timeAgoInWords()) ?>)
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if ($incident->isOngoing()): ?>
                        <span class="badge badge-warning">&#x23F3; <?= __d('incidents', 'Awaiting acknowledgement') ?></span>
                    <?php else: ?>
                        <span style="color: #999;"><?= __d('incidents', 'N/A') ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <?php if ($incident->description): ?>
        <div class="description-section">
            <div class="detail-label"><?= __d('incidents', 'Description') ?></div>
            <div class="description-box">
                <?= h($incident->description) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Timeline -->
<div class="timeline-section">
    <h3>⏱️ <?= __d('incidents', 'Event Timeline') ?></h3>
    <div class="timeline">
        <?php foreach ($timeline as $event): ?>
            <div class="timeline-item timeline-<?= h($event['color']) ?>">
                <div class="timeline-marker">
                    <span class="timeline-icon"><?= $event['icon'] ?></span>
                </div>
                <div class="timeline-content">
                    <div class="timeline-time">
                        <?= h($event['timestamp']->nice()) ?>
                        <span style="color: #999;">(<?= h($event['timestamp']->timeAgoInWords()) ?>)</span>
                    </div>
                    <div class="timeline-title"><?= h($event['title']) ?></div>
                    <div class="timeline-description"><?= h($event['description']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent Monitor Checks -->
<?php if ($recentChecks->count() > 0): ?>
    <div class="checks-section">
        <h3>📊 <?= __d('incidents', 'Recent Monitor Checks') ?></h3>
        <div class="checks-grid">
            <?php foreach ($recentChecks as $check): ?>
                <div class="check-item check-<?= h($check->status) ?>">
                    <div class="check-status">
                        <?= $check->status === 'success' ? '✅' : '❌' ?>
                    </div>
                    <div class="check-details">
                        <div class="check-time">
                            <?= h($check->checked_at->i18nFormat('HH:mm:ss')) ?>
                        </div>
                        <?php if ($check->response_time): ?>
                            <div class="check-response-time">
                                <?= number_format($check->response_time) ?>ms
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<div style="margin-top: 24px;">
    <?= $this->Html->link(__d('incidents', '← Back to Incidents'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
    <?= $this->Html->link(__d('incidents', 'View Monitor'), ['controller' => 'Monitors', 'action' => 'view', $incident->monitor->id], [
        'class' => 'btn btn-primary'
    ]) ?>
</div>
