<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Invitation> $invitations
 */
?>

<?php $this->assign('title', __('Team Invitations')); ?>

<div class="content-header">
    <h1><?= __('Team Invitations') ?></h1>
</div>

<!-- Send Invitation Form -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header">
        <h3><?= __('Send New Invitation') ?></h3>
    </div>
    <div class="card-body">
        <?= $this->Form->create(null, ['url' => ['action' => 'send']]) ?>
        <div style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 250px;">
                <label for="email"><?= __('Email Address') ?></label>
                <?= $this->Form->email('email', [
                    'required' => true,
                    'placeholder' => __('colleague@example.com'),
                    'class' => 'form-control',
                ]) ?>
            </div>
            <div style="min-width: 150px;">
                <label for="role"><?= __('Role') ?></label>
                <?= $this->Form->select('role', [
                    'member' => __('Member'),
                    'admin' => __('Admin'),
                    'viewer' => __('Viewer'),
                ], [
                    'default' => 'member',
                    'class' => 'form-control',
                ]) ?>
            </div>
            <div>
                <?= $this->Form->button(__('Send Invitation'), [
                    'type' => 'submit',
                    'class' => 'btn btn-primary',
                ]) ?>
            </div>
        </div>
        <?= $this->Form->end() ?>
    </div>
</div>

<!-- Invitations List -->
<div class="card">
    <div class="card-header">
        <h3><?= __('Invitations') ?></h3>
    </div>
    <div class="card-body">
        <?php if ($invitations->isEmpty()): ?>
            <?= $this->element('empty_state', [
                'icon' => '📨',
                'title' => __('No invitations sent yet'),
                'description' => __('Use the form above to invite team members.'),
            ]) ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><?= __('Email') ?></th>
                            <th><?= __('Role') ?></th>
                            <th><?= __('Invited By') ?></th>
                            <th><?= __('Status') ?></th>
                            <th><?= __('Sent') ?></th>
                            <th><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invitations as $invitation): ?>
                            <tr>
                                <td><?= h($invitation->email) ?></td>
                                <td>
                                    <span class="badge badge-<?= $invitation->role === 'admin' ? 'warning' : ($invitation->role === 'viewer' ? 'secondary' : 'info') ?>">
                                        <?= h(ucfirst($invitation->role)) ?>
                                    </span>
                                </td>
                                <td><?= h($invitation->inviter->username ?? __('Unknown')) ?></td>
                                <td>
                                    <?php if ($invitation->isAccepted()): ?>
                                        <span class="badge badge-success"><?= __('Accepted') ?></span>
                                    <?php elseif ($invitation->isExpired()): ?>
                                        <span class="badge badge-secondary"><?= __('Expired') ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?= __('Pending') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $invitation->created->nice() ?></td>
                                <td>
                                    <?php if ($invitation->isPending()): ?>
                                        <?= $this->Form->postLink(
                                            __('Revoke'),
                                            ['action' => 'revoke', $invitation->id],
                                            [
                                                'confirm' => __('Are you sure you want to revoke this invitation?'),
                                                'class' => 'btn btn-sm btn-danger',
                                            ]
                                        ) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
