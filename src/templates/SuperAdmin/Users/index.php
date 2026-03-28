<?php
/**
 * Super Admin — Users List
 *
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\User> $users
 * @var string|null $search
 */
$this->assign('title', __('Users'));
?>

<div class="dashboard-header">
    <h1><?= __('Users') ?></h1>
    <p><?= __('Cross-organization user directory') ?></p>
</div>

<!-- Search Bar -->
<div class="table-card" style="margin-bottom: 24px;">
    <form method="get" action="<?= $this->Url->build(['prefix' => 'SuperAdmin', 'controller' => 'Users', 'action' => 'index']) ?>" style="display: flex; gap: 12px; align-items: center;">
        <input type="text" name="search" value="<?= h($search ?? '') ?>" placeholder="<?= __('Search by username or email...') ?>" class="form-control" style="flex: 1; padding: 10px 14px; border: 1px solid #333; border-radius: 6px; background: #1a1a2e; color: #e0e0e0; font-size: 14px;">
        <button type="submit" class="btn btn-primary" style="padding: 10px 20px; white-space: nowrap;"><?= __('Search') ?></button>
        <?php if ($search): ?>
            <a href="<?= $this->Url->build(['prefix' => 'SuperAdmin', 'controller' => 'Users', 'action' => 'index']) ?>" class="btn btn-secondary" style="padding: 10px 20px; white-space: nowrap;"><?= __('Clear') ?></a>
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<div class="table-card">
    <?php if (count($users) > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?= __('Username') ?></th>
                        <th><?= __('Email') ?></th>
                        <th><?= __('Organizations') ?></th>
                        <th><?= __('Last Login') ?></th>
                        <th><?= __('Created') ?></th>
                        <th><?= __('Super Admin') ?></th>
                        <th><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?= h($user->username) ?></strong>
                                <?php if (!$user->active): ?>
                                    <span class="badge badge-secondary" style="margin-left: 6px;"><?= __('Inactive') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= h($user->email) ?></td>
                            <td>
                                <?php if (!empty($user->organization_users)): ?>
                                    <?php foreach ($user->organization_users as $ou): ?>
                                        <?php
                                            $roleBadge = match ($ou->role ?? '') {
                                                'owner' => 'badge-danger',
                                                'admin' => 'badge-warning',
                                                'member' => 'badge-primary',
                                                default => 'badge-secondary',
                                            };
                                        ?>
                                        <span class="badge <?= $roleBadge ?>" style="margin: 2px 4px 2px 0;">
                                            <?= h($ou->organization->name ?? __('Unknown')) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: #666;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user->last_login): ?>
                                    <?= $user->last_login->nice() ?>
                                <?php else: ?>
                                    <span style="color: #666;"><?= __('Never') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $user->created->nice() ?>
                            </td>
                            <td>
                                <?php if ($user->is_super_admin): ?>
                                    <span class="badge badge-danger"><?= __('Super Admin') ?></span>
                                <?php else: ?>
                                    <span style="color: #666;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $this->Html->link(
                                    __('View'),
                                    ['prefix' => 'SuperAdmin', 'controller' => 'Users', 'action' => 'view', $user->id],
                                    ['class' => 'btn btn-sm btn-primary']
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="paginator" style="margin-top: 16px; display: flex; justify-content: center; gap: 8px; flex-wrap: wrap;">
            <?= $this->Paginator->first(__('First')) ?>
            <?= $this->Paginator->prev(__('Previous')) ?>
            <?= $this->Paginator->numbers() ?>
            <?= $this->Paginator->next(__('Next')) ?>
            <?= $this->Paginator->last(__('Last')) ?>
        </div>
        <div style="text-align: center; margin-top: 8px; color: #999; font-size: 13px;">
            <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} of {{count}} users')) ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <?php if ($search): ?>
                <?= __('No users found matching "{0}".', h($search)) ?>
            <?php else: ?>
                <?= __('No users found.') ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
