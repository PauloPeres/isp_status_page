<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $monitors
 * @var array $stats
 */
$this->assign('title', __d('monitors', 'Monitors'));
?>

<style>
    .monitors-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 16px;
    }

    .btn-add {
        padding: 10px 20px;
        background: #22c55e;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
    }

    .btn-add:hover {
        background: #16a34a;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card-mini {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #333;
    }

    .stat-value.success { color: #22c55e; }
    .stat-value.error { color: #ef4444; }
    .stat-value.info { color: #3b82f6; }

    .filters-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .filters-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 16px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-size: 13px;
        font-weight: 600;
        color: #444;
        margin-bottom: 6px;
    }

    .filter-group input,
    .filter-group select {
        padding: 8px 12px;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        font-size: 14px;
        background: white;
    }

    .filter-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-filter {
        padding: 8px 16px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
    }

    .btn-filter:hover {
        background: #2563eb;
    }

    .btn-clear {
        padding: 8px 16px;
        background: white;
        color: #666;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        display: inline-block;
    }

    .btn-clear:hover {
        background: #f8f9fa;
    }

    .monitors-table {
        width: 100%;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .monitors-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .monitors-table th {
        background: #f8f9fa;
        padding: 12px 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        border-bottom: 2px solid #e0e0e0;
    }

    .monitors-table th a {
        color: #666;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .monitors-table th a:hover {
        color: #3b82f6;
    }

    .monitors-table th a::after {
        content: '⇅';
        opacity: 0.3;
        font-size: 12px;
    }

    .monitors-table th a.asc::after {
        content: '↑';
        opacity: 1;
        color: #3b82f6;
    }

    .monitors-table th a.desc::after {
        content: '↓';
        opacity: 1;
        color: #3b82f6;
    }

    .monitors-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
        vertical-align: middle;
    }

    .monitors-table tr:last-child td {
        border-bottom: none;
    }

    .monitors-table tbody tr:hover {
        background: #f8f9fa;
    }

    .status-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
    }

    .status-up {
        background: #22c55e;
        box-shadow: 0 0 8px rgba(34, 197, 94, 0.4);
    }

    .status-down {
        background: #ef4444;
        box-shadow: 0 0 8px rgba(239, 68, 68, 0.4);
    }

    .status-unknown {
        background: #999;
    }

    .monitor-name {
        font-weight: 600;
        color: #333;
    }

    .monitor-description {
        font-size: 13px;
        color: #666;
        margin-top: 4px;
    }

    .monitor-target {
        font-family: 'Courier New', monospace;
        font-size: 13px;
        color: #666;
        background: #f8f9fa;
        padding: 4px 8px;
        border-radius: 4px;
        display: inline-block;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .badge-success {
        background: #dcfce7;
        color: #16a34a;
    }

    .badge-info {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .badge-secondary {
        background: #f3f4f6;
        color: #6b7280;
    }

    .action-buttons {
        display: flex;
        gap: 4px;
        justify-content: flex-end;
    }

    .btn-action {
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-weight: 500;
        display: inline-block;
    }

    .btn-action-view {
        background: #3b82f6;
        color: white;
    }

    .btn-action-view:hover {
        background: #2563eb;
    }

    .btn-action-edit {
        background: #f59e0b;
        color: white;
    }

    .btn-action-edit:hover {
        background: #d97706;
    }

    .btn-action-toggle {
        background: #8b5cf6;
        color: white;
    }

    .btn-action-toggle:hover {
        background: #7c3aed;
    }

    .btn-action-danger {
        background: #ef4444;
        color: white;
    }

    .btn-action-danger:hover {
        background: #dc2626;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px !important;
        color: #999;
    }

    .empty-state-icon {
        font-size: 48px;
        margin-bottom: 16px;
    }

    .pagination {
        margin-top: 24px;
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #d0d0d0;
        border-radius: 4px;
        color: #666;
        text-decoration: none;
        font-size: 14px;
    }

    .pagination a:hover {
        background: #f8f9fa;
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .pagination .active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .pagination .disabled {
        color: #ccc;
        cursor: not-allowed;
    }

    .pagination-info {
        text-align: center;
        margin-top: 12px;
        font-size: 13px;
        color: #666;
    }

    @media (max-width: 768px) {
        .monitors-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .filters-row {
            grid-template-columns: 1fr;
        }

        .monitors-table {
            overflow-x: auto;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>

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
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <p style="font-size: 18px; margin-bottom: 8px;"><?= __d('monitors', 'No monitors found') ?></p>
            <p style="margin-bottom: 16px;"><?= __d('monitors', 'Try adjusting the filters or create your first monitor.') ?></p>
            <?= $this->Html->link(
                __d('monitors', 'Create First Monitor'),
                ['action' => 'add'],
                ['class' => 'btn-add']
            ) ?>
        </div>
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
