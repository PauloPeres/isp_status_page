<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SlaDefinition $slaDefinition
 * @var array $currentSla
 * @var array<\App\Model\Entity\SlaReport> $history
 * @var array $chartData
 */
$this->assign('title', __('SLA Report') . ' - ' . h($slaDefinition->name));

$statusLabel = match ($currentSla['status']) {
    'compliant' => __('Compliant'),
    'at_risk' => __('At Risk'),
    'breached' => __('Breached'),
    default => __('Unknown'),
};
$statusBadge = match ($currentSla['status']) {
    'compliant' => 'badge-success',
    'at_risk' => 'badge-warning',
    'breached' => 'badge-danger',
    default => 'badge-secondary',
};
$usagePercent = $currentSla['allowed_downtime_minutes'] > 0
    ? min(100, ($currentSla['downtime_minutes'] / $currentSla['allowed_downtime_minutes']) * 100)
    : 100;
$barColor = $usagePercent > 90 ? 'var(--color-error)' :
           ($usagePercent > 70 ? 'var(--color-warning)' : 'var(--color-success)');
?>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('SLA Tracking'), 'url' => $this->Url->build(['controller' => 'Sla', 'action' => 'index'])],
    ['title' => h($slaDefinition->name), 'url' => null],
]]) ?>

<div class="sla-report">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1><?= h($slaDefinition->name) ?></h1>
            <p>
                <?= $this->Html->link(
                    h($slaDefinition->monitor->name ?? __('Monitor')),
                    ['controller' => 'Monitors', 'action' => 'view', $slaDefinition->monitor_id]
                ) ?>
                &middot;
                <?= __('Target: {0}%', number_format((float)$slaDefinition->target_uptime, 3)) ?>
                &middot;
                <?= h(ucfirst($slaDefinition->measurement_period)) ?>
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(
                __('Export CSV'),
                ['action' => 'exportReport', $slaDefinition->id],
                ['class' => 'btn btn-secondary']
            ) ?>
            <?= $this->Html->link(
                __('Edit SLA'),
                ['action' => 'edit', $slaDefinition->id],
                ['class' => 'btn btn-primary']
            ) ?>
        </div>
    </div>

    <!-- Current Status Card -->
    <div class="status-cards-grid">
        <div class="sla-status-card">
            <div class="sla-big-number <?= $currentSla['status'] ?>">
                <?= number_format($currentSla['actual_uptime'], 3) ?>%
            </div>
            <div class="sla-status-label">
                <?= __('Current Uptime') ?>
                <span class="badge <?= $statusBadge ?>" style="margin-left: 8px;"><?= $statusLabel ?></span>
            </div>
            <div class="sla-period-info">
                <?= __('Period: {0} to {1}', $currentSla['period_start'], $currentSla['period_end']) ?>
            </div>
        </div>

        <div class="sla-stats-card">
            <div class="sla-stat">
                <span class="sla-stat-value"><?= number_format($currentSla['downtime_minutes'], 1) ?></span>
                <span class="sla-stat-label"><?= __('Downtime (min)') ?></span>
            </div>
            <div class="sla-stat">
                <span class="sla-stat-value"><?= number_format($currentSla['allowed_downtime_minutes'], 1) ?></span>
                <span class="sla-stat-label"><?= __('Allowed (min)') ?></span>
            </div>
            <div class="sla-stat">
                <span class="sla-stat-value"><?= number_format($currentSla['remaining_downtime_minutes'], 1) ?></span>
                <span class="sla-stat-label"><?= __('Remaining (min)') ?></span>
            </div>
            <div class="sla-stat">
                <span class="sla-stat-value"><?= $currentSla['incidents_count'] ?></span>
                <span class="sla-stat-label"><?= __('Incidents') ?></span>
            </div>
        </div>
    </div>

    <!-- Downtime Budget Bar -->
    <div class="card">
        <div class="card-header"><?= __('Downtime Budget') ?></div>
        <div style="padding: 24px;">
            <div class="budget-progress">
                <div class="budget-progress-info">
                    <span><?= __('Used: {0} min of {1} min allowed', number_format($currentSla['downtime_minutes'], 1), number_format($currentSla['allowed_downtime_minutes'], 1)) ?></span>
                    <span><strong><?= number_format($usagePercent, 1) ?>%</strong> <?= __('consumed') ?></span>
                </div>
                <div class="budget-progress-track">
                    <div class="budget-progress-fill" style="width: <?= number_format($usagePercent, 1) ?>%; background: <?= $barColor ?>;"></div>
                </div>
                <div class="budget-progress-labels">
                    <span>0%</span>
                    <span style="color: var(--color-warning);">70%</span>
                    <span style="color: var(--color-error);">90%</span>
                    <span>100%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Uptime Trend Chart -->
    <div class="card">
        <div class="card-header"><?= __('Uptime Trend') ?></div>
        <div style="padding: 20px;">
            <canvas id="slaTrendChart" height="80"></canvas>
        </div>
    </div>

    <!-- Monthly History Table -->
    <div class="card">
        <div class="card-header">
            <span><?= __('Period History') ?></span>
            <span class="badge badge-info"><?= count($history) ?> <?= __('periods') ?></span>
        </div>
        <?php if (count($history) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= __('Period') ?></th>
                            <th><?= __('Uptime') ?></th>
                            <th><?= __('Target') ?></th>
                            <th><?= __('Status') ?></th>
                            <th><?= __('Downtime') ?></th>
                            <th><?= __('Allowed') ?></th>
                            <th><?= __('Incidents') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $report): ?>
                            <?php
                            $rBadge = match ($report->status) {
                                'compliant' => 'badge-success',
                                'at_risk' => 'badge-warning',
                                'breached' => 'badge-danger',
                                default => 'badge-secondary',
                            };
                            $rLabel = match ($report->status) {
                                'compliant' => __('Compliant'),
                                'at_risk' => __('At Risk'),
                                'breached' => __('Breached'),
                                default => __('Unknown'),
                            };
                            ?>
                            <tr>
                                <td>
                                    <?= $report->period_start->format('Y-m-d') ?>
                                    <small style="color: var(--color-gray-medium);">to <?= $report->period_end->format('Y-m-d') ?></small>
                                </td>
                                <td><strong><?= number_format((float)$report->actual_uptime, 3) ?>%</strong></td>
                                <td><?= number_format((float)$report->target_uptime, 3) ?>%</td>
                                <td><span class="badge <?= $rBadge ?>"><?= $rLabel ?></span></td>
                                <td><?= number_format((float)$report->downtime_minutes, 1) ?> min</td>
                                <td><?= number_format((float)$report->allowed_downtime_minutes, 1) ?> min</td>
                                <td><?= $report->incidents_count ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding: 40px;">
                <p style="color: var(--color-gray-medium);"><?= __('No historical data yet. Reports are generated automatically.') ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.sla-report .page-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.sla-report .page-header h1 { margin: 0 0 4px; }
