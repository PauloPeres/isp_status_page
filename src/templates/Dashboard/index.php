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
 * @var array $slaSummary
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
    <div class="summary-card" title="<?= __d('dashboard', 'Services responding but slower than normal') ?>">
        <div class="card-label"><?= __d('dashboard', 'Degraded') ?> <span style="cursor:help;color:#999;font-size:12px;">&#9432;</span></div>
        <div class="card-value degraded"><?= number_format($summary['degraded']) ?></div>
    </div>
    <div class="summary-card" title="<?= __d('dashboard', 'No check data available yet') ?>">
        <div class="card-label"><?= __d('dashboard', 'Unknown') ?> <span style="cursor:help;color:#999;font-size:12px;">&#9432;</span></div>
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

<!-- SLA Status -->
<?php if (!empty($slaSummary) && $slaSummary['total'] > 0): ?>
<div class="sla-dashboard-card">
    <h3><?= __d('dashboard', 'SLA Status') ?></h3>
    <div class="sla-counts">
        <span class="sla-count compliant" title="<?= __d('dashboard', 'Meeting uptime target') ?>">
            <strong><?= $slaSummary['compliant'] ?></strong> <?= __d('dashboard', 'Compliant') ?>
        </span>
        <?php if ($slaSummary['at_risk'] > 0): ?>
            <span class="sla-count at-risk">
                <strong><?= $slaSummary['at_risk'] ?></strong> <?= __d('dashboard', 'At Risk') ?>
            </span>
        <?php endif; ?>
        <?php if ($slaSummary['breached'] > 0): ?>
            <span class="sla-count breached">
                <strong><?= $slaSummary['breached'] ?></strong> <?= __d('dashboard', 'Breached') ?>
            </span>
        <?php endif; ?>
    </div>
    <?php if ($slaSummary['most_at_risk']): ?>
        <div class="sla-at-risk-detail">
            <span style="font-size: 13px; color: var(--color-gray-medium);">
                <?= __d('dashboard', 'Most at risk:') ?>
                <strong><?= h($slaSummary['most_at_risk']['monitor_name']) ?></strong>
                &mdash; <?= number_format($slaSummary['most_at_risk']['remaining_minutes'], 1) ?> min remaining
            </span>
            <?= $this->Html->link(
                __d('dashboard', 'View SLA'),
                ['controller' => 'Sla', 'action' => 'report', $slaSummary['most_at_risk']['sla_id']],
                ['class' => 'btn btn-sm', 'style' => 'padding: 2px 10px; font-size: 12px;']
            ) ?>
        </div>
    <?php endif; ?>
    <div style="margin-top: 8px; text-align: right;">
        <?= $this->Html->link(__d('dashboard', 'View all SLAs'), ['controller' => 'Sla', 'action' => 'index'], ['style' => 'font-size: 13px;']) ?>
    </div>
</div>
<?php endif; ?>

<!-- Charts -->
<div class="charts-grid">
    <div class="chart-card">
        <h3 title="<?= __d('dashboard', 'Percentage of successful checks in the last 24 hours') ?>"><?= __d('dashboard', 'Uptime (Last 24h)') ?> <span style="cursor:help;color:#999;font-size:12px;">&#9432;</span></h3>
        <div class="chart-wrapper">
            <canvas id="uptimeChart"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h3 title="<?= __d('dashboard', 'How fast your service responds — lower is better') ?>"><?= __d('dashboard', 'Average Response Time (ms)') ?> <span style="cursor:help;color:#999;font-size:12px;">&#9432;</span></h3>
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
                            <td><?= $check->checked_at ? $check->checked_at->nice() : '-' ?></td>
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
                            <td><?= $alert->created ? $alert->created->nice() : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state"><?= __d('dashboard', 'No recent alerts.') ?></div>
        <?php endif; ?>
    </div>
</div>

<style>
.sla-dashboard-card {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: 20px 24px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
}
.sla-dashboard-card h3 { margin: 0 0 12px; font-size: 16px; }
.sla-counts { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 12px; }
.sla-count { padding: 6px 14px; border-radius: var(--radius-md); font-size: 14px; }
.sla-count.compliant { background: #E8F5E9; color: #2E7D32; }
.sla-count.at-risk { background: #FFF8E1; color: #F57F17; }
.sla-count.breached { background: #FFEBEE; color: #C62828; }
.sla-at-risk-detail {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; padding: 8px 12px; background: var(--color-gray-light);
    border-radius: var(--radius-sm); flex-wrap: wrap;
}
</style>

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
