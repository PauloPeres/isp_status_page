<?php
/**
 * Scheduled Reports Add (P4-010)
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\ScheduledReport $report
 */
?>

<div class="content-header">
    <h1><?= __('New Scheduled Report') ?></h1>
    <div class="content-header-actions">
        <?= $this->Html->link(
            __('Back to Reports'),
            ['action' => 'index'],
            ['class' => 'btn btn-secondary']
        ) ?>
    </div>
</div>

<div class="card">
    <?= $this->Form->create($report) ?>

    <div class="form-group">
        <label for="name"><?= __('Report Name') ?></label>
        <?= $this->Form->control('name', [
            'label' => false,
            'class' => 'form-control',
            'placeholder' => __('e.g., Weekly Infrastructure Report'),
        ]) ?>
    </div>

    <div class="form-group">
        <label for="frequency"><?= __('Frequency') ?></label>
        <?= $this->Form->control('frequency', [
            'label' => false,
            'type' => 'select',
            'class' => 'form-control',
            'options' => [
                'weekly' => __('Weekly (every Monday at 8:00 AM)'),
                'monthly' => __('Monthly (1st of each month at 8:00 AM)'),
            ],
            'default' => 'weekly',
        ]) ?>
    </div>

    <div class="form-group">
        <label for="recipients"><?= __('Recipients') ?></label>
        <?= $this->Form->control('recipients', [
            'label' => false,
            'type' => 'text',
            'class' => 'form-control',
            'placeholder' => __('admin@example.com, ops@example.com'),
        ]) ?>
        <small style="color: #888;"><?= __('Comma-separated list of email addresses') ?></small>
    </div>

    <h3 style="margin-top: 24px; margin-bottom: 16px; font-size: 16px; color: #555;"><?= __('Report Sections') ?></h3>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <?= $this->Form->checkbox('include_uptime', ['checked' => true]) ?>
                <?= __('Include Uptime Percentage') ?>
            </label>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <?= $this->Form->checkbox('include_response_time', ['checked' => true]) ?>
                <?= __('Include Response Time') ?>
            </label>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <?= $this->Form->checkbox('include_incidents', ['checked' => true]) ?>
                <?= __('Include Incidents') ?>
            </label>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <?= $this->Form->checkbox('include_sla', ['checked' => true]) ?>
                <?= __('Include SLA Status') ?>
            </label>
        </div>
    </div>

    <div class="form-group">
        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <?= $this->Form->checkbox('active', ['checked' => true]) ?>
            <strong><?= __('Active') ?></strong>
        </label>
        <small style="color: #888;"><?= __('Inactive reports will not be sent automatically.') ?></small>
    </div>

    <div class="form-actions" style="margin-top: 24px; display: flex; gap: 12px;">
        <?= $this->Form->button(__('Create Report'), ['class' => 'btn btn-primary']) ?>
        <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
