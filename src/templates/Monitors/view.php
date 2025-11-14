<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 * @var float $uptime
 * @var float $avgResponseTime
 * @var int $totalChecks
 */
$this->assign('title', __d('monitors', 'Monitor Details'));
?>

<div class="monitors-view">
    <div class="page-header">
        <div>
            <h1>🖥️ <?= h($monitor->name) ?></h1>
            <p><?= h($monitor->description) ?: __d('monitors', 'Service monitor') ?></p>
        </div>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(
                __('Edit'),
                ['action' => 'edit', $monitor->id],
                ['class' => 'btn btn-primary']
            ) ?>
            <?= $this->Html->link(
                __('Back'),
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>
    </div>

    <!-- Status Overview -->
    <div class="status-overview">
        <div class="status-card <?= $monitor->status ?>">
            <div class="status-icon">
                <?= $monitor->status === 'up' ? '🟢' : ($monitor->status === 'down' ? '🔴' : '⚪') ?>
            </div>
            <div class="status-content">
                <h2><?= h(ucfirst($monitor->status)) ?></h2>
                <p><?= $monitor->active ? __d('monitors', 'Monitoring active') : __d('monitors', 'Monitoring paused') ?></p>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($uptime, 2) ?>%</div>
                <div class="stat-label"><?= __d('monitors', 'Uptime (24h)') ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-content">
                <div class="stat-value"><?= $avgResponseTime ? number_format($avgResponseTime) . 'ms' : '-' ?></div>
                <div class="stat-label"><?= __d('monitors', 'Average Time') ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔍</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($totalChecks) ?></div>
                <div class="stat-label"><?= __d('monitors', 'Checks (24h)') ?></div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🕐</div>
            <div class="stat-content">
                <div class="stat-value"><?= $monitor->interval ?>s</div>
                <div class="stat-label"><?= __d('monitors', 'Interval') ?></div>
            </div>
        </div>
    </div>

    <!-- Monitor Details -->
    <div class="card">
        <div class="card-header">📋 <?= __d('monitors', 'Monitor Details') ?></div>
        <div class="details-grid">
            <div class="detail-item">
                <span class="detail-label"><?= __d('monitors', 'Type') ?>:</span>
                <span class="badge badge-info"><?= h(strtoupper($monitor->type)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __d('monitors', 'Target') ?>:</span>
                <code><?= h($monitor->target) ?></code>
            </div>

            <?php if ($monitor->type === 'http' && $monitor->expected_status_code): ?>
                <div class="detail-item">
                    <span class="detail-label"><?= __d('monitors', 'Expected HTTP Status') ?>:</span>
                    <code><?= h($monitor->expected_status_code) ?></code>
                </div>
            <?php endif; ?>

            <?php if ($monitor->type === 'port' && $monitor->port): ?>
                <div class="detail-item">
                    <span class="detail-label"><?= __d('monitors', 'Port') ?>:</span>
                    <code><?= h($monitor->port) ?></code>
                </div>
            <?php endif; ?>

            <div class="detail-item">
                <span class="detail-label"><?= __d('monitors', 'Interval') ?>:</span>
                <span><?= __d('monitors', '{0} seconds', $monitor->interval) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __d('monitors', 'Timeout') ?>:</span>
                <span><?= __d('monitors', '{0} seconds', $monitor->timeout) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __d('monitors', 'Last Check') ?>:</span>
                <span>
                    <?php if ($monitor->last_check_at): ?>
                        <span class="local-datetime" data-utc="<?= $monitor->last_check_at->format('c') ?>"></span>
                    <?php else: ?>
                        <?= __d('monitors', 'Never') ?>
                    <?php endif; ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __d('monitors', 'Response Time') ?>:</span>
                <span><?= $monitor->response_time ? number_format($monitor->response_time) . ' ms' : '-' ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __('Created') ?>:</span>
                <span class="local-datetime" data-utc="<?= $monitor->created->format('c') ?>"></span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __('Last Updated') ?>:</span>
                <span class="local-datetime" data-utc="<?= $monitor->modified->format('c') ?>"></span>
            </div>
        </div>
    </div>

    <!-- Recent Checks -->
    <?php if ($monitor->monitor_checks && count($monitor->monitor_checks) > 0): ?>
        <div class="card">
            <div class="card-header">
                <span>📊 <?= __d('monitors', 'Recent Checks') ?></span>
                <span class="badge badge-info"><?= __d('monitors', '{0} records', count($monitor->monitor_checks)) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= __('Status') ?></th>
                            <th><?= __('Date/Time') ?></th>
                            <th><?= __d('monitors', 'Response Time') ?></th>
                            <th><?= __d('monitors', 'Status Code') ?></th>
                            <th><?= __d('monitors', 'Error Message') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monitor->monitor_checks as $check): ?>
                            <tr>
                                <td>
                                    <span class="status-indicator status-<?= h($check->status) ?>"></span>
                                </td>
                                <td><span class="local-datetime" data-utc="<?= $check->created->format('c') ?>"></span></td>
                                <td>
                                    <?php if ($check->response_time): ?>
                                        <span class="<?= $check->response_time > 1000 ? 'text-error' : ($check->response_time > 500 ? 'text-warning' : 'text-success') ?>">
                                            <?= number_format($check->response_time) ?> ms
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $check->status_code ? h($check->status_code) : '-' ?>
                                </td>
                                <td>
                                    <?php if ($check->error_message): ?>
                                        <span class="text-error" title="<?= h($check->error_message) ?>">
                                            <?= h(mb_substr($check->error_message, 0, 50)) ?>
                                            <?= mb_strlen($check->error_message) > 50 ? '...' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-success">✓ OK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="empty-state">
                <div class="empty-state-icon">📊</div>
                <p><?= __d('monitors', 'No checks performed yet.') ?></p>
                <small class="text-muted"><?= __d('monitors', 'Checks will start automatically if the monitor is active.') ?></small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Incidents -->
    <?php if ($monitor->incidents && count($monitor->incidents) > 0): ?>
        <div class="card">
            <div class="card-header">
                <span>🚨 <?= __d('monitors', 'Recent Incidents') ?></span>
                <span class="badge badge-error"><?= __d('monitors', '{0} records', count($monitor->incidents)) ?></span>
            </div>
            <div class="incidents-list">
                <?php foreach ($monitor->incidents as $incident): ?>
                    <div class="incident-item">
                        <div class="incident-header">
                            <h4><?= h($incident->title) ?></h4>
                            <span class="badge badge-<?= $incident->status === 'resolved' ? 'success' : 'error' ?>">
                                <?= h($incident->status) ?>
                            </span>
                        </div>
                        <p><?= h($incident->description) ?></p>
                        <div class="incident-meta">
                            <span>📅 <?= __d('monitors', 'Started') ?>: <span class="local-datetime" data-utc="<?= $incident->started_at->format('c') ?>"></span></span>
                            <?php if ($incident->resolved_at): ?>
                                <span>✅ <?= __d('monitors', 'Resolved') ?>: <span class="local-datetime" data-utc="<?= $incident->resolved_at->format('c') ?>"></span></span>
                                <span>⏱️ <?= __d('monitors', 'Duration') ?>: <?= gmdate('H:i:s', $incident->duration ?? 0) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="card">
        <div class="card-header">⚙️ <?= __('Actions') ?></div>
        <div class="actions-grid">
            <?= $this->Form->postLink(
                $monitor->active ? __d('monitors', 'Pause Monitoring') : __d('monitors', 'Activate Monitoring'),
                ['action' => 'toggle', $monitor->id],
                [
                    'class' => 'btn ' . ($monitor->active ? 'btn-secondary' : 'btn-success'),
                    'confirm' => __d('monitors', 'Are you sure you want to {0} this monitor?', $monitor->active ? __d('monitors', 'pause') : __d('monitors', 'activate'))
                ]
            ) ?>

            <?= $this->Html->link(
                __d('monitors', 'Edit Settings'),
                ['action' => 'edit', $monitor->id],
                ['class' => 'btn btn-primary']
            ) ?>

            <?= $this->Form->postLink(
                __d('monitors', 'Delete Monitor'),
                ['action' => 'delete', $monitor->id],
                [
                    'class' => 'btn btn-error',
                    'confirm' => __d('monitors', 'Are you sure you want to delete this monitor? This action cannot be undone and all historical data will be lost.')
                ]
            ) ?>
        </div>
    </div>
</div>

<style>
.status-overview {
    margin-bottom: 24px;
}

.status-card {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: center;
    gap: 24px;
    border-left: 4px solid var(--color-gray-medium);
}

.status-card.up {
    border-left-color: var(--color-success);
    background: linear-gradient(135deg, #ffffff 0%, #E8F5E9 100%);
}

.status-card.down {
    border-left-color: var(--color-error);
    background: linear-gradient(135deg, #ffffff 0%, #FFEBEE 100%);
}

.status-icon {
    font-size: 64px;
}

.status-content h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 4px;
}

.status-content p {
    color: var(--color-gray-medium);
    font-size: 15px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--color-white);
    padding: 20px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    font-size: 40px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--color-dark);
}

.stat-label {
    font-size: 13px;
    color: var(--color-gray-medium);
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
    padding: 24px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--color-gray-medium);
}

.incidents-list {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.incident-item {
    padding: 16px;
    border-radius: var(--radius-md);
    background: var(--color-gray-light);
}

.incident-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.incident-header h4 {
    margin: 0;
    font-size: 16px;
}

.incident-meta {
    margin-top: 12px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 13px;
    color: var(--color-gray-medium);
}

.actions-grid {
    padding: 24px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.text-success {
    color: var(--color-success);
}

.text-warning {
    color: var(--color-warning);
}

.text-error {
    color: var(--color-error);
}

@media (max-width: 768px) {
    .status-card {
        flex-direction: column;
        text-align: center;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .actions-grid {
        flex-direction: column;
    }

    .actions-grid .btn {
        width: 100%;
    }
}
</style>
