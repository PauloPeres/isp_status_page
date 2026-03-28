<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $user
 * @var int $remainingCodes
 * @var array $recoveryCodes
 */
$this->assign('title', __('Recovery Codes'));
?>

<style>
.recovery-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.recovery-header h1 {
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

.btn-primary {
    background: #1E88E5;
    color: white;
}

.btn-primary:hover {
    background: #1976D2;
}

.btn-warning {
    background: #F59E0B;
    color: white;
}

.btn-warning:hover {
    background: #D97706;
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

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}

.status-ok {
    background: #E8F5E9;
    color: #2E7D32;
}

.status-warning {
    background: #FFF9E6;
    color: #F57C00;
}

.status-danger {
    background: #FFEBEE;
    color: #C62828;
}

.recovery-codes {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    margin: 16px 0;
}

.recovery-code {
    background: #f5f5f5;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 8px 12px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    text-align: center;
    user-select: all;
}

.recovery-warning {
    background: #FFF9E6;
    border: 1px solid #FDD835;
    border-radius: 6px;
    padding: 12px 16px;
    font-size: 14px;
    color: #856404;
    margin-bottom: 16px;
    line-height: 1.5;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #444;
    margin-bottom: 6px;
}

.form-group input[type="password"] {
    padding: 8px 12px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    font-size: 14px;
    width: 300px;
    max-width: 100%;
}

.form-group input:focus {
    outline: none;
    border-color: #1E88E5;
}

@media (max-width: 768px) {
    .recovery-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .recovery-codes {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="recovery-codes-page">
    <div class="recovery-header">
        <h1><?= __('Recovery Codes') ?></h1>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(__('Back to Profile'), ['controller' => 'Users', 'action' => 'edit', $user->id], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><?= __('Recovery Code Status') ?></div>

        <p style="margin-bottom: 12px; font-size: 14px; color: #666;">
            <?= __('Recovery codes can be used to access your account if you lose your authenticator device.') ?>
        </p>

        <p style="margin-bottom: 16px;">
            <strong><?= __('Remaining codes:') ?></strong>
            <?php if ($remainingCodes >= 5): ?>
                <span class="status-badge status-ok"><?= h($remainingCodes) ?></span>
            <?php elseif ($remainingCodes >= 2): ?>
                <span class="status-badge status-warning"><?= h($remainingCodes) ?></span>
            <?php else: ?>
                <span class="status-badge status-danger"><?= h($remainingCodes) ?></span>
            <?php endif; ?>
        </p>

        <?php if ($remainingCodes <= 2): ?>
            <div class="recovery-warning">
                <strong><?= __('Warning:') ?></strong>
                <?= __('You are running low on recovery codes. Consider regenerating them.') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($recoveryCodes)): ?>
            <div class="recovery-warning">
                <strong><?= __('Important:') ?></strong>
                <?= __('Save these new recovery codes in a safe place. Each code can only be used once. These codes replace all previous codes.') ?>
            </div>

            <div class="recovery-codes">
                <?php foreach ($recoveryCodes as $code): ?>
                    <div class="recovery-code"><?= h($code) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header"><?= __('Regenerate Recovery Codes') ?></div>

        <p style="margin-bottom: 16px; font-size: 14px; color: #666;">
            <?= __('This will invalidate all existing recovery codes and generate new ones. Enter your password to confirm.') ?>
        </p>

        <?= $this->Form->create(null, ['url' => ['action' => 'recoveryCodes']]) ?>
        <div class="form-group">
            <label><?= __('Password') ?></label>
            <input type="password" name="password" required autocomplete="current-password" placeholder="<?= __('Enter your password') ?>">
        </div>
        <button type="submit" class="btn btn-warning"><?= __('Regenerate Recovery Codes') ?></button>
        <?= $this->Form->end() ?>
    </div>
</div>
