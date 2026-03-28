<?php
/**
 * @var \App\View\AppView $this
 * @var string $secret
 * @var string $qrCodeUrl
 * @var \App\Model\Entity\User $user
 * @var bool $setupComplete
 * @var array $recoveryCodes
 */
$this->assign('title', __('Set Up Two-Factor Authentication'));
?>

<style>
.twofa-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.twofa-header h1 {
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

.setup-steps {
    margin-bottom: 24px;
}

.setup-step {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
    align-items: flex-start;
}

.step-number {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #1E88E5;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}

.step-content {
    flex: 1;
}

.step-content h3 {
    margin: 0 0 8px 0;
    font-size: 15px;
    color: #333;
}

.step-content p {
    margin: 0;
    font-size: 14px;
    color: #666;
    line-height: 1.5;
}

.secret-display {
    background: #f5f5f5;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 12px 16px;
    font-family: 'Courier New', monospace;
    font-size: 16px;
    letter-spacing: 2px;
    word-break: break-all;
    margin-top: 8px;
    user-select: all;
}

#qrcode {
    display: inline-block;
    padding: 16px;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-top: 8px;
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

.form-group input[type="text"] {
    padding: 10px 14px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    font-size: 18px;
    letter-spacing: 4px;
    text-align: center;
    width: 200px;
    font-family: 'Courier New', monospace;
}

.form-group input:focus {
    outline: none;
    border-color: #1E88E5;
    box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
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

.success-box {
    background: #E8F5E9;
    border: 1px solid #43A047;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.success-box h3 {
    color: #2E7D32;
    margin: 0 0 8px 0;
}

.success-box p {
    color: #2E7D32;
    margin: 0;
    font-size: 14px;
}

@media (max-width: 768px) {
    .twofa-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .recovery-codes {
        grid-template-columns: 1fr;
    }

    .form-group input[type="text"] {
        width: 100%;
    }
}
</style>

<div class="twofa-setup">
    <div class="twofa-header">
        <h1><?= __('Set Up Two-Factor Authentication') ?></h1>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(__('Back to Profile'), ['controller' => 'Users', 'action' => 'edit', $user->id], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <?php if (!empty($setupComplete) && $setupComplete): ?>
        <!-- Setup complete: show recovery codes -->
        <div class="card">
            <div class="success-box">
                <h3><?= __('Two-factor authentication is now enabled!') ?></h3>
                <p><?= __('Your account is now more secure. Save the recovery codes below.') ?></p>
            </div>

            <div class="card-header"><?= __('Recovery Codes') ?></div>

            <div class="recovery-warning">
                <strong><?= __('Important:') ?></strong>
                <?= __('Save these recovery codes in a safe place. Each code can only be used once. If you lose access to your authenticator app, you can use these codes to sign in.') ?>
            </div>

            <div class="recovery-codes">
                <?php foreach ($recoveryCodes as $code): ?>
                    <div class="recovery-code"><?= h($code) ?></div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 16px;">
                <?= $this->Html->link(__('Done'), ['controller' => 'Users', 'action' => 'edit', $user->id], ['class' => 'btn btn-primary']) ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Setup form -->
        <div class="card">
            <div class="card-header"><?= __('Setup Instructions') ?></div>

            <div class="setup-steps">
                <div class="setup-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3><?= __('Install an authenticator app') ?></h3>
                        <p><?= __('Download an authenticator app like Google Authenticator, Authy, or 1Password on your mobile device.') ?></p>
                    </div>
                </div>

                <div class="setup-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3><?= __('Scan the QR code or enter the secret manually') ?></h3>
                        <p><?= __('Open your authenticator app and scan the QR code below, or enter the secret key manually.') ?></p>

                        <div id="qrcode"></div>

                        <p style="margin-top: 12px; font-size: 13px; color: #888;"><?= __('Or enter this secret manually:') ?></p>
                        <div class="secret-display"><?= h($secret) ?></div>
                    </div>
                </div>

                <div class="setup-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3><?= __('Enter the verification code') ?></h3>
                        <p><?= __('Enter the 6-digit code from your authenticator app to confirm the setup.') ?></p>

                        <?= $this->Form->create(null, ['url' => ['action' => 'setup']]) ?>
                        <div class="form-group" style="margin-top: 12px;">
                            <label><?= __('Verification Code') ?></label>
                            <input type="text" name="code" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" required autofocus placeholder="000000">
                        </div>
                        <button type="submit" class="btn btn-primary"><?= __('Verify and Enable 2FA') ?></button>
                        <?= $this->Form->end() ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (empty($setupComplete) || !$setupComplete): ?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var qrContainer = document.getElementById('qrcode');
    if (qrContainer && typeof QRCode !== 'undefined') {
        new QRCode(qrContainer, {
            text: <?= json_encode($qrCodeUrl) ?>,
            width: 200,
            height: 200,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    }
});
</script>
<?php endif; ?>
