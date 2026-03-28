<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Subscriber> $subscribers
 * @var array $stats
 * @var string $period
 */
$this->assign('title', __d('subscribers', 'Notification Subscribers'));
?>

<!-- Styles provided by admin.css -->

<div class="subscribers-header">
    <h2><?= __d('subscribers', 'Notification Subscribers') ?></h2>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('subscribers', 'Total Subscribers') ?></div>
        <div class="stat-value info"><?= number_format($stats['total']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('subscribers', 'Verified') ?></div>
        <div class="stat-value success"><?= number_format($stats['verified']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('subscribers', 'Unverified') ?></div>
        <div class="stat-value warning"><?= number_format($stats['unverified']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('subscribers', 'Active') ?></div>
        <div class="stat-value success"><?= number_format($stats['active']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('subscribers', 'Added (7 days)') ?></div>
        <div class="stat-value info"><?= number_format($stats['recentlyAdded']) ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'filters-form']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label><?= __d('subscribers', 'Search') ?></label>
            <?= $this->Form->control('search', [
                'label' => false,
                'placeholder' => __d('subscribers', 'Email or name...'),
                'value' => $this->request->getQuery('search'),
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('subscribers', 'Verification Status') ?></label>
            <?= $this->Form->control('status', [
                'options' => [
                    '' => __d('subscribers', 'All'),
                    'verified' => __d('subscribers', 'Verified'),
                    'unverified' => __d('subscribers', 'Unverified'),
                ],
                'default' => $this->request->getQuery('status'),
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('subscribers', 'Active Status') ?></label>
            <?= $this->Form->control('active', [
                'options' => [
                    '' => __d('subscribers', 'All'),
                    'active' => __d('subscribers', 'Active'),
                    'inactive' => __d('subscribers', 'Inactive'),
                ],
                'default' => $this->request->getQuery('active'),
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('subscribers', 'Period') ?></label>
            <?= $this->Form->control('period', [
                'options' => [
                    '7d' => __d('subscribers', 'Last 7 days'),
                    '30d' => __d('subscribers', 'Last 30 days'),
                    '90d' => __d('subscribers', 'Last 90 days'),
                    'all' => __d('subscribers', 'All'),
                ],
                'default' => $period,
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-buttons">
            <?= $this->Form->button(__d('subscribers', 'Filter'), ['type' => 'submit', 'class' => 'btn-filter']) ?>
            <?= $this->Html->link(__d('subscribers', 'Clear'), ['action' => 'index'], ['class' => 'btn-clear']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<!-- Subscribers Table -->
<div class="subscribers-table">
    <?php if ($subscribers->count() > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('email', __d('subscribers', 'Email / Name')) ?></th>
                    <th><?= $this->Paginator->sort('verified', __d('subscribers', 'Verification')) ?></th>
                    <th><?= $this->Paginator->sort('active', __d('subscribers', 'Status')) ?></th>
                    <th><?= $this->Paginator->sort('created', __d('subscribers', 'Subscription Date')) ?></th>
                    <th><?= __d('subscribers', 'Subscriptions') ?></th>
                    <th style="text-align: right;"><?= __d('subscribers', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $subscriber): ?>
                    <tr>
                        <td>
                            <strong><?= h($subscriber->email) ?></strong>
                            <?php if ($subscriber->name): ?>
                                <br>
                                <span style="color: #999; font-size: 13px;">
                                    <?= h($subscriber->name) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($subscriber->verified): ?>
                                <span class="badge badge-success"><?= __d('subscribers', 'Verified') ?></span>
                                <?php if ($subscriber->verified_at): ?>
                                    <br>
                                    <span style="color: #999; font-size: 12px;">
                                        <?= $subscriber->verified_at->i18nFormat('dd/MM/yyyy HH:mm') ?>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-warning"><?= __d('subscribers', 'Pending') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($subscriber->active): ?>
                                <span class="badge badge-success"><?= __d('subscribers', 'Active') ?></span>
                            <?php else: ?>
                                <span class="badge badge-danger"><?= __d('subscribers', 'Inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= h($subscriber->created->i18nFormat('dd/MM/yyyy')) ?></strong>
                            <br>
                            <span style="color: #666; font-size: 13px;">
                                <?= h($subscriber->created->i18nFormat('HH:mm:ss')) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (isset($subscriber->subscriptions) && count($subscriber->subscriptions) > 0): ?>
                                <span class="badge badge-info">
                                    <?= count($subscriber->subscriptions) ?> <?= count($subscriber->subscriptions) > 1 ? __d('subscribers', 'monitors') : __d('subscribers', 'monitor') ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #ccc;"><?= __d('subscribers', 'None') ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-buttons">
                                <?= $this->Html->link(
                                    __d('subscribers', 'View'),
                                    ['action' => 'view', $subscriber->id],
                                    ['class' => 'btn-action btn-action-view', 'title' => __d('subscribers', 'View details')]
                                ) ?>
                                <?= $this->Form->postLink(
                                    $subscriber->active ? __d('subscribers', 'Deactivate') : __d('subscribers', 'Activate'),
                                    ['action' => 'toggle', $subscriber->id],
                                    [
                                        'class' => 'btn-action btn-action-toggle',
                                        'title' => $subscriber->active ? __d('subscribers', 'Deactivate subscriber') : __d('subscribers', 'Activate subscriber'),
                                        'confirm' => __d('subscribers', 'Tem certeza que deseja {0} este inscrito?', $subscriber->active ? __d('subscribers', 'deactivate') : __d('subscribers', 'activate'))
                                    ]
                                ) ?>
                                <?= $this->Form->postLink(
                                    __d('subscribers', 'Delete'),
                                    ['action' => 'delete', $subscriber->id],
                                    [
                                        'class' => 'btn-action btn-action-danger',
                                        'title' => __d('subscribers', 'Delete subscriber'),
                                        'confirm' => __d('subscribers', 'Are you sure you want to delete this subscriber? This action cannot be undone.')
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
            'icon' => '👥',
            'title' => __d('subscribers', 'No subscribers yet'),
        ]) ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($subscribers->count() > 0): ?>
    <div class="pagination">
        <?= $this->Paginator->first(__d('subscribers', '« First')) ?>
        <?= $this->Paginator->prev(__d('subscribers', '‹ Previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__d('subscribers', 'Next ›')) ?>
        <?= $this->Paginator->last(__d('subscribers', 'Last »')) ?>
    </div>
    <div class="pagination-info">
        <?= $this->Paginator->counter(__d('subscribers', 'Page {{page}} of {{pages}}, showing {{current}} record(s) of {{count}} total')) ?>
    </div>
<?php endif; ?>
