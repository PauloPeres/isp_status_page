<?php
/**
 * Super Admin — Platform Health Dashboard
 *
 * @var \App\View\AppView $this
 * @var array $health
 * @var array $engagement
 * @var array $typeDistribution
 * @var iterable $failedAlerts
 * @var array $stats
 */
$this->assign('title', __('Platform Health'));
?>

<div class="dashboard-header">
    <h1><?= __('Platform Health') ?></h1>
    <p><?= __('Monitor performance, system status, and user engagement') ?></p>
</div>

<!-- Top Row: Health KPI Cards -->
<div class="summary-grid">
    <div class="summary-card">
        <div class="card-label"><?= __('Total Monitors') ?></div>
        <div class="card-value" style="color: #3b82f6;"><?= number_format($health['total_monitors']) ?></div>
        <div style="color: #999; font-size: 12px; margin-top: 4px;">
            <?= __('Active: {0}', number_format($health['active_monitors'])) ?>
        </div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Checks Today') ?></div>
        <div class="card-value" style="color: #22c55e;"><?= number_format($health['checks_today']) ?></div>
        <div style="color: #999; font-size: 12px; margin-top: 4px;">
            <?= __('This week: {0}', number_format($health['checks_this_week'])) ?>
        </div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Active Incidents') ?></div>
        <div class="card-value" style="color: <?= $health['active_incidents'] > 0 ? '#ef4444' : '#22c55e' ?>;">
            <?= number_format($health['active_incidents']) ?>
        </div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Alerts Today') ?></div>
        <div class="card-value" style="color: #f59e0b;"><?= number_format($health['alerts_today']) ?></div>
    </div>
</div>

<!-- Second Row: Monitor Type Distribution + User Engagement -->
<div class="charts-grid" style="margin-top: 24px;">
    <!-- Monitor Type Distribution -->
    <div class="chart-card">
        <h3><?= __('Monitor Type Distribution') ?></h3>
        <?php if (!empty($typeDistribution)): ?>
            <div class="chart-wrapper">
                <canvas id="typeDistributionChart"></canvas>
            </div>
        <?php else: ?>
            <div class="empty-state"><?= __('No monitors found.') ?></div>
        <?php endif; ?>
    </div>

    <!-- User Engagement -->
    <div class="chart-card">
        <h3><?= __('User Engagement') ?></h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 16px 0;">
            <div style="text-align: center; padding: 16px; background: rgba(59, 130, 246, 0.1); border-radius: 8px;">
                <div style="font-size: 28px; font-weight: 700; color: #3b82f6;"><?= number_format($engagement['dau']) ?></div>
                <div style="color: #999; font-size: 13px; margin-top: 4px;"><?= __('DAU') ?></div>
            </div>
            <div style="text-align: center; padding: 16px; background: rgba(34, 197, 94, 0.1); border-radius: 8px;">
                <div style="font-size: 28px; font-weight: 700; color: #22c55e;"><?= number_format($engagement['wau']) ?></div>
                <div style="color: #999; font-size: 13px; margin-top: 4px;"><?= __('WAU') ?></div>
            </div>
            <div style="text-align: center; padding: 16px; background: rgba(168, 85, 247, 0.1); border-radius: 8px;">
                <div style="font-size: 28px; font-weight: 700; color: #a855f7;"><?= number_format($engagement['mau']) ?></div>
                <div style="color: #999; font-size: 13px; margin-top: 4px;"><?= __('MAU') ?></div>
            </div>
            <div style="text-align: center; padding: 16px; background: rgba(245, 158, 11, 0.1); border-radius: 8px;">
                <div style="font-size: 28px; font-weight: 700; color: #f59e0b;"><?= $engagement['api_adoption_rate'] ?>%</div>
                <div style="color: #999; font-size: 13px; margin-top: 4px;"><?= __('API Adoption') ?></div>
            </div>
        </div>
        <div style="text-align: center; color: #666; font-size: 12px; padding-top: 8px; border-top: 1px solid #333;">
            <?= __('Total Users: {0}', number_format($engagement['total_users'])) ?>
        </div>
    </div>
</div>

<!-- Third Row: Platform Stats -->
<div class="summary-grid" style="margin-top: 24px;">
    <div class="summary-card">
        <div class="card-label"><?= __('Total Organizations') ?></div>
        <div class="card-value total"><?= number_format($stats['total_orgs']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Total Users') ?></div>
        <div class="card-value" style="color: #3b82f6;"><?= number_format($stats['total_users']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Active API Keys') ?></div>
        <div class="card-value" style="color: #a855f7;"><?= number_format($stats['total_api_keys']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Webhook Endpoints') ?></div>
        <div class="card-value" style="color: #f59e0b;"><?= number_format($stats['total_webhook_endpoints']) ?></div>
    </div>
</div>

<!-- Fourth Row: Check Volume + Failed Alerts -->
<div class="tables-grid" style="margin-top: 24px;">
    <!-- Check Volume -->
    <div class="table-card">
        <h3><?= __('Check Volume') ?></h3>
        <table>
            <thead>
                <tr>
                    <th><?= __('Period') ?></th>
                    <th style="text-align: right;"><?= __('Checks') ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= __('Today') ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($health['checks_today']) ?></td>
                </tr>
                <tr>
                    <td><?= __('This Week') ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($health['checks_this_week']) ?></td>
                </tr>
                <tr>
                    <td><?= __('This Month') ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($health['checks_this_month']) ?></td>
                </tr>
                <tr>
                    <td><?= __('Active Monitors') ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= number_format($health['active_monitors']) ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Recent Failed Alerts -->
    <div class="table-card">
        <h3><?= __('Recent Failed Alerts') ?></h3>
        <?php if (count($failedAlerts) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th><?= __('Channel') ?></th>
                        <th><?= __('Error') ?></th>
                        <th><?= __('Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($failedAlerts as $alert): ?>
                        <tr>
                            <td>
                                <span class="badge badge-danger"><?= h($alert->channel ?? 'unknown') ?></span>
                            </td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?= h($alert->error_message ?? $alert->response ?? '-') ?>
                            </td>
                            <td>
                                <?php if ($alert->created): ?>
                                    <?= $alert->created->nice() ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state" style="color: #22c55e;"><?= __('No failed alerts in the last 7 days.') ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Pass data to JS and render chart -->
<script>
(function() {
    var typeData = <?= json_encode($typeDistribution) ?>;
    var labels = Object.keys(typeData);
    var values = Object.values(typeData);

    if (labels.length === 0) return;

    // Color palette for monitor types
    var colors = [
        '#3b82f6', '#22c55e', '#f59e0b', '#ef4444', '#a855f7',
        '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
    ];

    var bgColors = labels.map(function(_, i) {
        return colors[i % colors.length];
    });

    var ctx = document.getElementById('typeDistributionChart');
    if (!ctx) return;

    new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels.map(function(l) {
                return l.charAt(0).toUpperCase() + l.slice(1).replace(/_/g, ' ');
            }),
            datasets: [{
                label: '<?= __('Monitors') ?>',
                data: values,
                backgroundColor: bgColors,
                borderColor: bgColors,
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        color: '#999',
                        stepSize: 1
                    },
                    grid: { color: 'rgba(255,255,255,0.05)' }
                },
                y: {
                    ticks: { color: '#e0e0e0' },
                    grid: { display: false }
                }
            }
        }
    });
})();
</script>
