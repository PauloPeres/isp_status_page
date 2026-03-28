<?php
/**
 * Super Admin — Organizations List
 * TASK-SA-009
 *
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $organizations
 * @var string|null $search
 * @var string|null $planFilter
 * @var array $orgMonitorCounts
 */
$this->assign('title', __('Organizations'));
?>

<div class="dashboard-header">
    <h1><?= __('Organizations') ?></h1>
    <p><?= __('Manage all tenant organizations') ?></p>
</div>

<!-- Search & Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'org-filters-form']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label><?= __('Search') ?></label>
            <?= $this->Form->control('search', [
                'label' => false,
                'placeholder' => __('Name or slug...'),
                'value' => $search ?? '',
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __('Plan') ?></label>
            <?= $this->Form->control('plan', [
                'label' => false,
                'options' => [
                    '' => __('All Plans'),
                    'free' => __('Free'),
                    'pro' => __('Pro'),
                    'business' => __('Business'),
                ],
                'value' => $planFilter ?? '',
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-buttons">
            <?= $this->Form->button(__('Filter'), ['type' => 'submit', 'class' => 'btn-filter']) ?>
            <?= $this->Html->link(__('Clear'), ['action' => 'index'], ['class' => 'btn-clear']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<!-- Organizations Table -->
<div class="table-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?= __('Name') ?></th>
                    <th><?= __('Slug') ?></th>
                    <th><?= __('Plan') ?></th>
                    <th><?= __('Monitors') ?></th>
                    <th><?= __('Team Members') ?></th>
                    <th><?= __('Active') ?></th>
                    <th><?= __('Created') ?></th>
                    <th><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($organizations->count() === 0): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 24px; color: #94a3b8;">
                        <?= __('No organizations found.') ?>
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($organizations as $org): ?>
                <tr>
                    <td>
                        <strong>
                            <?= $this->Html->link(h($org->name), [
                                'action' => 'view',
                                $org->id,
                            ]) ?>
                        </strong>
                    </td>
                    <td><code><?= h($org->slug) ?></code></td>
                    <td>
                        <?php
                        $planClass = match ($org->plan) {
                            'business' => 'badge-info',
                            'pro' => 'badge-success',
                            default => 'badge-secondary',
                        };
                        ?>
                        <span class="badge <?= $planClass ?>"><?= h(ucfirst($org->plan)) ?></span>
                    </td>
                    <td><?= $orgMonitorCounts[$org->id] ?? 0 ?></td>
                    <td><?= count($org->organization_users ?? []) ?></td>
                    <td>
                        <?php if ($org->active): ?>
                            <span class="badge badge-success"><?= __('Yes') ?></span>
                        <?php else: ?>
                            <span class="badge badge-danger"><?= __('No') ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $org->created ? $org->created->nice() : '-' ?>
                    </td>
                    <td class="actions-cell">
                        <div class="action-buttons">
                            <?= $this->Html->link(
                                __('View'),
                                ['action' => 'view', $org->id],
                                ['class' => 'btn-action btn-action-view']
                            ) ?>
                            <?= $this->Form->postLink(
                                __('Impersonate'),
                                ['action' => 'impersonate', $org->id],
                                [
                                    'class' => 'btn-action btn-action-toggle',
                                    'confirm' => __('Impersonate organization "{0}"?', h($org->name)),
                                ]
                            ) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
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
</div>
