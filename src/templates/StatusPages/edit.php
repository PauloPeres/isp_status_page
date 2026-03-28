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

    <?php
    $themeConfig = $statusPage->getThemeConfig();
    $primaryColor = $themeConfig['primary_color'] ?? '#1E88E5';
    $logoUrl = $themeConfig['logo_url'] ?? '';
    $customCss = $themeConfig['custom_css'] ?? '';
    ?>

    <h3 style="margin-top: 24px; margin-bottom: 12px; padding-top: 16px; border-top: 1px solid #eee;"><?= __('Branding') ?></h3>

    <div class="form-group">
        <label for="theme-primary-color"><?= __('Primary Color') ?></label>
        <input type="color" name="theme_primary_color" id="theme-primary-color" value="<?= h($primaryColor) ?>" class="form-control" style="height: 40px; width: 80px; padding: 2px;">
    </div>

    <div class="form-group">
        <?= $this->Form->control('theme_logo_url', [
            'label' => __('Logo URL'),
            'type' => 'text',
            'class' => 'form-control',
            'placeholder' => __('https://example.com/logo.png'),
            'value' => $logoUrl,
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('theme_custom_css', [
            'label' => __('Custom CSS'),
            'type' => 'textarea',
            'class' => 'form-control',
            'rows' => 5,
            'placeholder' => __('/* Add custom CSS rules here */'),
            'value' => $customCss,
        ]) ?>
    </div>

    <h3 style="margin-top: 24px; margin-bottom: 12px; padding-top: 16px; border-top: 1px solid #eee;"><?= __('Display Options') ?></h3>

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
