<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $incidents
 * @var array $stats
 * @var array $monitors
 */
$this->assign('title', __d('incidents', 'Incidents'));
?>

<!-- Styles provided by admin.css -->

<div class="incidents-header">
    <h2>🚨 <?= __d('incidents', 'Incidents') ?></h2>
    <?= $this->Html->link('+ ' . __d('incidents', 'New Incident'), ['action' => 'add'], ['class' => 'btn-add']) ?>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('incidents', 'Total') ?></div>
        <div class="stat-value info"><?= number_format($stats['total']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('incidents', 'Active') ?></div>
        <div class="stat-value error"><?= number_format($stats['active']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('incidents', 'Resolved') ?></div>
        <div class="stat-value success"><?= number_format($stats['resolved']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('incidents', 'Critical') ?></div>
        <div class="stat-value warning"><?= number_format($stats['critical']) ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'filters-form']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label><?= __d('incidents', 'Search') ?></label>
            <?= $this->Form->control('search', [
                'label' => false,
                'placeholder' => __d('incidents', 'Title or description...'),
                'value' => $this->request->getQuery('search'),
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('incidents', 'Status') ?></label>
            <?= $this->Form->control('status', [
                'label' => false,
                'options' => [
                    '' => __d('incidents', 'All'),
                    'active' => __d('incidents', 'Active'),
                    'investigating' => __d('incidents', 'Investigating'),
                    'identified' => __d('incidents', 'Identified'),
                    'monitoring' => __d('incidents', 'Monitoring'),
                    'resolved' => __d('incidents', 'Resolved'),
                ],
                'value' => $this->request->getQuery('status'),
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('incidents', 'Severity') ?></label>
            <?= $this->Form->control('severity', [
                'label' => false,
                'options' => [
                    '' => __d('incidents', 'All'),
                    'critical' => __d('incidents', 'Critical'),
                    'major' => __d('incidents', 'Major'),
                    'minor' => __d('incidents', 'Minor'),
                    'maintenance' => __d('incidents', 'Maintenance'),
                ],
                'value' => $this->request->getQuery('severity'),
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('incidents', 'Monitor') ?></label>
            <?= $this->Form->control('monitor_id', [
                'label' => false,
                'options' => ['' => __d('incidents', 'All Monitors')] + $monitors,
                'value' => $this->request->getQuery('monitor_id'),
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>
    </div>

    <div class="filter-buttons">
        <?= $this->Form->button(__d('incidents', 'Filter'), ['type' => 'submit', 'class' => 'btn-filter']) ?>
        <?= $this->Html->link(__d('incidents', 'Clear'), ['action' => 'index'], ['class' => 'btn-clear']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>

<!-- Incidents Table -->
<div class="incidents-table">
    <?php if ($incidents->count() > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('status', __d('incidents', 'Status')) ?></th>
                    <th><?= $this->Paginator->sort('title', __d('incidents', 'Incident')) ?></th>
                    <th><?= $this->Paginator->sort('monitor_id', __d('incidents', 'Monitor')) ?></th>
                    <th><?= $this->Paginator->sort('severity', __d('incidents', 'Severity')) ?></th>
                    <th><?= $this->Paginator->sort('started_at', __d('incidents', 'Started')) ?></th>
                    <th><?= $this->Paginator->sort('duration', __d('incidents', 'Duration')) ?></th>
                    <th style="text-align: right;"><?= __d('incidents', 'Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($incidents as $incident): ?>
                    <tr class="<?= $incident->isResolved() ? 'resolved' : '' ?>">
                        <td>
                            <span class="badge badge-<?= $incident->isResolved() ? 'success' : 'danger' ?>">
                                <?= $incident->isResolved() ? '✅' : '⚠️' ?>
                                <?= h($incident->getStatusName()) ?>
                            </span>
                        </td>
                        <td>
                            <div class="incident-title">
                                <?= h($incident->title) ?>
                                <?php if ($incident->auto_created): ?>
                                    <span class="badge badge-secondary" title="<?= __d('incidents', 'Auto-created') ?>">&#x1F916;</span>
                                <?php endif; ?>
                                <?= $this->element('incidents/acknowledge_badge', ['incident' => $incident]) ?>
                            </div>
                            <?php if ($incident->description): ?>
                                <div class="incident-description" title="<?= h($incident->description) ?>">
                                    <?= h($incident->description) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $this->Html->link(
                                h($incident->monitor->name),
                                ['controller' => 'Monitors', 'action' => 'view', $incident->monitor->id],
                                ['class' => 'monitor-link']
                            ) ?>
                            <br>
                            <span style="color: #999; font-size: 12px;">
                                <?= h(strtoupper($incident->monitor->type)) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?= $incident->getSeverityBadgeClass() ?>">
                                <?= h(ucfirst($incident->severity)) ?>
                            </span>
                        </td>
                        <td>
                            <span class="local-datetime" data-utc="<?= $incident->started_at->format('c') ?>"></span>
                        </td>
                        <td>
                            <?php if ($incident->duration !== null): ?>
                                <span style="font-family: 'Courier New', monospace; color: #666;">
                                    <?php
                                    $duration = $incident->duration;
                                    if ($duration < 60) {
                                        echo "{$duration}s";
                                    } elseif ($duration < 3600) {
                                        $minutes = floor($duration / 60);
                                        echo "{$minutes}m";
                                    } else {
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        echo $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
                                    }
                                    ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999; font-size: 13px;">
                                    <?= $incident->isResolved() ? __d('incidents', 'N/A') : __d('incidents', 'In progress') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-buttons">
                                <?= $this->Html->link(
                                    __d('incidents', 'View'),
                                    ['action' => 'view', $incident->id],
                                    ['class' => 'btn-action btn-action-view', 'title' => __d('incidents', 'View details')]
                                ) ?>
                                <?php if (!$incident->isResolved()): ?>
                                    <?= $this->Html->link(
                                        __d('incidents', 'Edit'),
                                        ['action' => 'edit', $incident->id],
                                        ['class' => 'btn-action btn-action-edit', 'title' => __d('incidents', 'Edit')]
                                    ) ?>
                                    <?= $this->Form->postLink(
                                        __d('incidents', 'Resolve'),
                                        ['action' => 'resolve', $incident->id],
                                        [
                                            'class' => 'btn-action btn-action-resolve',
                                            'title' => __d('incidents', 'Resolve'),
                                            'confirm' => __d('incidents', 'Are you sure you want to resolve this incident?')
                                        ]
                                    ) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <?= $this->element('empty_state', [
            'icon' => '✅',
            'title' => __d('incidents', 'No incidents recorded'),
            'description' => __d('incidents', 'All systems running smoothly!'),
        ]) ?>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($incidents->count() > 0): ?>
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
