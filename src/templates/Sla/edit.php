<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\SlaDefinition $slaDefinition
 * @var array $monitors
 */
$this->assign('title', __('Edit SLA'));

// Determine which preset matches the current target
$currentTarget = number_format((float)$slaDefinition->target_uptime, 3);
$presetValues = ['99.900', '99.950', '99.990'];
$isCustom = !in_array($currentTarget, $presetValues);
?>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('SLA Tracking'), 'url' => $this->Url->build(['controller' => 'Sla', 'action' => 'index'])],
    ['title' => h($slaDefinition->name), 'url' => null],
]]) ?>

<div class="sla-form">
    <div class="page-header">
        <h1><?= __('Edit SLA Definition') ?></h1>
        <p><?= h($slaDefinition->name) ?></p>
    </div>

    <div class="card">
        <div class="card-body" style="padding: 24px;">
            <?= $this->Form->create($slaDefinition) ?>

            <div class="form-group">
                <label for="monitor-id"><?= __('Monitor') ?></label>
                <?= $this->Form->control('monitor_id', [
                    'type' => 'select',
                    'options' => $monitors,
                    'label' => false,
                    'id' => 'monitor-id',
                    'class' => 'form-control',
                    'required' => true,
                ]) ?>
                <small class="form-text"><?= __('Each monitor can have only one SLA definition.') ?></small>
            </div>

            <div class="form-group">
                <label for="name"><?= __('SLA Name') ?></label>
                <?= $this->Form->control('name', [
                    'type' => 'text',
                    'label' => false,
                    'class' => 'form-control',
                    'required' => true,
                ]) ?>
            </div>

            <div class="form-group">
                <label><?= __('Target Uptime') ?></label>
                <div class="target-presets">
                    <label class="preset-option">
                        <input type="radio" name="target_uptime_preset" value="99.900" <?= $currentTarget === '99.900' ? 'checked' : '' ?>>
                        <div class="preset-card">
                            <strong>99.9%</strong>
                            <span class="preset-detail"><?= __('~43 min/month downtime') ?></span>
                        </div>
                    </label>
                    <label class="preset-option">
                        <input type="radio" name="target_uptime_preset" value="99.950" <?= $currentTarget === '99.950' ? 'checked' : '' ?>>
                        <div class="preset-card">
                            <strong>99.95%</strong>
                            <span class="preset-detail"><?= __('~22 min/month downtime') ?></span>
                        </div>
                    </label>
                    <label class="preset-option">
                        <input type="radio" name="target_uptime_preset" value="99.990" <?= $currentTarget === '99.990' ? 'checked' : '' ?>>
                        <div class="preset-card">
                            <strong>99.99%</strong>
                            <span class="preset-detail"><?= __('~4.3 min/month downtime') ?></span>
                        </div>
                    </label>
                    <label class="preset-option">
                        <input type="radio" name="target_uptime_preset" value="custom" <?= $isCustom ? 'checked' : '' ?>>
                        <div class="preset-card">
                            <strong><?= __('Custom') ?></strong>
                            <span class="preset-detail"><?= __('Enter your own target') ?></span>
                        </div>
                    </label>
                </div>
                <div id="custom-target-wrapper" style="<?= $isCustom ? '' : 'display: none;' ?> margin-top: 12px;">
                    <?= $this->Form->control('target_uptime', [
                        'type' => 'number',
                        'label' => false,
                        'class' => 'form-control',
                        'step' => '0.001',
                        'min' => '90',
                        'max' => '100',
                        'value' => $isCustom ? $currentTarget : '',
                        'id' => 'custom-target-input',
                    ]) ?>
                    <small class="form-text"><?= __('Enter a value between 90.000 and 100.000') ?></small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label for="measurement-period"><?= __('Measurement Period') ?></label>
                    <?= $this->Form->control('measurement_period', [
                        'type' => 'select',
                        'options' => [
                            'monthly' => __('Monthly'),
                            'quarterly' => __('Quarterly'),
                            'yearly' => __('Yearly'),
                        ],
                        'label' => false,
                        'id' => 'measurement-period',
                        'class' => 'form-control',
                    ]) ?>
                </div>

                <div class="form-group" style="flex: 1;">
                    <label for="warning-threshold"><?= __('Warning Threshold (%)') ?></label>
                    <?= $this->Form->control('warning_threshold', [
                        'type' => 'number',
                        'label' => false,
                        'class' => 'form-control',
                        'step' => '0.001',
                        'min' => '90',
                        'max' => '100',
                        'id' => 'warning-threshold',
                    ]) ?>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <?= $this->Form->control('breach_notification', [
                        'type' => 'checkbox',
                        'label' => false,
                    ]) ?>
                    <span><?= __('Send notifications when SLA is breached or at risk') ?></span>
                </label>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <?= $this->Form->control('active', [
                        'type' => 'checkbox',
                        'label' => false,
                    ]) ?>
                    <span><?= __('SLA is active') ?></span>
                </label>
            </div>

            <div class="form-actions">
                <?= $this->Form->button(__('Update SLA'), ['class' => 'btn btn-primary']) ?>
                <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<style>
.sla-form .page-header { margin-bottom: 24px; }
.sla-form .page-header h1 { margin: 0 0 4px; }
.sla-form .page-header p { margin: 0; color: var(--color-gray-medium); }

.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
.form-group small.form-text { display: block; margin-top: 4px; color: var(--color-gray-medium); font-size: 12px; }
.form-row { display: flex; gap: 20px; }

.target-presets { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
.preset-option input[type="radio"] { display: none; }
.preset-card {
    display: flex; flex-direction: column; align-items: center; padding: 16px;
    border: 2px solid var(--color-gray-light); border-radius: var(--radius-md);
    cursor: pointer; transition: all 0.2s; text-align: center;
}
.preset-card:hover { border-color: var(--color-primary); }
.preset-option input:checked + .preset-card {
    border-color: var(--color-primary); background: rgba(30, 136, 229, 0.05);
}
.preset-detail { font-size: 12px; color: var(--color-gray-medium); margin-top: 4px; }

.checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.checkbox-label input { margin: 0; }

.form-actions { display: flex; gap: 12px; margin-top: 24px; }

@media (max-width: 768px) {
    .form-row { flex-direction: column; gap: 0; }
    .target-presets { grid-template-columns: repeat(2, 1fr); }
    .form-actions { flex-direction: column; }
    .form-actions .btn { width: 100%; min-height: 44px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var presetRadios = document.querySelectorAll('input[name="target_uptime_preset"]');
    var customWrapper = document.getElementById('custom-target-wrapper');
    var customInput = document.getElementById('custom-target-input');

    presetRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (this.value === 'custom') {
                customWrapper.style.display = 'block';
                customInput.required = true;
                customInput.focus();
            } else {
                customWrapper.style.display = 'none';
                customInput.required = false;
            }
        });
    });
});
</script>
