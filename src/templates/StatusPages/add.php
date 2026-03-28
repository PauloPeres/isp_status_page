<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\StatusPage $statusPage
 * @var array $monitors
 */
?>

<div class="content-header">
    <h1><?= __('Create Status Page') ?></h1>
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
            'placeholder' => __('My Status Page'),
            'required' => true,
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('slug', [
            'label' => __('URL Slug'),
            'class' => 'form-control',
            'placeholder' => __('my-status-page'),
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
            'placeholder' => __('Welcome to our status page'),
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('footer_text', [
            'label' => __('Footer Text'),
            'type' => 'textarea',
            'class' => 'form-control',
            'rows' => 2,
            'placeholder' => __('Powered by ISP Status'),
        ]) ?>
    </div>

    <div class="form-group">
        <label><?= __('Monitors to Display') ?></label>
        <?php if (!empty($monitors)): ?>
            <?php foreach ($monitors as $monitorId => $monitorName): ?>
                <div class="form-check">
                    <input type="checkbox" name="monitor_ids[]" value="<?= $monitorId ?>" id="monitor_<?= $monitorId ?>" class="form-check-input">
                    <label for="monitor_<?= $monitorId ?>" class="form-check-label"><?= h($monitorName) ?></label>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted"><?= __('No monitors available. Create monitors first.') ?></p>
        <?php endif; ?>
    </div>

    <h3 style="margin-top: 24px; margin-bottom: 12px; padding-top: 16px; border-top: 1px solid #eee;"><?= __('Branding') ?></h3>

    <div class="form-group">
        <label for="theme-primary-color"><?= __('Primary Color') ?></label>
        <input type="color" name="theme_primary_color" id="theme-primary-color" value="#1E88E5" class="form-control" style="height: 40px; width: 80px; padding: 2px;">
    </div>

    <div class="form-group">
        <?= $this->Form->control('theme_logo_url', [
            'label' => __('Logo URL'),
            'type' => 'text',
            'class' => 'form-control',
            'placeholder' => __('https://example.com/logo.png'),
            'value' => '',
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('theme_custom_css', [
            'label' => __('Custom CSS'),
            'type' => 'textarea',
            'class' => 'form-control',
            'rows' => 5,
            'placeholder' => __('/* Add custom CSS rules here */'),
            'value' => '',
        ]) ?>
    </div>

    <h3 style="margin-top: 24px; margin-bottom: 12px; padding-top: 16px; border-top: 1px solid #eee;"><?= __('Display Options') ?></h3>

    <div class="form-group">
        <?= $this->Form->control('show_uptime_chart', [
            'label' => __('Show Uptime Chart'),
            'type' => 'checkbox',
            'checked' => true,
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('show_incident_history', [
            'label' => __('Show Incident History'),
            'type' => 'checkbox',
            'checked' => true,
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('password', [
            'label' => __('Password Protection (optional)'),
            'type' => 'password',
            'class' => 'form-control',
            'placeholder' => __('Leave empty for public access'),
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('active', [
            'label' => __('Active'),
            'type' => 'checkbox',
            'checked' => true,
        ]) ?>
    </div>

    <div class="form-actions">
        <?= $this->Form->button(__('Create Status Page'), ['class' => 'btn btn-primary']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
