<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\MaintenanceWindow $maintenanceWindow
 * @var array $monitors
 */
?>

<div class="content-header">
    <h1><?= __('Schedule Maintenance') ?></h1>
    <div class="header-actions">
        <?= $this->Html->link(__('Back to List'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>

<div class="card">
    <?= $this->Form->create($maintenanceWindow) ?>

    <div class="form-group">
        <?= $this->Form->control('title', [
            'label' => __('Title'),
            'class' => 'form-control',
            'placeholder' => __('Scheduled Server Maintenance'),
            'required' => true,
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('description', [
            'label' => __('Description'),
            'type' => 'textarea',
            'class' => 'form-control',
            'rows' => 3,
            'placeholder' => __('Describe the maintenance work being done...'),
        ]) ?>
    </div>

    <div class="form-group">
        <label><?= __('Affected Monitors') ?></label>
        <?php if (!empty($monitors)): ?>
            <p class="help-text"><?= __('Leave unchecked to affect all monitors') ?></p>
            <?php foreach ($monitors as $monitorId => $monitorName): ?>
                <div class="form-check">
                    <input type="checkbox" name="monitor_ids_list[]" value="<?= $monitorId ?>" id="monitor_<?= $monitorId ?>" class="form-check-input">
                    <label for="monitor_<?= $monitorId ?>" class="form-check-label"><?= h($monitorName) ?></label>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted"><?= __('No monitors available.') ?></p>
        <?php endif; ?>
    </div>

    <div class="form-row" style="display: flex; gap: 16px;">
        <div class="form-group" style="flex: 1;">
            <?= $this->Form->control('starts_at', [
                'label' => __('Starts At'),
                'type' => 'datetime-local',
                'class' => 'form-control',
                'required' => true,
            ]) ?>
        </div>
        <div class="form-group" style="flex: 1;">
            <?= $this->Form->control('ends_at', [
                'label' => __('Ends At'),
                'type' => 'datetime-local',
                'class' => 'form-control',
                'required' => true,
            ]) ?>
        </div>
    </div>

    <div class="form-group">
        <?= $this->Form->control('auto_suppress_alerts', [
            'label' => __('Automatically suppress alerts during maintenance'),
            'type' => 'checkbox',
            'checked' => true,
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('notify_subscribers', [
            'label' => __('Notify subscribers about this maintenance'),
            'type' => 'checkbox',
            'checked' => true,
        ]) ?>
    </div>

    <div class="form-actions">
        <?= $this->Form->button(__('Schedule Maintenance'), ['class' => 'btn btn-primary']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
