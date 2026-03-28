<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\StatusPage> $statusPages
 */
?>

<div class="content-header">
    <h1><?= __('Status Pages') ?></h1>
    <div class="header-actions">
        <?= $this->Html->link(
            __('+ New Status Page'),
            ['action' => 'add'],
            ['class' => 'btn btn-primary']
        ) ?>
    </div>
</div>

<div class="card">
    <?php if (count($statusPages) === 0): ?>
        <?= $this->element('empty_state', [
            'icon' => '🌐',
            'title' => __('No status pages created'),
            'description' => __('Create a public status page for your customers.'),
            'actionUrl' => $this->Url->build(['action' => 'add']),
            'actionLabel' => __('Create Status Page'),
        ]) ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th><?= __('Name') ?></th>
                        <th><?= __('Slug') ?></th>
                        <th><?= __('Custom Domain') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Password') ?></th>
                        <th><?= __('Created') ?></th>
                        <th><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statusPages as $statusPage): ?>
                    <tr>
                        <td><strong><?= h($statusPage->name) ?></strong></td>
                        <td><code><?= h($statusPage->slug) ?></code></td>
                        <td><?= $statusPage->custom_domain ? h($statusPage->custom_domain) : '<span class="text-muted">-</span>' ?></td>
                        <td>
                            <?php if ($statusPage->active): ?>
                                <span class="badge badge-success"><?= __('Active') ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?= __('Inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($statusPage->isPasswordProtected()): ?>
                                <span class="badge badge-warning"><?= __('Protected') ?></span>
                            <?php else: ?>
                                <span class="text-muted"><?= __('Public') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= h($statusPage->created->format('Y-m-d H:i')) ?></td>
                        <td class="actions">
                            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $statusPage->id], ['class' => 'btn btn-sm btn-secondary']) ?>
                            <?= $this->Form->postLink(
                                __('Delete'),
                                ['action' => 'delete', $statusPage->id],
                                ['confirm' => __('Are you sure you want to delete {0}?', $statusPage->name), 'class' => 'btn btn-sm btn-danger']
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
