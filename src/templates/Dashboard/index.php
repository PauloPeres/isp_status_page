<?php
/**
 * @var \App\View\AppView $this
 * @var array $summary
 * @var \Cake\Collection\CollectionInterface $activeIncidents
 * @var array $incidentsBySeverity
 * @var array $uptimeData
 * @var array $responseTimeData
 * @var \Cake\Collection\CollectionInterface $recentChecks
 * @var \Cake\Collection\CollectionInterface $recentAlerts
 */
$this->assign('title', __d('dashboard', 'Dashboard'));
?>

<!-- Styles provided by admin.css -->

<div class="dashboard-header">
    <h1><?= __d('dashboard', 'Dashboard') ?></h1>
    <p><?= __d('dashboard', 'System monitoring overview') ?></p>
</div>

<!-- Summary Cards -->
<div class="summary-grid">
    <div class="summary-card">
        <div class="card-label"><?= __d('dashboard', 'Total Monitors') ?></div>
        <div class="card-value total"><?= number_format($summary['total']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __d('dashboard', 'Online') ?></div>
        <div class="card-value up"><?= number_format($summary['up']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __d('dashboard', 'Offline') ?></div>
        <div class="card-value down"><?= number_format($summary['down']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __d('dashboard', 'Degraded') ?></div>
        <div class="card-value degraded"><?= number_format($summary['degraded']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __d('dashboard', 'Unknown') ?></div>
        <div class="card-value unknown"><?= number_format($summary['unknown']) ?></div>
    </div>
</div>

<!-- Active Incidents -->
<div class="incidents-summary">
    <h3><?= __d('dashboard', 'Active Incidents') ?> (<?= $activeIncidents->count() ?>)</h3>
    <div class="severity-badges">
        <?php if ($incidentsBySeverity['critical'] > 0): ?>
            <span class="severity-badge critical"><?= $incidentsBySeverity['critical'] ?> <?= __d('dashboard', 'Critical') ?></span>
        <?php endif; ?>
        <?php if ($incidentsBySeverity['major'] > 0): ?>
            <span class="severity-badge major"><?= $incidentsBySeverity['major'] ?> <?= __d('dashboard', 'Major') ?></span>
        <?php endif; ?>
        <?php if ($incidentsBySeverity['minor'] > 0): ?>
            <span class="severity-badge minor"><?= $incidentsBySeverity['minor'] ?> <?= __d('dashboard', 'Minor') ?></span>
        <?php endif; ?>
        <?php if ($incidentsBySeverity['maintenance'] > 0): ?>
            <span class="severity-badge maintenance"><?= $incidentsBySeverity['maintenance'] ?> <?= __d('dashboard', 'Maintenance') ?></span>
        <?php endif; ?>
        <?php if ($activeIncidents->count() === 0): ?>
            <span style="color: var(--color-success); font-weight: 600;"><?= __d('dashboard', 'No active incidents') ?></span>
        <?php endif; ?>
    </div>
</div>

<!-- Charts -->
<div class="charts-grid">
    <div class="chart-card">
        <h3><?= __d('dashboard', 'Uptime (Last 24h)') ?></h3>
        <div class="chart-wrapper">
            <canvas id="uptimeChart"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h3><?= __d('dashboard', 'Average Response Time (ms)') ?></h3>
        <div class="chart-wrapper">
            <canvas id="responseTimeChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Tables -->
<div class="tables-grid">
    <!-- Recent Checks -->
    <div class="table-card">
        <h3><?= __d('dashboard', 'Recent Checks') ?></h3>
        <?php if ($recentChecks->count() > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th><?= __d('dashboard', 'Monitor') ?></th>
                        <th><?= __d('dashboard', 'Status') ?></th>
                        <th><?= __d('dashboard', 'Time (ms)') ?></th>
                        <th><?= __d('dashboard', 'Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentChecks as $check): ?>
                        <tr>
                            <td><?= h($check->monitor->name ?? 'N/A') ?></td>
                            <td>
                                <?php
                                    $badgeClass = match($check->status) {
                                        'success' => 'badge-success',
                                        'failure', 'error' => 'badge-danger',
                                        'timeout' => 'badge-warning',
                                        default => 'badge-secondary',
                                    };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= h($check->status) ?></span>
                            </td>
                            <td><?= $check->response_time !== null ? number_format($check->response_time) : '-' ?></td>
                            <td><?= $check->checked_at ? h($check->checked_at->format('d/m H:i:s')) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state"><?= __d('dashboard', 'No recent checks.') ?></div>
        <?php endif; ?>
    </div>

    <!-- Recent Alerts -->
    <div class="table-card">
        <h3><?= __d('dashboard', 'Recent Alerts') ?></h3>
        <?php if ($recentAlerts->count() > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th><?= __d('dashboard', 'Monitor') ?></th>
                        <th><?= __d('dashboard', 'Channel') ?></th>
                        <th><?= __d('dashboard', 'Status') ?></th>
                        <th><?= __d('dashboard', 'Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAlerts as $alert): ?>
                        <tr>
                            <td><?= h($alert->monitor->name ?? 'N/A') ?></td>
                            <td><?= h($alert->channel ?? '-') ?></td>
                            <td>
                                <?php
                                    $alertBadge = match($alert->status) {
                                        'sent' => 'badge-success',
                                        'failed' => 'badge-danger',
                                        'queued' => 'badge-warning',
                                        default => 'badge-secondary',
                                    };
                                ?>
                                <span class="badge <?= $alertBadge ?>"><?= h($alert->status) ?></span>
                            </td>
                            <td><?= $alert->created ? h($alert->created->format('d/m H:i:s')) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state"><?= __d('dashboard', 'No recent alerts.') ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Pass data to JS -->
<script>
    window.dashboardData = {
        uptime: <?= json_encode($uptimeData) ?>,
        responseTime: <?= json_encode($responseTimeData) ?>
    };
</script>
<script src="/js/charts.js"></script>
