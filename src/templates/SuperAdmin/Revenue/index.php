<?php
/**
 * Super Admin Revenue Dashboard
 *
 * @var \App\View\AppView $this
 * @var array $revenue
 * @var array $trials
 * @var array $growth
 * @var array $organizations
 */
$this->assign('title', __('Revenue Dashboard'));

$planPrices = [
    'pro' => $revenue['paid_orgs'] > 0 ? $revenue['mrr'] / $revenue['paid_orgs'] : 0,
    'business' => 0,
];
?>

<div class="dashboard-header">
    <h1><?= __('Revenue Dashboard') ?></h1>
    <p><?= __('Revenue metrics and billing overview') ?></p>
</div>

<!-- Top Row: Revenue KPI Cards -->
<div class="summary-grid">
    <div class="summary-card">
        <div class="card-label"><?= __('MRR') ?></div>
        <div class="card-value" style="color: #22c55e;">$<?= number_format($revenue['mrr'], 2) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('ARR') ?></div>
        <div class="card-value" style="color: #22c55e;">$<?= number_format($revenue['arr'], 2) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('ARPU') ?></div>
        <div class="card-value" style="color: #3b82f6;">$<?= number_format($revenue['arpu'], 2) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Paid Orgs') ?></div>
        <div class="card-value" style="color: #8b5cf6;"><?= number_format($revenue['paid_orgs']) ?></div>
    </div>
</div>

<!-- Charts Row -->
<div class="charts-grid">
    <div class="chart-card">
        <h3><?= __('Revenue by Plan') ?></h3>
        <div class="chart-wrapper">
            <canvas id="revenueByPlanChart"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h3><?= __('Plan Distribution') ?></h3>
        <div class="chart-wrapper">
            <canvas id="revenuePlanDistChart"></canvas>
        </div>
    </div>
</div>

<!-- Paying Customers Table -->
<div class="tables-grid" style="margin-top: 24px;">
    <div class="table-card" style="grid-column: 1 / -1;">
        <h3><?= __('Paying Customers') ?></h3>
        <?php if (!empty($organizations)): ?>
            <table>
                <thead>
                    <tr>
                        <th><?= __('Organization') ?></th>
                        <th><?= __('Plan') ?></th>
                        <th><?= __('Monthly Price') ?></th>
                        <th><?= __('Customer Since') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($organizations as $org): ?>
                        <tr>
                            <td><?= h($org->name) ?></td>
                            <td>
                                <?php
                                    $planBadge = match($org->plan) {
                                        'business' => 'badge-danger',
                                        'pro' => 'badge-warning',
                                        default => 'badge-secondary',
                                    };
                                ?>
                                <span class="badge <?= $planBadge ?>"><?= h(ucfirst($org->plan)) ?></span>
                            </td>
                            <td>
                                <?php
                                    $planPrice = $revenue['revenue_by_plan'][$org->plan] ?? 0;
                                    $orgCount = 0;
                                    foreach ($organizations as $o) {
                                        if ($o->plan === $org->plan) {
                                            $orgCount++;
                                        }
                                    }
                                    $perOrg = $orgCount > 0 ? $planPrice / $orgCount : 0;
                                ?>
                                $<?= number_format($perOrg, 2) ?>
                            </td>
                            <td><?= $org->created ? $org->created->nice() : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state"><?= __('No paying customers yet.') ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Trial Metrics Section -->
<div class="summary-grid" style="margin-top: 24px;">
    <div class="summary-card">
        <div class="card-label"><?= __('Active Trials') ?></div>
        <div class="card-value" style="color: #8b5cf6;"><?= number_format($trials['active_trials']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Trial Conversion Rate') ?></div>
        <div class="card-value" style="color: #22c55e;"><?= $trials['conversion_rate'] ?>%</div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Total Trialed') ?></div>
        <div class="card-value" style="color: #f59e0b;"><?= number_format($trials['total_trialed']) ?></div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Pass data to JS -->
<script>
    window.superAdminData = {
        revenueByPlan: <?= json_encode($revenue['revenue_by_plan']) ?>,
        paidOrgs: <?= json_encode($revenue['paid_orgs']) ?>,
        totalOrgs: <?= json_encode($revenue['total_orgs']) ?>
    };
</script>
<script src="/js/super-admin-charts.js"></script>
