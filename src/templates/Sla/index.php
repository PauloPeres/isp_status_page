<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\SlaDefinition> $slaDefinitions
 * @var array $slaStatuses
 */
$this->assign('title', __('SLA Tracking'));
?>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('SLA Tracking'), 'url' => null],
]]) ?>

<div class="sla-index">
    <div class="page-header">
        <div>
            <h1><?= __('SLA Tracking') ?></h1>
            <p><?= __('Monitor uptime commitments and compliance status') ?></p>
        </div>
        <div>
            <?= $this->Html->link(
                '+ ' . __('New SLA'),
                ['action' => 'add'],
                ['class' => 'btn btn-primary']
            ) ?>
        </div>
    </div>

    <?php if (count($slaDefinitions) > 0): ?>
        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= __('Monitor') ?></th>
                            <th><?= __('SLA Name') ?></th>
                            <th><?= __('Target') ?></th>
                            <th><?= __('Actual Uptime') ?></th>
                            <th><?= __('Status') ?></th>
                            <th><?= __('Downtime Budget') ?></th>
                            <th><?= __('Period') ?></th>
                            <th><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($slaDefinitions as $slaDef): ?>
                            <?php
                            $status = $slaStatuses[$slaDef->id] ?? null;
                            $statusLabel = $status ? $status['status'] : 'unknown';
                            $badgeClass = match ($statusLabel) {
                                'compliant' => 'badge-success',
                                'at_risk' => 'badge-warning',
                                'breached' => 'badge-danger',
                                default => 'badge-secondary',
                            };
                            $statusText = match ($statusLabel) {
                                'compliant' => __('Compliant'),
                                'at_risk' => __('At Risk'),
                                'breached' => __('Breached'),
                                default => __('Unknown'),
                            };
                            ?>
                            <tr>
                                <td>
                                    <?= $this->Html->link(
                                        h($slaDef->monitor->name ?? __('N/A')),
                                        ['controller' => 'Monitors', 'action' => 'view', $slaDef->monitor_id]
                                    ) ?>
                                </td>
                                <td><?= h($slaDef->name) ?></td>
                                <td><strong><?= number_format((float)$slaDef->target_uptime, 3) ?>%</strong></td>
                                <td>
                                    <?php if ($status): ?>
                                        <strong><?= number_format($status['actual_uptime'], 3) ?>%</strong>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                </td>
                                <td>
                                    <?php if ($status): ?>
                                        <?php
                                        $allowed = $status['allowed_downtime_minutes'];
                                        $used = $status['downtime_minutes'];
                                        $remaining = $status['remaining_downtime_minutes'];
                                        $usagePercent = $allowed > 0 ? min(100, ($used / $allowed) * 100) : 100;
                                        $barColor = $usagePercent > 90 ? 'var(--color-error)' :
                                                   ($usagePercent > 70 ? 'var(--color-warning)' : 'var(--color-success)');
                                        ?>
                                        <div class="downtime-budget-bar" title="<?= sprintf(__('Used: %.1f min / Allowed: %.1f min / Remaining: %.1f min'), $used, $allowed, $remaining) ?>">
                                            <div class="budget-bar-track">
                                                <div class="budget-bar-fill" style="width: <?= number_format($usagePercent, 1) ?>%; background: <?= $barColor ?>;"></div>
                                            </div>
                                            <span class="budget-bar-label"><?= number_format($remaining, 1) ?> min left</span>
                                        </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= h(ucfirst($slaDef->measurement_period)) ?></td>
                                <td class="actions-cell">
                                    <?= $this->Html->link(
                                        __('Report'),
                                        ['action' => 'report', $slaDef->id],
                                        ['class' => 'btn btn-sm btn-primary']
                                    ) ?>
                                    <?= $this->Html->link(
                                        __('Edit'),
                                        ['action' => 'edit', $slaDef->id],
                                        ['class' => 'btn btn-sm btn-secondary']
                                    ) ?>
                                    <?= $this->Form->postLink(
                                        __('Delete'),
                                        ['action' => 'delete', $slaDef->id],
                                        [
                                            'class' => 'btn btn-sm btn-error',
                                            'confirm' => __('Are you sure? This action cannot be undone.'),
                                        ]
                                    ) ?>
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
                <div class="empty-state-icon" style="font-size: 48px;">&#x1F4CA;</div>
                <h3><?= __('No SLA definitions yet') ?></h3>
                <p><?= __('Create your first SLA to start tracking uptime commitments for your monitors.') ?></p>
                <?= $this->Html->link(
                    '+ ' . __('New SLA'),
                    ['action' => 'add'],
                    ['class' => 'btn btn-primary']
                ) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.sla-index .page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.sla-index .page-header h1 { margin: 0 0 4px; }
.sla-index .page-header p { margin: 0; color: var(--color-gray-medium); }
.actions-cell { white-space: nowrap; display: flex; gap: 4px; }
.btn-sm { padding: 4px 10px; font-size: 12px; }
.downtime-budget-bar { min-width: 140px; }
.budget-bar-track {
    width: 100%;
    height: 8px;
    background: var(--color-gray-light);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 4px;
}
.budget-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}
.budget-bar-label {
    font-size: 11px;
    color: var(--color-gray-medium);
}
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state h3 { margin: 16px 0 8px; }
.empty-state p { color: var(--color-gray-medium); margin-bottom: 20px; }

@media (max-width: 768px) {
    .sla-index .page-header { flex-direction: column; align-items: flex-start; }
    .actions-cell { flex-direction: column; }
    .btn-sm { width: 100%; min-height: 36px; }
}
</style>
