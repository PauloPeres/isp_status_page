<?php
/**
 * @var \App\View\AppView $this
 * @var string|null $plainKey
 * @var \App\Model\Entity\ApiKey|null $newApiKey
 */
$this->assign('title', __('Create API Key'));
?>

<style>
    .apikeys-add-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .form-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 24px;
        max-width: 600px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #333;
        margin-bottom: 6px;
    }

    .form-group input[type="text"] {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        font-size: 14px;
        background: white;
        box-sizing: border-box;
    }

    .form-group input[type="text"]:focus {
        outline: none;
        border-color: #1E88E5;
        box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
    }

    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .checkbox-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .checkbox-item input[type="checkbox"] {
        margin-top: 3px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .checkbox-label {
        display: flex;
        flex-direction: column;
    }

    .checkbox-label strong {
        font-size: 14px;
        color: #333;
    }

    .checkbox-label span {
        font-size: 12px;
        color: #666;
    }

    .btn-submit {
        padding: 10px 24px;
        background: #1E88E5;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
    }

    .btn-submit:hover {
        background: #1565C0;
    }

    .btn-back {
        padding: 10px 24px;
        background: white;
        color: #666;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        display: inline-block;
    }

    .btn-back:hover {
        background: #f8f9fa;
    }

    .form-buttons {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .key-alert {
        background: #fef3c7;
        border: 2px solid #f59e0b;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
        max-width: 600px;
    }

    .key-alert-title {
        font-size: 16px;
        font-weight: 700;
        color: #92400e;
        margin-bottom: 8px;
    }

    .key-alert-text {
        font-size: 13px;
        color: #92400e;
        margin-bottom: 12px;
    }

    .key-display {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .key-value {
        font-family: 'Courier New', monospace;
        background: white;
        border: 1px solid #d97706;
        border-radius: 4px;
        padding: 10px 14px;
        font-size: 14px;
        color: #333;
        word-break: break-all;
        flex: 1;
        min-width: 200px;
    }

    .btn-copy {
        padding: 10px 16px;
        background: #f59e0b;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }

    .btn-copy:hover {
        background: #d97706;
    }

    @media (max-width: 768px) {
        .form-card {
            max-width: 100%;
        }

        .key-alert {
            max-width: 100%;
        }

        .key-display {
            flex-direction: column;
        }

        .key-value {
            width: 100%;
        }

        .form-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="apikeys-add-header">
    <h2><?= __('Create API Key') ?></h2>
</div>

<?php if (!empty($plainKey)): ?>
    <div class="key-alert">
        <div class="key-alert-title"><?= __('Your new API key') ?></div>
        <div class="key-alert-text"><?= __('Copy this key now. You will not be able to see it again!') ?></div>
        <div class="key-display">
            <code class="key-value" id="apiKeyValue"><?= h($plainKey) ?></code>
            <button class="btn-copy" onclick="copyApiKey()" id="copyBtn"><?= __('Copy') ?></button>
        </div>
    </div>

    <div class="form-buttons">
        <?= $this->Html->link(__('Back to API Keys'), ['action' => 'index'], ['class' => 'btn-back']) ?>
    </div>

    <script>
    function copyApiKey() {
        var keyValue = document.getElementById('apiKeyValue').innerText;
        navigator.clipboard.writeText(keyValue).then(function() {
            var btn = document.getElementById('copyBtn');
            btn.textContent = '<?= __('Copied!') ?>';
            setTimeout(function() {
                btn.textContent = '<?= __('Copy') ?>';
            }, 2000);
        });
    }
    </script>
<?php else: ?>
    <div class="form-card">
        <?= $this->Form->create(null, ['url' => ['action' => 'add']]) ?>

        <div class="form-group">
            <label for="name"><?= __('Key Name') ?></label>
            <?= $this->Form->control('name', [
                'label' => false,
                'type' => 'text',
                'placeholder' => __('e.g., Production API, CI/CD Pipeline'),
                'required' => true,
            ]) ?>
        </div>

        <div class="form-group">
            <label><?= __('Permissions') ?></label>
            <div class="checkbox-group">
                <div class="checkbox-item">
                    <input type="checkbox" name="perm_read" id="perm_read" value="1" checked>
                    <div class="checkbox-label">
                        <strong><?= __('Read') ?></strong>
                        <span><?= __('View monitors, incidents, checks, and status data') ?></span>
                    </div>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" name="perm_write" id="perm_write" value="1">
                    <div class="checkbox-label">
                        <strong><?= __('Write') ?></strong>
                        <span><?= __('Create and update monitors, incidents, and alert rules') ?></span>
                    </div>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" name="perm_admin" id="perm_admin" value="1">
                    <div class="checkbox-label">
                        <strong><?= __('Admin') ?></strong>
                        <span><?= __('Full access including settings, team management, and billing') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-buttons">
            <?= $this->Form->button(__('Create API Key'), ['class' => 'btn-submit']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn-back']) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
<?php endif; ?>