.sla-report .page-header p { margin: 0; color: var(--color-gray-medium); }

.status-cards-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;
}

.sla-status-card {
    background: var(--color-white); border-radius: var(--radius-lg);
    padding: 32px; box-shadow: var(--shadow-md); text-align: center;
}
.sla-big-number {
    font-size: 48px; font-weight: 800; line-height: 1; margin-bottom: 12px;
}
.sla-big-number.compliant { color: var(--color-success); }
.sla-big-number.at_risk { color: #F9A825; }
.sla-big-number.breached { color: var(--color-error); }
.sla-status-label { font-size: 16px; font-weight: 600; color: var(--color-dark); margin-bottom: 8px; }
.sla-period-info { font-size: 13px; color: var(--color-gray-medium); }

.sla-stats-card {
    background: var(--color-white); border-radius: var(--radius-lg);
    padding: 24px; box-shadow: var(--shadow-md);
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
}
.sla-stat { text-align: center; }
.sla-stat-value { display: block; font-size: 28px; font-weight: 700; color: var(--color-dark); }
.sla-stat-label { display: block; font-size: 12px; color: var(--color-gray-medium); margin-top: 4px; }

.budget-progress { margin: 0; }
.budget-progress-info {
    display: flex; justify-content: space-between; margin-bottom: 8px;
    font-size: 14px; color: var(--color-gray-medium);
}
.budget-progress-track {
    width: 100%; height: 20px;
    background: var(--color-gray-light); border-radius: 10px; overflow: hidden;
}
.budget-progress-fill {
    height: 100%; border-radius: 10px; transition: width 0.5s ease;
}
.budget-progress-labels {
    display: flex; justify-content: space-between; margin-top: 6px;
    font-size: 11px; color: var(--color-gray-medium);
}

.card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 24px; border-bottom: 1px solid var(--color-gray-light);
    font-weight: 600; font-size: 15px;
}

@media (max-width: 768px) {
    .sla-report .page-header { flex-direction: column; align-items: flex-start; }
    .status-cards-grid { grid-template-columns: 1fr; }
    .sla-big-number { font-size: 36px; }
    .sla-stats-card { grid-template-columns: 1fr 1fr; gap: 12px; }
    .sla-stat-value { font-size: 22px; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var chartData = <?= json_encode($chartData) ?>;

    if (chartData.length === 0) {
        document.getElementById('slaTrendChart').parentElement.innerHTML =
            '<p style="text-align:center;color:#999;padding:40px 0;"><?= __('Not enough data for trend chart yet.') ?></p>';
        return;
    }

    var labels = chartData.map(function(d) { return d.period; });
    var uptimeValues = chartData.map(function(d) { return d.uptime; });
    var targetValues = chartData.map(function(d) { return d.target; });

    var ctx = document.getElementById('slaTrendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '<?= __('Actual Uptime') ?>',
                    data: uptimeValues,
                    borderColor: '#1E88E5',
                    backgroundColor: 'rgba(30, 136, 229, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 2,
                },
                {
                    label: '<?= __('Target') ?>',
                    data: targetValues,
                    borderColor: '#E53935',
                    borderDash: [8, 4],
                    fill: false,
                    pointRadius: 0,
                    borderWidth: 2,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y.toFixed(3) + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    min: Math.max(90, Math.min.apply(null, uptimeValues) - 1),
                    max: 100.1,
                    title: { display: true, text: '<?= __('Uptime %') ?>' },
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: {
                        callback: function(val) { return val.toFixed(1) + '%'; }
                    }
                },
                x: {
                    title: { display: true, text: '<?= __('Period') ?>' },
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
