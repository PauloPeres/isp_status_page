<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\User> $users
 */
$this->assign('title', __d('users', 'Users'));
?>

<style>
.users-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.users-header h1 {
    margin: 0;
    font-size: 24px;
    color: #333;
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
    color: white;
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

.stat-value.info { color: #3b82f6; }
.stat-value.success { color: #22c55e; }
.stat-value.error { color: #ef4444; }

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
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    display: inline-block;
}

.btn-clear:hover {
    background: #f8f9fa;
    color: #666;
}

.table-container {
    width: 100%;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 24px;
}

.table-container table {
    width: 100%;
    border-collapse: collapse;
}

.table-container th {
    background: #f8f9fa;
    padding: 12px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    border-bottom: 2px solid #e0e0e0;
}

.table-container th a {
    color: #666;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.table-container th a:hover {
    color: #3b82f6;
}

.table-container th a::after {
    content: '⇅';
    opacity: 0.3;
    font-size: 12px;
}

.table-container th a.asc::after {
    content: '↑';
    opacity: 1;
    color: #3b82f6;
}

.table-container th a.desc::after {
    content: '↓';
    opacity: 1;
    color: #3b82f6;
}

.table-container td {
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 14px;
    vertical-align: middle;
}

.table-container tr:last-child td {
    border-bottom: none;
}

.table-container tbody tr:hover {
    background: #f8f9fa;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-success {
    background: #dcfce7;
    color: #16a34a;
}

.badge-danger {
    background: #fee2e2;
    color: #dc2626;
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
    color: white;
}

.btn-action-edit {
    background: #f59e0b;
    color: white;
}

.btn-action-edit:hover {
    background: #d97706;
    color: white;
}

.btn-action-danger {
    background: #ef4444;
    color: white;
}

.btn-action-danger:hover {
    background: #dc2626;
    color: white;
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

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .filters-row {
        grid-template-columns: 1fr;
    }

    .table-container {
        overflow-x: auto;
    }

    .action-buttons {
        flex-direction: column;
    }
}
</style>

<div class="users-index">
    <div class="users-header">
        <h1><?= __d('users', 'Users') ?></h1>
        <?= $this->Html->link(__d('users', 'New User'), ['action' => 'add'], ['class' => 'btn-add']) ?>
    </div>

    <!-- Estatísticas -->
    <?php
    $total = count($users);
    $active = 0;
    $admins = 0;
    foreach ($users as $user) {
        if ($user->active) $active++;
        if ($user->role === 'admin') $admins++;
    }
    ?>

    <div class="stats-grid">
        <div class="stat-card-mini">
            <div class="stat-label"><?= __('Total') ?></div>
            <div class="stat-value info"><?= number_format($total) ?></div>
        </div>

        <div class="stat-card-mini">
            <div class="stat-label"><?= __('Active') ?></div>
            <div class="stat-value success"><?= number_format($active) ?></div>
        </div>

        <div class="stat-card-mini">
            <div class="stat-label"><?= __d('users', 'Administrators') ?></div>
            <div class="stat-value error"><?= number_format($admins) ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filters-card">
        <?= $this->Form->create(null, ['type' => 'get', 'valueSources' => ['query', 'context']]) ?>
        <div class="filters-row">
            <div class="filter-group">
                <label><?= __d('users', 'Role') ?></label>
                <?= $this->Form->select('role', [
                    '' => __('All'),
                    'admin' => __d('users', 'Administrator'),
                    'user' => __d('users', 'User'),
                    'viewer' => __d('users', 'Viewer')
                ], [
                    'empty' => false,
                    'label' => false,
                    'value' => $this->request->getQuery('role')
                ]) ?>
            </div>

            <div class="filter-group">
                <label><?= __('Status') ?></label>
                <?= $this->Form->select('active', [
                    '' => __('All'),
                    '1' => __('Active'),
                    '0' => __('Inactive')
                ], [
                    'empty' => false,
                    'label' => false,
                    'value' => $this->request->getQuery('active')
                ]) ?>
            </div>

            <div class="filter-group">
                <label><?= __('Search') ?></label>
                <?= $this->Form->control('search', [
                    'label' => false,
                    'placeholder' => __d('users', 'Username or email...'),
                    'value' => $this->request->getQuery('search')
                ]) ?>
            </div>
        </div>

        <div class="filter-buttons">
            <?= $this->Form->button(__('Filter'), ['type' => 'submit', 'class' => 'btn-filter']) ?>
            <?= $this->Html->link(__('Clear'), ['action' => 'index'], ['class' => 'btn-clear']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>

    <!-- Tabela -->
    <?php if (count($users) > 0): ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><?= $this->Paginator->sort('username', __d('users', 'Username')) ?></th>
                        <th><?= $this->Paginator->sort('email', __('Email')) ?></th>
                        <th><?= $this->Paginator->sort('role', __d('users', 'Role')) ?></th>
                        <th><?= $this->Paginator->sort('active', __('Status')) ?></th>
                        <th><?= $this->Paginator->sort('last_login', __d('users', 'Last Login')) ?></th>
                        <th><?= $this->Paginator->sort('created', __('Created')) ?></th>
                        <th style="text-align: right;"><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= h($user->username) ?></strong></td>
                            <td><?= h($user->email) ?></td>
                            <td>
                                <span class="badge <?= $user->role === 'admin' ? 'badge-danger' : 'badge-info' ?>">
                                    <?= h($user->getRoleName()) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user->active): ?>
                                    <span class="badge badge-success"><?= __('Active') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-secondary"><?= __('Inactive') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user->last_login): ?>
                                    <?= $user->last_login->format('d/m/Y H:i') ?>
                                <?php else: ?>
                                    <span style="color: #999;"><?= __d('users', 'Never') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= $user->created->format('d/m/Y') ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?= $this->Html->link(__('View'), ['action' => 'view', $user->id], ['class' => 'btn-action btn-action-view']) ?>
                                    <?= $this->Html->link(__('Edit'), ['action' => 'edit', $user->id], ['class' => 'btn-action btn-action-edit']) ?>
                                    <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $user->id], [
                                        'confirm' => __('Are you sure you want to delete this user?'),
                                        'class' => 'btn-action btn-action-danger'
                                    ]) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div class="pagination">
            <?= $this->Paginator->first('«') ?>
            <?= $this->Paginator->prev('‹') ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next('›') ?>
            <?= $this->Paginator->last('»') ?>
        </div>

        <div class="pagination-info">
            <?= $this->Paginator->counter(__('Showing {{start}} to {{end}} of {{count}} entries')) ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div style="font-size: 48px; margin-bottom: 16px;">👤</div>
            <p><?= __d('users', 'No users found') ?></p>
        </div>
    <?php endif; ?>
</div>
