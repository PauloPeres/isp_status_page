<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */
$this->assign('title', __('Edit Monitor'));
?>

<div class="monitors-form">
    <div class="page-header">
        <div>
            <h1>✏️ <?= __('Edit Monitor') ?></h1>
            <p><?= __('Update monitor settings') ?>: <strong><?= h($monitor->name) ?></strong></p>
        </div>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(
                '👁️ ' . __('View Details'),
                ['action' => 'view', $monitor->id],
                ['class' => 'btn btn-secondary']
            ) ?>
            <?= $this->Html->link(
                '← ' . __('Back'),
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>
    </div>

    <div class="card">
        <?= $this->Form->create($monitor) ?>

        <div class="form-section">
            <h3 class="form-section-title"><?= __('Basic Information') ?></h3>

            <?= $this->Form->control('name', [
                'label' => __('Monitor Name') . ' *',
                'required' => true,
                'class' => 'form-control',
            ]) ?>

            <?= $this->Form->control('description', [
                'label' => __('Description'),
                'type' => 'textarea',
                'rows' => 3,
                'class' => 'form-control',
            ]) ?>

            <div class="form-row">
                <?= $this->Form->control('type', [
                    'label' => __('Monitor Type') . ' *',
                    'options' => [
                        'http' => 'HTTP/HTTPS',
                        'ping' => 'Ping (ICMP)',
                        'port' => __('Port') . ' (TCP/UDP)',
                    ],
                    'required' => true,
                    'class' => 'form-control',
                    'id' => 'monitor-type',
                ]) ?>

                <?= $this->Form->control('active', [
                    'label' => __('Active'),
                    'type' => 'checkbox',
                ]) ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title"><?= __('Target Configuration') ?></h3>

            <?= $this->Form->control('target', [
                'label' => __('Target') . ' *',
                'required' => true,
                'class' => 'form-control',
                'help' => __('Full URL for HTTP, hostname/IP for Ping and Port'),
            ]) ?>

            <div id="http-fields" class="monitor-type-fields">
                <?= $this->Form->control('expected_status_code', [
                    'label' => __('Expected HTTP Code'),
                    'type' => 'number',
                    'class' => 'form-control',
                    'help' => __('Expected HTTP status code (e.g.: 200, 301)'),
                ]) ?>
            </div>

            <div id="port-fields" class="monitor-type-fields" style="display:none;">
                <?= $this->Form->control('port', [
                    'label' => __('Port'),
                    'type' => 'number',
                    'min' => 1,
                    'max' => 65535,
                    'class' => 'form-control',
                    'help' => __('TCP/UDP port number (1-65535)'),
                ]) ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title"><?= __('Check Settings') ?></h3>

            <div class="form-row">
                <?= $this->Form->control('interval', [
                    'label' => __('Interval (seconds)') . ' *',
                    'type' => 'number',
                    'min' => 10,
                    'max' => 3600,
                    'required' => true,
                    'class' => 'form-control',
                    'help' => __('Check frequency (minimum 10s)'),
                ]) ?>

                <?= $this->Form->control('timeout', [
                    'label' => __('Timeout (seconds)') . ' *',
                    'type' => 'number',
                    'min' => 1,
                    'max' => 60,
                    'required' => true,
                    'class' => 'form-control',
                    'help' => __('Maximum wait time'),
                ]) ?>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title"><?= __('System Information') ?></h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label"><?= __('Current Status') ?>:</span>
                    <span class="badge badge-<?= $monitor->status === 'up' ? 'success' : ($monitor->status === 'down' ? 'error' : 'info') ?>">
                        <?= h(ucfirst($monitor->status)) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><?= __('Last Check') ?>:</span>
                    <span><?= $monitor->last_check ? $monitor->last_check->format('Y-m-d H:i:s') : __('Never') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><?= __('Created') ?>:</span>
                    <span><?= $monitor->created->format('Y-m-d H:i:s') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><?= __('Last Updated') ?>:</span>
                    <span><?= $monitor->modified->format('Y-m-d H:i:s') ?></span>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <?= $this->Form->button('💾 ' . __('Save Changes'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(
                __('Cancel'),
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
            <?= $this->Form->postLink(
                '🗑️ ' . __('Delete'),
                ['action' => 'delete', $monitor->id],
                [
                    'class' => 'btn btn-error',
                    'confirm' => __('Are you sure you want to delete this monitor? This action cannot be undone.')
                ]
            ) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('monitor-type');
    const httpFields = document.getElementById('http-fields');
    const portFields = document.getElementById('port-fields');

    function updateFields() {
        const type = typeSelect.value;
        httpFields.style.display = 'none';
        portFields.style.display = 'none';
        if (type === 'http') {
            httpFields.style.display = 'block';
        } else if (type === 'port') {
            portFields.style.display = 'block';
        }
    }

    typeSelect.addEventListener('change', updateFields);
    updateFields();
});
</script>

<style>
.monitors-form { max-width: 800px; }
.form-section { margin-bottom: 32px; padding-bottom: 32px; border-bottom: 1px solid var(--color-gray-light); }
.form-section:last-of-type { border-bottom: none; }
.form-section-title { font-size: 18px; font-weight: 600; color: var(--color-dark); margin-bottom: 16px; }
.form-control { width: 100%; padding: 12px; border: 2px solid var(--color-gray-light); border-radius: var(--radius-md); font-size: 15px; transition: all 0.3s ease; }
.form-control:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1); }
.form-control[type="checkbox"] { width: auto; margin-top: 8px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-actions { display: flex; gap: 16px; padding-top: 24px; }
.monitor-type-fields { margin-top: 16px; padding: 16px; background: var(--color-gray-light); border-radius: var(--radius-md); }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; padding: 16px; background: var(--color-gray-light); border-radius: var(--radius-md); }
.info-item { display: flex; flex-direction: column; gap: 4px; }
.info-label { font-size: 13px; font-weight: 600; color: var(--color-gray-medium); }
@media (max-width: 768px) { .page-header { flex-direction: column; } .page-header > div { width: 100%; } .form-row { grid-template-columns: 1fr; } .form-actions { flex-direction: column; } .form-actions .btn { width: 100%; } }
</style>
