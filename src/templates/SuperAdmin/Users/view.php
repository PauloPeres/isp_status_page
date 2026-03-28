<?php
/**
 * Super Admin — User Detail
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var iterable $apiKeys
 */
$this->assign('title', __('User: {0}', $user->username));
?>

<div class="dashboard-header">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1><?= h($user->username) ?></h1>
            <p><?= h($user->email) ?></p>
        </div>
        <div>
            <?= $this->Html->link(
                __('Back to Users'),
                ['prefix' => 'SuperAdmin', 'controller' => 'Users', 'action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>
    </div>
</div>

<!-- User Info Card -->
<div class="summary-grid">
    <div class="summary-card">
        <div class="card-label"><?= __('Status') ?></div>
        <div class="card-value">
            <?php if ($user->active): ?>
                <span class="badge badge-success"><?= __('Active') ?></span>
            <?php else: ?>
                <span class="badge badge-secondary"><?= __('Inactive') ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Super Admin') ?></div>
        <div class="card-value">
            <?php if ($user->is_super_admin): ?>
                <span class="badge badge-danger"><?= __('Yes') ?></span>
            <?php else: ?>
                <span style="color: #999;"><?= __('No') ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Created') ?></div>
        <div class="card-value" style="font-size: 16px;">
            <span class="local-datetime" data-utc="<?= $user->created->format('c') ?>">
                <?= h($user->created->format('Y-m-d H:i')) ?>
            </span>
        </div>
    </div>
    <div class="summary-card">
        <div class="card-label"><?= __('Last Login') ?></div>
        <div class="card-value" style="font-size: 16px;">
            <?php if ($user->last_login): ?>
                <span class="local-datetime" data-utc="<?= $user->last_login->format('c') ?>">
                    <?= h($user->last_login->format('Y-m-d H:i')) ?>
                </span>
            <?php else: ?>
                <span style="color: #666;"><?= __('Never') ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Organizations Membership -->
<div class="table-card" style="margin-top: 24px;">
    <h3><?= __('Organizations') ?></h3>
    <?php if (!empty($user->organization_users)): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?= __('Organization') ?></th>
                        <th><?= __('Role') ?></th>
                        <th><?= __('Joined') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user->organization_users as $ou): ?>
                        <tr>
                            <td>
                                <?php if (!empty($ou->organization)): ?>
                                    <?= $this->Html->link(
                                        h($ou->organization->name),
                                        ['prefix' => 'SuperAdmin', 'controller' => 'Organizations', 'action' => 'view', $ou->organization->id],
                                        ['style' => 'color: #60a5fa;']
                                    ) ?>
                                <?php else: ?>
                                    <span style="color: #666;"><?= __('Unknown') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $roleBadge = match ($ou->role ?? '') {
                                        'owner' => 'badge-danger',
                                        'admin' => 'badge-warning',
                                        'member' => 'badge-primary',
                                        default => 'badge-secondary',
                                    };
                                ?>
                                <span class="badge <?= $roleBadge ?>"><?= h(ucfirst($ou->role ?? 'unknown')) ?></span>
                            </td>
                            <td>
                                <?php if (!empty($ou->accepted_at)): ?>
                                    <span class="local-datetime" data-utc="<?= $ou->accepted_at->format('c') ?>">
                                        <?= h($ou->accepted_at->format('Y-m-d')) ?>
                                    </span>
                                <?php elseif (!empty($ou->created)): ?>
                                    <span class="local-datetime" data-utc="<?= $ou->created->format('c') ?>">
                                        <?= h($ou->created->format('Y-m-d')) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #666;">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><?= __('This user is not a member of any organization.') ?></div>
    <?php endif; ?>
</div>

<!-- API Keys -->
<div class="table-card" style="margin-top: 24px;">
    <h3><?= __('API Keys') ?></h3>
    <?php if (count($apiKeys) > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th><?= __('Name') ?></th>
                        <th><?= __('Prefix') ?></th>
                        <th><?= __('Permissions') ?></th>
                        <th><?= __('Last Used') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apiKeys as $key): ?>
                        <tr>
                            <td>
                                <strong><?= h($key->name) ?></strong>
                                <?php if (!$key->active): ?>
                                    <span class="badge badge-secondary" style="margin-left: 6px;"><?= __('Inactive') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="background: #1a1a2e; padding: 2px 8px; border-radius: 4px; font-size: 13px;">
                                    <?= h($key->prefix ?? substr($key->key ?? '', 0, 8) . '...') ?>
                                </code>
                            </td>
                            <td>
                                <?php
                                    $permissions = $key->permissions ?? [];
                                    if (is_string($permissions)) {
                                        $permissions = json_decode($permissions, true) ?? [];
                                    }
                                    if (!empty($permissions)):
                                        foreach ($permissions as $perm):
                                ?>
                                    <span class="badge badge-primary" style="margin: 2px 4px 2px 0;"><?= h($perm) ?></span>
                                <?php
                                        endforeach;
                                    else:
                                ?>
                                    <span style="color: #666;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($key->last_used_at)): ?>
                                    <span class="local-datetime" data-utc="<?= $key->last_used_at->format('c') ?>">
                                        <?= h($key->last_used_at->format('Y-m-d H:i')) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #666;"><?= __('Never') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><?= __('No API keys found for this user.') ?></div>
    <?php endif; ?>
</div>
