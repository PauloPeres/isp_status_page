<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\MaintenanceWindow> $maintenanceWindows
 */
?>

<div class="content-header">
    <h1><?= __('Maintenance Windows') ?></h1>
    <div class="header-actions">
        <?= $this->Html->link(
            __('+ Schedule Maintenance'),
            ['action' => 'add'],
            ['class' => 'btn btn-primary']
        ) ?>
    </div>
</div>

<div class="card">
    <?php if (count($maintenanceWindows) === 0): ?>
        <div class="empty-state">
            <p><?= __('No maintenance windows scheduled.') ?></p>
            <p><?= $this->Html->link(__('Schedule your first maintenance window'), ['action' => 'add']) ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= __('Title') ?></th>
                        <th><?= __('Starts At') ?></th>
                        <th><?= __('Ends At') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Suppress Alerts') ?></th>
                        <th><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maintenanceWindows as $window): ?>
                    <tr>
                        <td>
                            <strong><?= h($window->title) ?></strong>
                            <?php if ($window->description): ?>
                                <br><small class="text-muted"><?= h(\Cake\Utility\Text::truncate($window->description, 60)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= h($window->starts_at->format('Y-m-d H:i')) ?></td>
                        <td><?= h($window->ends_at->format('Y-m-d H:i')) ?></td>
                        <td>
                            <?php
                            $statusClass = match ($window->status) {
                                'scheduled' => 'badge-info',
                                'in_progress' => 'badge-warning',
                                'completed' => 'badge-success',
                                'cancelled' => 'badge-secondary',
                                default => 'badge-secondary',
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>"><?= h(ucfirst(str_replace('_', ' ', $window->status))) ?></span>
                        </td>
                        <td>
                            <?php if ($window->auto_suppress_alerts): ?>
                                <span class="badge badge-success"><?= __('Yes') ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?= __('No') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $window->id], ['class' => 'btn btn-sm btn-secondary']) ?>
                            <?= $this->Form->postLink(
                                __('Delete'),
                                ['action' => 'delete', $window->id],
                                ['confirm' => __('Are you sure you want to delete this maintenance window?'), 'class' => 'btn btn-sm btn-danger']
                            ) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="paginator">
            <?= $this->Paginator->prev('< ' . __('Previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('Next') . ' >') ?>
        </div>
    <?php endif; ?>
</div>
