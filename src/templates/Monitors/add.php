<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */
$this->assign('title', __d('monitors', 'New Monitor'));
?>

<div class="monitors-form">
    <div class="page-header">
        <div>
            <h1>➕ <?= __d('monitors', 'New Monitor') ?></h1>
            <p><?= __d('monitors', 'Configure a new service for monitoring') ?></p>
        </div>
        <?= $this->Html->link(
            '← ' . __('Back'),
            ['action' => 'index'],
            ['class' => 'btn btn-secondary']
        ) ?>
    </div>

    <div class="card">
        <?= $this->Form->create($monitor) ?>

        <div class="form-section">
            <h3 class="form-section-title"><?= __d('monitors', 'Basic Information') ?></h3>

            <div class="form-group">
                <label>
                    <?= __d('monitors', 'Monitor Name') ?> *
                    <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.monitor_name')]) ?>
                </label>
                <?= $this->Form->text('name', [
                    'placeholder' => __d('monitors', 'e.g. Main Website'),
                    'required' => true,
                    'class' => 'form-control',
                ]) ?>
            </div>

            <?= $this->Form->control('description', [
                'label' => __('Description'),
                'placeholder' => __d('monitors', 'Brief description of what is being monitored'),
                'type' => 'textarea',
                'rows' => 3,
                'class' => 'form-control',
            ]) ?>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        <?= __d('monitors', 'Monitor Type') ?> *
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.monitor_type')]) ?>
                    </label>
                    <?= $this->Form->select('type', [
                        'http' => 'HTTP/HTTPS',
                        'ping' => 'Ping (ICMP)',
                        'port' => __d('monitors', 'Port (TCP/UDP)'),
                    ], [
                        'required' => true,
                        'class' => 'form-control',
                        'id' => 'monitor-type',
                    ]) ?>
                </div>

                <div class="form-group">
                    <label>
                        <?= $this->Form->checkbox('active', ['checked' => true]) ?>
                        <?= __d('monitors', 'Active') ?>
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.active')]) ?>
                    </label>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3 class="form-section-title"><?= __d('monitors', 'Target Configuration') ?></h3>
            <p class="form-help"><?= __d('monitors', 'Configuration depends on selected monitor type') ?></p>

            <!-- HTTP/HTTPS Fields -->
            <?= $this->element('monitor/form_http') ?>

            <!-- Ping/ICMP Fields -->
            <?= $this->element('monitor/form_ping') ?>

            <!-- Port/TCP/UDP Fields -->
            <?= $this->element('monitor/form_port') ?>
        </div>

        <div class="form-section">
            <h3 class="form-section-title"><?= __d('monitors', 'Check Settings') ?></h3>

            <div class="form-row">
                <div class="form-group">
                    <label>
                        <?= __d('monitors', 'Interval (seconds)') ?> *
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.check_interval')]) ?>
                    </label>
                    <?= $this->Form->number('check_interval', [
                        'value' => 30,
                        'min' => 10,
                        'max' => 3600,
                        'required' => true,
                        'class' => 'form-control',
                    ]) ?>
                    <small class="form-help"><?= __d('monitors', 'Check frequency (minimum 10s)') ?></small>
                </div>

                <div class="form-group">
                    <label>
                        <?= __d('monitors', 'Timeout (seconds)') ?> *
                        <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.timeout')]) ?>
                    </label>
                    <?= $this->Form->number('timeout', [
                        'value' => 10,
                        'min' => 1,
                        'max' => 60,
                        'required' => true,
                        'class' => 'form-control',
                    ]) ?>
                    <small class="form-help"><?= __d('monitors', 'Maximum wait time') ?></small>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <?= $this->Form->button('💾 ' . __d('monitors', 'Save Monitor'), ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(
                __('Cancel'),
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>

        <?= $this->Form->end() ?>
    </div>
</div>

<?= $this->Html->script('monitor-form', ['block' => true]) ?>

<style>
.monitors-form {
    max-width: 800px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: var(--color-dark);
}

.form-help {
    display: block;
    margin-top: 6px;
    font-size: 13px;
    color: var(--color-gray-medium);
    line-height: 1.4;
}

.form-section {
    margin-bottom: 32px;
    padding-bottom: 32px;
    border-bottom: 1px solid var(--color-gray-light);
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-dark);
    margin-bottom: 16px;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--color-gray-light);
    border-radius: var(--radius-md);
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
}

.form-control[type="checkbox"] {
    width: auto;
    margin-top: 8px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-actions {
    display: flex;
    gap: 16px;
    padding-top: 24px;
}

.monitor-type-fields {
    margin-top: 16px;
    padding: 16px;
    background: var(--color-gray-light);
    border-radius: var(--radius-md);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
    }

    .form-actions .btn {
        width: 100%;
    }
}
</style>
