<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AlertRule> $alertRules
 * @var array $monitors
 */
$this->assign('title', __('Alert Rules'));
?>

<div class="monitors-header">
    <h2><?= __('Alert Rules') ?></h2>
    <?= $this->Html->link(
        '+ ' . __('New Alert Rule'),
        ['action' => 'add'],
        ['class' => 'btn-add']
    ) ?>
</div>

<!-- Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'filters-form']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label><?= __('Search') ?></label>
            <?= $this->Form->control('search', [
                'label' => false,
                'placeholder' => __('Monitor name or recipient...'),
                'value' => $this->request->getQuery('search'),
                'class' => 'form-control',
            ]) ?>
        </div>
        <div class="filter-group">
            <label><?= __('Channel') ?></label>
            <?= $this->Form->select('channel', [
                '' => __('All Channels'),
                'email' => 'Email',
                'slack' => 'Slack',
                'discord' => 'Discord',
                'telegram' => 'Telegram',
                'webhook' => 'Webhook',
            ], [
                'value' => $this->request->getQuery('channel'),
                'class' => 'form-control',
            ]) ?>
        </div>
        <div class="filter-group">
            <label><?= __('Status') ?></label>
            <?= $this->Form->select('active', [
                '' => __('All'),
                '1' => __('Active'),
                '0' => __('Inactive'),
            ], [
                'value' => $this->request->getQuery('active'),
                'class' => 'form-control',
            ]) ?>
        </div>
        <div class="filter-group">
            <button type="submit" class="btn btn-primary"><?= __('Filter') ?></button>
            <?= $this->Html->link(__('Clear'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<div class="card">
    <table class="data-table">
        <thead>
            <tr>
                <th><?= __('Monitor') ?></th>
                <th><?= __('Channel') ?></th>
                <th><?= __('Trigger') ?></th>
                <th><?= __('Recipients') ?></th>
                <th><?= __('Cooldown') ?></th>
                <th><?= __('Status') ?></th>
                <th><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($alertRules) || (is_countable($alertRules) && count($alertRules) === 0)): ?>
                <tr>
                    <td colspan="7" class="text-center"><?= __('No alert rules configured. Create one to start receiving alerts.') ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($alertRules as $alertRule): ?>
                    <tr>
                        <td>
                            <?php if ($alertRule->monitor): ?>
                                <?= $this->Html->link(
                                    h($alertRule->monitor->name),
                                    ['controller' => 'Monitors', 'action' => 'view', $alertRule->monitor_id]
                                ) ?>
                            <?php else: ?>
                                <span class="text-muted"><?= __('Deleted') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-info"><?= h($alertRule->getChannelName()) ?></span>
                        </td>
                        <td><?= h($alertRule->getTriggerName()) ?></td>
                        <td>
                            <?php
                            $recipients = $alertRule->getRecipients();
                            $count = count($recipients);
                            if ($count > 0) {
                                echo h($recipients[0]);
                                if ($count > 1) {
                                    echo ' <span class="text-muted">+' . ($count - 1) . ' more</span>';
                                }
                            } else {
                                echo '<span class="text-muted">-</span>';
                            }
                            ?>
                        </td>
                        <td><?= $alertRule->throttle_minutes ?> <?= __('min') ?></td>
                        <td>
                            <?php if ($alertRule->active): ?>
                                <span class="badge badge-success"><?= __('Active') ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?= __('Inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="actions-cell">
                            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $alertRule->id], ['class' => 'btn btn-sm btn-secondary']) ?>
                            <?= $this->Form->postLink(
                                __('Delete'),
                                ['action' => 'delete', $alertRule->id],
                                [
                                    'confirm' => __('Are you sure? This action cannot be undone.'),
                                    'class' => 'btn btn-sm btn-danger',
                                ]
                            ) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="paginator">
        <ul class="pagination">
            <?= $this->Paginator->first('<< ' . __('First')) ?>
            <?= $this->Paginator->prev('< ' . __('Previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('Next') . ' >') ?>
            <?= $this->Paginator->last(__('Last') . ' >>') ?>
        </ul>
        <p class="paginator-counter"><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
    </div>
</div>
