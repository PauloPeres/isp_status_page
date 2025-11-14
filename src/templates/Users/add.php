<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
$this->assign('title', __d('users', 'New User'));
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

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
    color: white;
}

.btn-success {
    background: #22c55e;
    color: white;
}

.btn-success:hover {
    background: #16a34a;
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

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #444;
    margin-bottom: 6px;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group select {
    padding: 8px 12px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #3b82f6;
}

.form-group input.error,
.form-group select.error {
    border-color: #ef4444;
}

.error-message {
    color: #ef4444;
    font-size: 12px;
    margin-top: 4px;
}

.form-section {
    margin-bottom: 24px;
}

.form-section-title {
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-help {
    font-size: 13px;
    color: #666;
    margin-bottom: 12px;
}

.form-buttons {
    display: flex;
    gap: 8px;
    margin-top: 24px;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .users-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .form-buttons {
        flex-direction: column;
    }

    .form-buttons .btn {
        width: 100%;
    }
}
</style>

<div class="users-add">
    <div class="users-header">
        <h1><?= __d('users', 'New User') ?></h1>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(__('Back'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><?= __d('users', 'User Information') ?></div>

        <?= $this->Form->create($user) ?>

        <div class="form-section">
            <div class="form-section-title"><?= __d('users', 'Access Data') ?></div>

            <div class="form-row">
                <div class="form-group">
                    <label><?= __d('users', 'Username') ?> *</label>
                    <?= $this->Form->control('username', [
                        'label' => false,
                        'required' => true,
                        'placeholder' => __d('users', 'Enter username')
                    ]) ?>
                </div>

                <div class="form-group">
                    <label><?= __('Email') ?> *</label>
                    <?= $this->Form->control('email', [
                        'label' => false,
                        'type' => 'email',
                        'required' => true,
                        'placeholder' => __d('users', 'Enter email')
                    ]) ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?= __('Password') ?> *</label>
                    <?= $this->Form->control('password', [
                        'label' => false,
                        'type' => 'password',
                        'required' => true,
                        'placeholder' => __d('users', 'Minimum 8 characters')
                    ]) ?>
                </div>

                <div class="form-group">
                    <label><?= __('Confirm Password') ?> *</label>
                    <?= $this->Form->control('confirm_password', [
                        'label' => false,
                        'type' => 'password',
                        'required' => true,
                        'placeholder' => __d('users', 'Re-enter password')
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><?= __d('users', 'Permissions') ?></div>

            <div class="form-row">
                <div class="form-group">
                    <label><?= __d('users', 'Role') ?> *</label>
                    <?= $this->Form->control('role', [
                        'label' => false,
                        'type' => 'select',
                        'options' => [
                            'admin' => __d('users', 'Administrator'),
                            'user' => __d('users', 'User'),
                            'viewer' => __d('users', 'Viewer')
                        ],
                        'empty' => __d('users', 'Select a role'),
                        'required' => true
                    ]) ?>
                </div>

                <div class="form-group">
                    <label><?= __('Status') ?></label>
                    <div class="checkbox-group">
                        <?= $this->Form->checkbox('active', ['checked' => true]) ?>
                        <span><?= __d('users', 'Active user') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-buttons">
            <?= $this->Form->button(__d('users', 'Create User'), ['class' => 'btn btn-success']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
