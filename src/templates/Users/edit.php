<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 */
$this->assign('title', __d('users', 'Edit Profile'));
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

.form-group select {
    padding: 8px 12px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.form-group select:focus {
    outline: none;
    border-color: #3b82f6;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"] {
    padding: 8px 12px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.form-group input:focus {
    outline: none;
    border-color: #3b82f6;
}

.form-group input.error {
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

@media (max-width: 768px) {
    .users-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .form-control,
    .form-group input,
    .form-group select,
    .form-group textarea {
        font-size: 16px;
        min-height: 44px;
    }

    .form-buttons {
        flex-direction: column;
    }

    .form-buttons .btn {
        width: 100%;
        min-height: 44px;
    }
}
</style>

<div class="users-edit">
    <div class="users-header">
        <h1><?= __d('users', 'Edit Profile') ?></h1>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(__('Back'), ['action' => 'view', $user->id], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><?= __d('users', 'Update Information') ?></div>

        <?= $this->Form->create($user) ?>

        <div class="form-section">
            <div class="form-section-title"><?= __d('users', 'Basic Information') ?></div>

            <div class="form-row">
                <div class="form-group">
                    <label><?= __d('users', 'Username') ?></label>
                    <?= $this->Form->control('username', [
                        'label' => false,
                        'required' => true,
                        'placeholder' => __d('users', 'Enter your username')
                    ]) ?>
                </div>

                <div class="form-group">
                    <label><?= __('Email') ?></label>
                    <?= $this->Form->control('email', [
                        'label' => false,
                        'type' => 'email',
                        'required' => true,
                        'placeholder' => __d('users', 'Enter your email')
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><?= __d('users', 'Preferences') ?></div>

            <div class="form-row">
                <div class="form-group">
                    <label><?= __d('users', 'Language') ?></label>
                    <?= $this->Form->control('language', [
                        'label' => false,
                        'type' => 'select',
                        'options' => [
                            'en' => __d('users', 'English'),
                            'pt_BR' => __d('users', 'Portuguese (Brazil)'),
                            'es' => __d('users', 'Spanish'),
                        ],
                        'default' => $user->language ?? 'en',
                    ]) ?>
                </div>

                <div class="form-group">
                    <label><?= __d('users', 'Timezone') ?></label>
                    <?= $this->Form->control('timezone', [
                        'label' => false,
                        'type' => 'select',
                        'options' => [
                            'America/Sao_Paulo' => __d('users', 'America/Sao Paulo (BRT)'),
                            'America/New_York' => __d('users', 'America/New York (EST)'),
                            'America/Chicago' => __d('users', 'America/Chicago (CST)'),
                            'America/Denver' => __d('users', 'America/Denver (MST)'),
                            'America/Los_Angeles' => __d('users', 'America/Los Angeles (PST)'),
                            'America/Argentina/Buenos_Aires' => __d('users', 'America/Buenos Aires (ART)'),
                            'America/Bogota' => __d('users', 'America/Bogota (COT)'),
                            'America/Santiago' => __d('users', 'America/Santiago (CLT)'),
                            'America/Lima' => __d('users', 'America/Lima (PET)'),
                            'America/Mexico_City' => __d('users', 'America/Mexico City (CST)'),
                            'Europe/London' => __d('users', 'Europe/London (GMT)'),
                            'Europe/Paris' => __d('users', 'Europe/Paris (CET)'),
                            'Europe/Berlin' => __d('users', 'Europe/Berlin (CET)'),
                            'Europe/Lisbon' => __d('users', 'Europe/Lisbon (WET)'),
                            'Europe/Madrid' => __d('users', 'Europe/Madrid (CET)'),
                            'Asia/Tokyo' => __d('users', 'Asia/Tokyo (JST)'),
                            'Asia/Shanghai' => __d('users', 'Asia/Shanghai (CST)'),
                            'Asia/Kolkata' => __d('users', 'Asia/Kolkata (IST)'),
                            'Australia/Sydney' => __d('users', 'Australia/Sydney (AEST)'),
                            'Pacific/Auckland' => __d('users', 'Pacific/Auckland (NZST)'),
                            'UTC' => 'UTC',
                        ],
                        'default' => $user->timezone ?? 'America/Sao_Paulo',
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title"><?= __d('users', 'Change Password') ?></div>
            <div class="form-help"><?= __d('users', 'Leave blank if you do not want to change the password') ?></div>

            <div class="form-row">
                <div class="form-group">
                    <label><?= __d('users', 'Current Password') ?></label>
                    <?= $this->Form->control('current_password', [
                        'label' => false,
                        'type' => 'password',
                        'required' => false,
                        'value' => '',
                        'placeholder' => __d('users', 'Enter your current password')
                    ]) ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label><?= __d('users', 'New Password') ?></label>
                    <?= $this->Form->control('new_password', [
                        'label' => false,
                        'type' => 'password',
                        'required' => false,
                        'value' => '',
                        'placeholder' => __d('users', 'Minimum 8 characters')
                    ]) ?>
                </div>

                <div class="form-group">
                    <label><?= __d('users', 'Confirm New Password') ?></label>
                    <?= $this->Form->control('confirm_password', [
                        'label' => false,
                        'type' => 'password',
                        'required' => false,
                        'value' => '',
                        'placeholder' => __d('users', 'Re-enter password')
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="form-buttons">
            <?= $this->Form->button(__('Save Changes'), ['class' => 'btn btn-success']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'view', $user->id], ['class' => 'btn btn-secondary']) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>
