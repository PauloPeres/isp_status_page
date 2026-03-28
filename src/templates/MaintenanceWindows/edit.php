<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\MaintenanceWindow $maintenanceWindow
 * @var array $monitors
 */

$selectedMonitorIds = [];
if (!empty($maintenanceWindow->monitor_ids)) {
    $decoded = json_decode($maintenanceWindow->monitor_ids, true);
    if (is_array($decoded)) {
        $selectedMonitorIds = $decoded;
    }
}
?>

<div class="content-header">
    <h1><?= __('Edit Maintenance Window') ?></h1>
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
                    <input type="checkbox" name="monitor_ids_list[]" value="<?= $monitorId ?>" id="monitor_<?= $monitorId ?>" class="form-check-input"
                        <?= in_array($monitorId, $selectedMonitorIds) ? 'checked' : '' ?>>
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
                'value' => $maintenanceWindow->starts_at ? $maintenanceWindow->starts_at->format('Y-m-d\TH:i') : '',
            ]) ?>
        </div>
        <div class="form-group" style="flex: 1;">
            <?= $this->Form->control('ends_at', [
                'label' => __('Ends At'),
                'type' => 'datetime-local',
                'class' => 'form-control',
                'required' => true,
                'value' => $maintenanceWindow->ends_at ? $maintenanceWindow->ends_at->format('Y-m-d\TH:i') : '',
            ]) ?>
        </div>
    </div>

    <div class="form-group">
        <?= $this->Form->control('auto_suppress_alerts', [
            'label' => __('Automatically suppress alerts during maintenance'),
            'type' => 'checkbox',
            'checked' => $maintenanceWindow->auto_suppress_alerts,
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('notify_subscribers', [
            'label' => __('Notify subscribers about this maintenance'),
            'type' => 'checkbox',
            'checked' => $maintenanceWindow->notify_subscribers,
        ]) ?>
    </div>

    <div class="form-actions">
        <?= $this->Form->button(__('Save Changes'), ['class' => 'btn btn-primary']) ?>
        <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
        <?= $this->Form->postLink(
            __('Delete'),
            ['action' => 'delete', $maintenanceWindow->id],
            ['confirm' => __('Are you sure you want to delete this maintenance window?'), 'class' => 'btn btn-danger']
        ) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
