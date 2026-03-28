<?php
/**
 * Super Admin Dashboard
 *
 * @var \App\View\AppView $this
 * @var array $revenue
 * @var array $growth
 * @var array $customers
 * @var array $health
 * @var array $trials
 */
$this->assign('title', __('Super Admin Dashboard'));
?>

<div class="dashboard-header">
    <h1><?= __('Super Admin Dashboard') ?></h1>
    <p><?= __('Platform-wide metrics and analytics') ?></p>
</div>

<!-- Top Row: KPI Cards -->
<div class="summary-grid">
    <div class="summary-card">
        <div class="card-label"><?= __('MRR') ?></div>
        <div class="card-value" style="color: #22c55e;">$<?= number_format($revenue['mrr'], 2) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Total Active Orgs') ?></div>
        <div class="card-value total"><?= number_format($revenue['total_orgs']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Total Monitors') ?></div>
        <div class="card-value" style="color: #3b82f6;"><?= number_format($health['total_monitors']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Active Incidents') ?></div>
        <div class="card-value" style="color: <?= $health['active_incidents'] > 0 ? '#ef4444' : '#22c55e' ?>;">
            <?= number_format($health['active_incidents']) ?>
        </div>
    </div>
</div>

<!-- Second Row: Charts -->
<div class="charts-grid">
    <div class="chart-card">
        <h3><?= __('Plan Distribution') ?></h3>
        <div class="chart-wrapper">
            <canvas id="planDistributionChart"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h3><?= __('New Signups (Last 30 Days)') ?></h3>
        <div class="chart-wrapper">
            <canvas id="signupsChart"></canvas>
        </div>
    </div>
</div>

<!-- Third Row: Tables -->
<div class="tables-grid">
    <!-- Recent Signups -->
    <div class="table-card">
        <h3><?= __('Recent Signups') ?></h3>
        <?php if (!empty($customers['recent_signups'])): ?>
            <table>
                <thead>
                    <tr>
                        <th><?= __('Organization') ?></th>
                        <th><?= __('Plan') ?></th>
                        <th><?= __('Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers['recent_signups'] as $org): ?>
                        <tr>
                            <td><?= h($org->name) ?></td>
                            <td>
                                <?php
                                    $planBadge = match($org->plan) {
                                        'business' => 'badge-gold',
                                        'pro' => 'badge-warning',
                                        default => 'badge-secondary',
                                    };
                                ?>
                                <span class="badge <?= $planBadge ?>"><?= h(ucfirst($org->plan)) ?></span>
                            </td>
                            <td><?= $org->created ? $org->created->nice() : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state"><?= __('No recent signups.') ?></div>
        <?php endif; ?>
    </div>

    <!-- Top Organizations by Monitors -->
    <div class="table-card">
        <h3><?= __('Top Organizations by Monitors') ?></h3>
        <?php if (!empty($customers['top_by_monitors'])): ?>
            <table>
                <thead>
                    <tr>
                        <th><?= __('Organization') ?></th>
                        <th><?= __('Plan') ?></th>
                        <th><?= __('Monitors') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers['top_by_monitors'] as $org): ?>
                        <tr>
                            <td><?= h($org->name) ?></td>
                            <td>
                                <?php
                                    $planBadge = match($org->plan) {
                                        'business' => 'badge-gold',
                                        'pro' => 'badge-warning',
                                        default => 'badge-secondary',
                                    };
                                ?>
                                <span class="badge <?= $planBadge ?>"><?= h(ucfirst($org->plan)) ?></span>
                            </td>
                            <td><?= number_format($org->monitor_count) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state"><?= __('No organizations found.') ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Fourth Row: Platform Health Cards -->
<div class="summary-grid" style="margin-top: 24px;">
    <div class="summary-card">
        <div class="card-label"><?= __('Checks Today') ?></div>
        <div class="card-value" style="color: #3b82f6;"><?= number_format($health['checks_today']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Checks This Week') ?></div>
        <div class="card-value" style="color: #3b82f6;"><?= number_format($health['checks_this_week']) ?></div>
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

<!-- Bottom Row: Trial Metrics -->
<div class="summary-grid" style="margin-top: 24px;">
    <div class="summary-card">
        <div class="card-label"><?= __('Active Trials') ?></div>
        <div class="card-value" style="color: #8b5cf6;"><?= number_format($trials['active_trials']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Trial Conversion Rate') ?></div>
        <div class="card-value" style="color: #22c55e;"><?= $trials['conversion_rate'] ?>%</div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Pass data to JS -->
<script>
    window.superAdminData = {
        planDistribution: <?= json_encode($customers['by_plan']) ?>,
        signupsByDay: <?= json_encode($growth['signups_by_day']) ?>
    };
</script>
<script src="/js/super-admin-charts.js"></script>
