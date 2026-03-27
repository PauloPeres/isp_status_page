<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\StatusPage $statusPage
 * @var array $monitors
 */
$selectedMonitors = $statusPage->getMonitorIds();
?>

<div class="content-header">
    <h1><?= __('Edit Status Page') ?></h1>
    <div class="header-actions">
        <?= $this->Html->link(__('Back to List'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>

<div class="card">
    <?= $this->Form->create($statusPage) ?>

    <div class="form-group">
        <?= $this->Form->control('name', [
            'label' => __('Page Name'),
            'class' => 'form-control',
            'required' => true,
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('slug', [
            'label' => __('URL Slug'),
            'class' => 'form-control',
            'required' => true,
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('custom_domain', [
            'label' => __('Custom Domain (optional)'),
            'class' => 'form-control',
            'placeholder' => __('status.example.com'),
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('header_text', [
            'label' => __('Header Text'),
            'type' => 'textarea',
            'class' => 'form-control',
            'rows' => 3,
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('footer_text', [
            'label' => __('Footer Text'),
            'type' => 'textarea',
            'class' => 'form-control',
            'rows' => 2,
        ]) ?>
    </div>

    <div class="form-group">
        <label><?= __('Monitors to Display') ?></label>
        <?php if (!empty($monitors)): ?>
            <?php foreach ($monitors as $monitorId => $monitorName): ?>
                <div class="form-check">
                    <input type="checkbox" name="monitor_ids[]" value="<?= $monitorId ?>" id="monitor_<?= $monitorId ?>"
                           class="form-check-input" <?= in_array($monitorId, $selectedMonitors) ? 'checked' : '' ?>>
                    <label for="monitor_<?= $monitorId ?>" class="form-check-label"><?= h($monitorName) ?></label>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted"><?= __('No monitors available.') ?></p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('show_uptime_chart', [
            'label' => __('Show Uptime Chart'),
            'type' => 'checkbox',
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('show_incident_history', [
            'label' => __('Show Incident History'),
            'type' => 'checkbox',
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('password', [
            'label' => __('Password Protection (optional)'),
            'type' => 'password',
            'class' => 'form-control',
            'placeholder' => __('Leave empty to keep current or remove protection'),
            'value' => '',
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('active', [
            'label' => __('Active'),
            'type' => 'checkbox',
        ]) ?>
    </div>

    <div class="form-actions">
        <?= $this->Form->button(__('Save Changes'), ['class' => 'btn btn-primary']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
