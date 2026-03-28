<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 * @var float $uptime
 * @var float $avgResponseTime
 * @var int $totalChecks
 * @var array $responseTimeData
 * @var array $uptimeData
 * @var string $timeRange
 * @var array|null $slaData
 */
$this->assign('title', __d('monitors', 'Monitor Details'));
?>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('Monitors'), 'url' => $this->Url->build(['controller' => 'Monitors', 'action' => 'index'])],
    ['title' => h($monitor->name), 'url' => null],
]]) ?>

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

    <!-- SLA Status (if monitor has an SLA) -->
    <?php if (!empty($slaData)): ?>
        <?php
        $slaStatusBadge = match ($slaData['status']) {
            'compliant' => 'badge-success',
            'at_risk' => 'badge-warning',
            'breached' => 'badge-danger',
            default => 'badge-secondary',
        };
        $slaStatusLabel = match ($slaData['status']) {
            'compliant' => __d('monitors', 'Compliant'),
            'at_risk' => __d('monitors', 'At Risk'),
            'breached' => __d('monitors', 'Breached'),
            default => __d('monitors', 'Unknown'),
        };
        $slaUsage = $slaData['allowed_downtime_minutes'] > 0
            ? min(100, ($slaData['downtime_minutes'] / $slaData['allowed_downtime_minutes']) * 100)
            : 100;
        $slaBarColor = $slaUsage > 90 ? 'var(--color-error)' :
                      ($slaUsage > 70 ? 'var(--color-warning)' : 'var(--color-success)');
        ?>
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
                <span><?= __d('monitors', 'SLA: {0}', h($slaData['sla_name'])) ?></span>
                <span class="badge <?= $slaStatusBadge ?>"><?= $slaStatusLabel ?></span>
            </div>
            <div style="padding: 20px;">
                <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap; margin-bottom: 16px;">
                    <div>
                        <span style="font-size: 13px; color: var(--color-gray-medium);"><?= __d('monitors', 'Target') ?></span><br>
                        <strong style="font-size: 20px;"><?= number_format($slaData['target_uptime'], 3) ?>%</strong>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: var(--color-gray-medium);"><?= __d('monitors', 'Current') ?></span><br>
                        <strong style="font-size: 20px;"><?= number_format($slaData['actual_uptime'], 3) ?>%</strong>
                    </div>
                    <div>
                        <span style="font-size: 13px; color: var(--color-gray-medium);"><?= __d('monitors', 'Remaining Budget') ?></span><br>
                        <strong style="font-size: 20px;"><?= number_format($slaData['remaining_downtime_minutes'], 1) ?> min</strong>
                    </div>
                </div>
                <div style="margin-bottom: 12px;">
                    <div style="width: 100%; height: 10px; background: var(--color-gray-light); border-radius: 5px; overflow: hidden;">
                        <div style="width: <?= number_format($slaUsage, 1) ?>%; height: 100%; background: <?= $slaBarColor ?>; border-radius: 5px; transition: width 0.3s;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--color-gray-medium); margin-top: 4px;">
                        <span><?= number_format($slaData['downtime_minutes'], 1) ?> min used</span>
                        <span><?= number_format($slaData['allowed_downtime_minutes'], 1) ?> min allowed</span>
                    </div>
                </div>
                <?= $this->Html->link(
                    __d('monitors', 'View Full SLA Report'),
                    ['controller' => 'Sla', 'action' => 'report', $slaData['sla_id']],
                    ['class' => 'btn btn-primary', 'style' => 'font-size: 13px; padding: 6px 16px;']
                ) ?>
            </div>
        </div>
    <?php endif; ?>

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

    <!-- Uptime History Bar (P2-011) -->
    <div class="card">
        <div class="card-header">📊 <?= __d('monitors', '30-Day Uptime History') ?></div>
        <div style="padding: 20px;">
            <?= $this->element('monitor/uptime_bar', ['uptimeData' => $uptimeData, 'days' => 30]) ?>
        </div>
    </div>

    <!-- Response Time Graph (P2-003) -->
    <div class="card">
        <div class="card-header">
            <span>📈 <?= __d('monitors', 'Response Time') ?></span>
            <div class="chart-range-buttons">
                <?php
                $ranges = ['24h' => __('24h'), '7d' => __('7d'), '30d' => __('30d')];
                foreach ($ranges as $key => $label):
                ?>
                    <a href="<?= $this->Url->build(['action' => 'view', $monitor->id, '?' => ['range' => $key]]) ?>"
                       class="btn-range<?= $timeRange === $key ? ' active' : '' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="padding: 20px;">
            <canvas id="responseTimeChart" height="80"></canvas>
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
                                <span>⏱️ <?= __d('monitors', 'Duration') ?>: <?php
                                    $d = $incident->duration ?? 0;
                                    if ($d < 60) {
                                        echo "{$d}s";
                                    } elseif ($d < 3600) {
                                        echo floor($d / 60) . 'm ' . ($d % 60) . 's';
                                    } else {
                                        echo floor($d / 3600) . 'h ' . floor(($d % 3600) / 60) . 'm';
                                    }
                                ?></span>
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
        padding: 16px;
    }

    .status-icon {
        font-size: 48px;
    }

    .status-content h2 {
        font-size: 24px;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stat-icon {
        font-size: 28px;
    }

    .stat-value {
        font-size: 22px;
    }

    .stat-card {
        padding: 14px;
        gap: 10px;
    }

    .details-grid {
        grid-template-columns: 1fr;
        padding: 16px;
    }

    .detail-item code {
        word-break: break-all;
    }

    .actions-grid {
        flex-direction: column;
        padding: 16px;
    }

    .actions-grid .btn {
        width: 100%;
        min-height: 44px;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    /* Prevent chart canvas overflow */
    .card canvas {
        max-width: 100%;
    }

    .chart-range-buttons {
        width: 100%;
        justify-content: flex-end;
    }

    .btn-range {
        min-height: 36px;
        display: inline-flex;
        align-items: center;
    }

    .incidents-list {
        padding: 16px;
    }

    .incident-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .incident-meta {
        flex-direction: column;
        gap: 6px;
        font-size: 12px;
    }

    .table-responsive {
        margin: 0 -16px;
        padding: 0 16px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Chart range buttons */
.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}

.chart-range-buttons {
    display: flex;
    gap: 4px;
}

.btn-range {
    padding: 4px 12px;
    border-radius: var(--radius-sm);
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    color: var(--color-gray-medium);
    background: var(--color-gray-light);
    transition: all 0.2s;
}

.btn-range:hover {
    background: var(--color-primary-light);
    color: var(--color-primary);
}

.btn-range.active {
    background: var(--color-primary);
    color: var(--color-white);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rawData = <?= json_encode($responseTimeData) ?>;

    if (rawData.length === 0) {
        document.getElementById('responseTimeChart').parentElement.innerHTML =
            '<p style="text-align:center;color:#999;padding:40px 0;">No response time data available for this period.</p>';
        return;
    }

    const labels = rawData.map(d => d.time);
    const values = rawData.map(d => d.value);
    const pointColors = rawData.map(d => d.status === 'success' ? '#22c55e' : '#ef4444');
    const pointRadius = rawData.length > 200 ? 0 : 3;

    const ctx = document.getElementById('responseTimeChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Response Time (ms)',
                data: values,
                borderColor: '#1E88E5',
                backgroundColor: 'rgba(30, 136, 229, 0.1)',
                fill: true,
                tension: 0.3,
                pointBackgroundColor: pointColors,
                pointBorderColor: pointColors,
                pointRadius: pointRadius,
                pointHoverRadius: 6,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: function(items) {
                            return items[0].label;
                        },
                        label: function(context) {
                            const idx = context.dataIndex;
                            const status = rawData[idx].status === 'success' ? 'OK' : 'FAIL';
                            const val = context.parsed.y !== null ? context.parsed.y + ' ms' : 'N/A';
                            return status + ': ' + val;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'ms' },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    title: { display: true, text: 'Time' },
                    grid: { display: false },
                    ticks: {
                        maxTicksLimit: 12,
                        maxRotation: 0
                    }
                }
            }
        }
    });
});
</script>
