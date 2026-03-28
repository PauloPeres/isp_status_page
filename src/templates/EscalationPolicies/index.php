<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\EscalationPolicy> $escalationPolicies
 */
$this->assign('title', __('Escalation Policies'));
?>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('Escalation Policies'), 'url' => null],
]]) ?>

<div class="escalation-policies-index">
    <div class="page-header">
        <div>
            <h1><?= __('Escalation Policies') ?></h1>
            <p><?= __('Define alert escalation workflows for unacknowledged incidents') ?></p>
        </div>
        <?= $this->Html->link(
            '+ ' . __('New Policy'),
            ['action' => 'add'],
            ['class' => 'btn btn-primary']
        ) ?>
    </div>

    <div class="card">
        <?php if (count($escalationPolicies) === 0): ?>
            <div class="empty-state">
                <p><?= __('No escalation policies yet.') ?></p>
                <p class="form-help"><?= __('Create a policy to automatically escalate alerts when incidents are not acknowledged.') ?></p>
                <?= $this->Html->link(
                    '+ ' . __('Create First Policy'),
                    ['action' => 'add'],
                    ['class' => 'btn btn-primary']
                ) ?>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th><?= __('Name') ?></th>
                        <th><?= __('Steps') ?></th>
                        <th><?= __('Monitors Using') ?></th>
                        <th><?= __('Repeat') ?></th>
                        <th><?= __('Active') ?></th>
                        <th><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($escalationPolicies as $policy): ?>
                    <tr>
                        <td>
                            <?= $this->Html->link(
                                h($policy->name),
                                ['action' => 'view', $policy->id],
                                ['class' => 'link-primary']
                            ) ?>
                            <?php if ($policy->description): ?>
                                <br><small class="text-muted"><?= h(\Cake\Utility\Text::truncate($policy->description, 60)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-info"><?= $policy->getStepCount() ?> <?= __('step(s)') ?></span>
                        </td>
                        <td>
                            <span class="badge badge-secondary"><?= $policy->getMonitorCount() ?> <?= __('monitor(s)') ?></span>
                        </td>
                        <td>
                            <?php if ($policy->repeat_enabled): ?>
                                <span class="badge badge-warning"><?= __('Every {0} min', $policy->repeat_after_minutes) ?></span>
                            <?php else: ?>
                                <span class="text-muted"><?= __('No') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($policy->active): ?>
                                <span class="badge badge-success"><?= __('Active') ?></span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?= __('Inactive') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <?= $this->Html->link(__('View'), ['action' => 'view', $policy->id], ['class' => 'btn btn-sm btn-secondary']) ?>
                            <?= $this->Html->link(__('Edit'), ['action' => 'edit', $policy->id], ['class' => 'btn btn-sm btn-primary']) ?>
                            <?= $this->Form->postLink(
                                __('Delete'),
                                ['action' => 'delete', $policy->id],
                                [
                                    'class' => 'btn btn-sm btn-error',
                                    'confirm' => __('Are you sure? This action cannot be undone.'),
                                ]
                            ) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="paginator">
                <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.escalation-policies-index .page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}
.escalation-policies-index .page-header h1 {
    font-size: 24px;
    margin-bottom: 4px;
}
.escalation-policies-index .page-header p {
    color: var(--color-gray-medium);
    font-size: 14px;
}
.empty-state {
    text-align: center;
    padding: 48px 24px;
}
.empty-state p:first-child {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-dark);
}
.actions {
    white-space: nowrap;
}
.actions .btn {
    margin-right: 4px;
}
.btn-sm {
    padding: 4px 10px;
    font-size: 13px;
}
.link-primary {
    color: var(--color-primary);
    font-weight: 600;
    text-decoration: none;
}
.link-primary:hover {
    text-decoration: underline;
}
.text-muted {
    color: var(--color-gray-medium);
}
</style>
