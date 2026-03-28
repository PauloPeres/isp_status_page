<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\User> $users
 */
$this->assign('title', __d('users', 'Users'));
?>

<!-- Styles provided by admin.css -->
<style>
.users-header h1 {
    margin: 0;
    font-size: 24px;
    color: var(--color-dark);
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
