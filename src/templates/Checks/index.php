<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\MonitorCheck> $checks
 * @var array $stats
 * @var array $monitors
 * @var string $period
 */
$this->assign('title', __d('checks', 'Monitor Checks'));
?>

<!-- Styles provided by admin.css -->

<div class="checks-header">
    <h2>📈 <?= __d('checks', 'Monitor Checks') ?></h2>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('checks', 'Total Checks') ?></div>
        <div class="stat-value info"><?= number_format($stats['total']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('checks', 'Success') ?></div>
        <div class="stat-value success"><?= number_format($stats['success']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('checks', 'Failures') ?></div>
        <div class="stat-value error"><?= number_format($stats['failed']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('checks', 'Success Rate') ?></div>
        <div class="stat-value <?= $stats['successRate'] >= 95 ? 'success' : ($stats['successRate'] >= 80 ? 'info' : 'error') ?>">
            <?= number_format($stats['successRate'], 1) ?>%
        </div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('checks', 'Average Time') ?></div>
        <div class="stat-value">
            <?php if ($stats['avgResponseTime']): ?>
                <?= number_format($stats['avgResponseTime'], 0) ?>ms
            <?php else: ?>
                <span style="font-size: 18px; color: #999;"><?= __d('checks', 'N/A') ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'filters-form']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label><?= __d('checks', 'Monitor') ?></label>
            <?= $this->Form->control('monitor_id', [
                'options' => ['' => __d('checks', 'All Monitors')] + $monitors,
                'default' => $this->request->getQuery('monitor_id'),
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('checks', 'Status') ?></label>
            <?= $this->Form->control('status', [
                'options' => [
                    '' => __d('checks', 'All'),
                    'success' => __d('checks', 'Success'),
                    'failed' => __d('checks', 'Failed'),
                ],
                'default' => $this->request->getQuery('status'),
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('checks', 'Period') ?></label>
            <?= $this->Form->control('period', [
                'options' => [
                    '24h' => __d('checks', 'Last 24 hours'),
                    '7d' => __d('checks', 'Last 7 days'),
                    '30d' => __d('checks', 'Last 30 days'),
                    'all' => __d('checks', 'All'),
                ],
                'default' => $period,
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-buttons">
            <?= $this->Form->button(__d('checks', 'Filter'), ['type' => 'submit', 'class' => 'btn-filter']) ?>
            <?= $this->Html->link(__d('checks', 'Clear'), ['action' => 'index'], ['class' => 'btn-clear']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<!-- Checks Table -->
<div class="checks-table">
    <?php if ($checks->count() > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('checked_at', __d('checks', 'Date/Time')) ?></th>
                    <th><?= $this->Paginator->sort('monitor_id', __d('checks', 'Monitor')) ?></th>
                    <th><?= $this->Paginator->sort('status', __d('checks', 'Status')) ?></th>
                    <th><?= $this->Paginator->sort('response_time', __d('checks', 'Response Time')) ?></th>
                    <th><?= __d('checks', 'Message') ?></th>
                    <th style="text-align: right;"><?= __d('checks', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $check): ?>
                    <tr>
                        <td>
                            <span class="local-datetime" data-utc="<?= $check->checked_at->format('c') ?>"></span>
                        </td>
                        <td>
                            <?= $this->Html->link(
                                h($check->monitor->name),
                                ['controller' => 'Monitors', 'action' => 'view', $check->monitor->id],
                                ['class' => 'monitor-link']
                            ) ?>
                            <br>
                            <span style="color: #999; font-size: 12px;">
                                <?= h(strtoupper($check->monitor->type)) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?= $check->status === 'success' ? 'success' : 'danger' ?>">
                                <?= $check->status === 'success' ? '✅ ' . __d('checks', 'Success') : '❌ ' . __d('checks', 'Failed') ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($check->response_time !== null): ?>
                                <span class="response-time"><?= number_format($check->response_time, 0) ?>ms</span>
                            <?php else: ?>
                                <span style="color: #ccc;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($check->message): ?>
                                <span class="check-message" title="<?= h($check->message) ?>">
                                    <?= h($check->message) ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #ccc;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-buttons">
                                <?= $this->Html->link(
                                    __d('checks', 'View'),
                                    ['action' => 'view', $check->id],
                                    ['class' => 'btn-action btn-action-view', 'title' => __d('checks', 'View details')]
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <?= $this->element('empty_state', [
            'icon' => '📊',
            'title' => __d('checks', 'No checks recorded yet'),
        ]) ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($checks->count() > 0): ?>
    <div class="pagination">
        <?= $this->Paginator->first('« ' . __('First')) ?>
        <?= $this->Paginator->prev('‹ ' . __('Previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('Next') . ' ›') ?>
        <?= $this->Paginator->last(__('Last') . ' »') ?>
    </div>
    <div class="pagination-info">
        <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) of {{count}} total')) ?>
    </div>
<?php endif; ?>
