<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
$this->assign('title', __d('users', 'My Profile'));
?>

<style>
.users-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.users-header h1 {
    margin: 0;
    font-size: 24px;
    color: #333;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    display: inline-block;
}

.btn-primary {
    background: #f59e0b;
    color: white;
}

.btn-primary:hover {
    background: #d97706;
    color: white;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
    color: white;
}

.card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    margin-bottom: 24px;
}

.card-header {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e0e0e0;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 6px;
}

.detail-label {
    font-size: 13px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.detail-value {
    font-size: 14px;
    color: #333;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-success {
    background: #dcfce7;
    color: #16a34a;
}

.badge-danger {
    background: #fee2e2;
    color: #dc2626;
}

.badge-info {
    background: #dbeafe;
    color: #1d4ed8;
}

.badge-secondary {
    background: #f3f4f6;
    color: #6b7280;
}

.text-muted {
    color: #999;
    font-style: italic;
}

@media (max-width: 768px) {
    .users-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="users-view">
    <div class="users-header">
        <h1><?= __d('users', 'My Profile') ?></h1>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(__d('users', 'Edit Profile'), ['action' => 'edit', $user->id], ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(__('Back'), ['controller' => 'Admin', 'action' => 'index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><?= __d('users', 'User Information') ?></div>
        <div class="details-grid">
            <div class="detail-item">
                <span class="detail-label"><?= __d('users', 'Username') ?></span>
                <span class="detail-value"><?= h($user->username) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __('Email') ?></span>
                <span class="detail-value"><?= h($user->email) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __d('users', 'Role') ?></span>
                <span class="detail-value">
                    <span class="badge <?= $user->role === 'admin' ? 'badge-danger' : 'badge-info' ?>">
                        <?= h($user->getRoleName()) ?>
                    </span>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __('Status') ?></span>
                <span class="detail-value">
                    <?php if ($user->active): ?>
                        <span class="badge badge-success"><?= __('Active') ?></span>
                    <?php else: ?>
                        <span class="badge badge-danger"><?= __('Inactive') ?></span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __d('users', 'Last Login') ?></span>
                <span class="detail-value">
                    <?php if ($user->last_login): ?>
                        <?= $user->last_login->format('d/m/Y H:i:s') ?>
                    <?php else: ?>
                        <span class="text-muted"><?= __d('users', 'Never') ?></span>
                    <?php endif; ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __d('users', 'Account Created') ?></span>
                <span class="detail-value"><?= $user->created->format('d/m/Y H:i:s') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label"><?= __('Last Modified') ?></span>
                <span class="detail-value"><?= $user->modified->format('d/m/Y H:i:s') ?></span>
            </div>
        </div>
    </div>
</div>
