<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $monitors
 * @var array $stats
 */
$this->assign('title', __d('monitors', 'Monitors'));
?>

<!-- Styles provided by admin.css -->

<div class="monitors-header">
    <h2>🖥️ <?= __d('monitors', 'Monitors') ?></h2>
    <?= $this->Html->link(
        '+ ' . __d('monitors', 'New Monitor'),
        ['action' => 'add'],
        ['class' => 'btn-add']
    ) ?>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('monitors', 'Total') ?></div>
        <div class="stat-value info"><?= number_format($stats['total']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('monitors', 'Active') ?></div>
        <div class="stat-value success"><?= number_format($stats['active']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('monitors', 'Online') ?></div>
        <div class="stat-value success"><?= number_format($stats['online']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('monitors', 'Offline') ?></div>
        <div class="stat-value error"><?= number_format($stats['offline']) ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'filters-form']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label><?= __('Search') ?></label>
            <?= $this->Form->control('search', [
                'label' => false,
                'placeholder' => __d('monitors', 'Name, target or description...'),
                'value' => $this->request->getQuery('search'),
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('monitors', 'Type') ?></label>
            <?= $this->Form->control('type', [
                'label' => false,
                'options' => [
                    '' => __('All'),
                    'http' => 'HTTP',
                    'ping' => 'Ping',
                    'port' => 'Port',
                ],
                'value' => $this->request->getQuery('type'),
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __('Status') ?></label>
            <?= $this->Form->control('status', [
                'label' => false,
                'options' => [
                    '' => __('All'),
                    'up' => __d('monitors', 'Online'),
                    'down' => __d('monitors', 'Offline'),
                    'unknown' => __d('monitors', 'Unknown'),
                ],
                'value' => $this->request->getQuery('status'),
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('monitors', 'State') ?></label>
            <?= $this->Form->control('active', [
                'label' => false,
                'options' => [
                    '' => __('All'),
                    '1' => __d('monitors', 'Active'),
                    '0' => __d('monitors', 'Inactive'),
                ],
                'value' => $this->request->getQuery('active'),
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>
    </div>

    <div class="filter-buttons">
        <?= $this->Form->button(__('Filter'), ['type' => 'submit', 'class' => 'btn-filter']) ?>
        <?= $this->Html->link(__('Clear'), ['action' => 'index'], ['class' => 'btn-clear']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>

<!-- Monitors Table -->
<div class="monitors-table">
    <?php if ($monitors->count() > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('status', __('Status')) ?></th>
                    <th><?= $this->Paginator->sort('name', __d('monitors', 'Name')) ?></th>
                    <th><?= $this->Paginator->sort('type', __d('monitors', 'Type')) ?></th>
                    <th><?= $this->Paginator->sort('target', __d('monitors', 'Target')) ?></th>
                    <th><?= $this->Paginator->sort('last_check_at', __d('monitors', 'Last Check')) ?></th>
                    <th><?= __d('monitors', 'Response Time') ?></th>
                    <th><?= $this->Paginator->sort('active', __d('monitors', 'State')) ?></th>
                    <th style="text-align: right;"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monitors as $monitor): ?>
                    <tr>
                        <td>
                            <span class="status-indicator status-<?= h($monitor->status) ?>"
                                  title="<?= h(ucfirst($monitor->status)) ?>">
                            </span>
                        </td>
                        <td>
                            <div class="monitor-name"><?= h($monitor->name) ?></div>
                            <?php if ($monitor->description): ?>
                                <div class="monitor-description"><?= h($monitor->description) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-info"><?= h(strtoupper($monitor->type)) ?></span>
                        </td>
                        <td>
                            <span class="monitor-target"><?= h($monitor->target) ?></span>
                        </td>
                        <td>
                            <?php if ($monitor->last_check_at): ?>
                                <span class="local-datetime" data-utc="<?= $monitor->last_check_at->format('c') ?>"></span>
                            <?php else: ?>
                                <span style="color: #999;"><?= __d('monitors', 'Never') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($monitor->response_time): ?>
                                <span style="font-family: 'Courier New', monospace; color: #666;">
                                    <?= number_format($monitor->response_time, 0) ?>ms
                                </span>
                            <?php else: ?>
                                <span style="color: #ccc;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($monitor->active): ?>
                                <span class="badge badge-success"><?= __d('monitors', 'Active') ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?= __d('monitors', 'Inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?= $this->Html->link(
                                    __('View'),
                                    ['action' => 'view', $monitor->id],
                                    ['class' => 'btn-action btn-action-view', 'title' => __('View details')]
                                ) ?>
                                <?= $this->Html->link(
                                    __('Edit'),
                                    ['action' => 'edit', $monitor->id],
                                    ['class' => 'btn-action btn-action-edit', 'title' => __('Edit')]
                                ) ?>
                                <?= $this->Form->postLink(
                                    $monitor->active ? __d('monitors', 'Deactivate') : __d('monitors', 'Activate'),
                                    ['action' => 'toggle', $monitor->id],
                                    [
                                        'class' => 'btn-action btn-action-toggle',
                                        'confirm' => __d('monitors', 'Are you sure you want to {0} this monitor?', $monitor->active ? __d('monitors', 'deactivate') : __d('monitors', 'activate'))
                                    ]
                                ) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['action' => 'delete', $monitor->id],
                                    [
                                        'class' => 'btn-action btn-action-danger',
                                        'confirm' => __d('monitors', 'Are you sure you want to delete this monitor? This action cannot be undone.')
                                    ]
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <?= $this->element('empty_state', [
            'icon' => '🖥️',
            'title' => __d('monitors', 'No monitors yet'),
            'description' => __d('monitors', 'Create your first monitor to start tracking uptime.'),
            'actionUrl' => $this->Url->build(['action' => 'add']),
            'actionLabel' => __d('monitors', 'Add Monitor'),
        ]) ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($monitors->count() > 0): ?>
    <div class="pagination">
        <?= $this->Paginator->first('« ' . __('First')) ?>
        <?= $this->Paginator->prev('‹ ' . __('Previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('Next') . ' ›') ?>
        <?= $this->Paginator->last(__('Last') . ' »') ?>
    </div>
    <div class="pagination-info">
        <?= $this->Paginator->counter(__d('monitors', 'Page {{page}} of {{pages}}, showing {{current}} of {{count}} monitors')) ?>
    </div>
<?php endif; ?>
