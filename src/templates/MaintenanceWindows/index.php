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
        <?= $this->element('empty_state', [
            'icon' => '🔧',
            'title' => __('No maintenance windows scheduled'),
            'description' => __('Plan ahead by scheduling maintenance windows.'),
            'actionUrl' => $this->Url->build(['action' => 'add']),
            'actionLabel' => __('Schedule Maintenance'),
        ]) ?>
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
                        <td><?= $window->starts_at->nice() ?></td>
                        <td><?= $window->ends_at->nice() ?></td>
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
                            <div class="action-buttons">
                                <?= $this->Html->link(__('Edit'), ['action' => 'edit', $window->id], ['class' => 'btn-action btn-action-edit']) ?>
                                <?= $this->Form->postLink(
                                    __('Delete'),
                                    ['action' => 'delete', $window->id],
                                    ['confirm' => __('Are you sure? This action cannot be undone.'), 'class' => 'btn-action btn-action-danger']
                                ) ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

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
</div>
