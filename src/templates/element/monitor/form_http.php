<?php
/**
 * HTTP Monitor Type Form Fields
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */

$configuration = $monitor->configuration ?? [];
?>

<div id="http-fields" class="monitor-type-fields">
    <h4 class="type-fields-title">
        <span class="icon">🌐</span>
        <?= __d('monitors', 'HTTP/HTTPS Configuration') ?>
    </h4>

    <div class="form-group">
        <?= $this->Form->control('configuration.method', [
            'label' => __d('monitors', 'HTTP Method') . ' *',
            'type' => 'select',
            'options' => [
                'GET' => 'GET',
                'POST' => 'POST',
                'PUT' => 'PUT',
                'DELETE' => 'DELETE',
                'HEAD' => 'HEAD',
                'OPTIONS' => 'OPTIONS',
                'PATCH' => 'PATCH',
            ],
            'default' => $configuration['method'] ?? 'GET',
            'required' => true,
            'class' => 'form-control',
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('expected_status_code', [
            'label' => __d('monitors', 'Expected HTTP Status Code') . ' *',
            'type' => 'number',
            'min' => 100,
            'max' => 599,
            'default' => 200,
            'required' => true,
            'class' => 'form-control',
            'help' => __d('monitors', 'Expected HTTP status code (e.g. 200=OK, 301=Redirect, 404=Not Found)'),
        ]) ?>
    </div>

    <div class="form-group">
        <?= $this->Form->control('configuration.headers', [
            'label' => __d('monitors', 'Custom Headers'),
            'type' => 'textarea',
            'rows' => 4,
            'class' => 'form-control',
            'placeholder' => '{
  "User-Agent": "ISP Status Monitor/1.0",
  "Authorization": "Bearer your-token-here"
}',
            'value' => !empty($configuration['headers']) ? json_encode($configuration['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
            'help' => __d('monitors', 'Custom HTTP headers in JSON format (optional)'),
        ]) ?>
        <small class="form-text text-muted">
            <?= __d('monitors', 'Leave empty for no custom headers') ?>
        </small>
    </div>

    <div class="form-group">
        <?= $this->Form->control('configuration.body', [
            'label' => __d('monitors', 'Request Body'),
            'type' => 'textarea',
            'rows' => 3,
            'class' => 'form-control',
            'value' => $configuration['body'] ?? '',
            'placeholder' => __d('monitors', 'Request body for POST/PUT/PATCH (optional)'),
            'help' => __d('monitors', 'Only used for POST, PUT, PATCH methods'),
        ]) ?>
    </div>

    <div class="form-row">
        <div class="form-group">
            <?= $this->Form->control('configuration.verify_ssl', [
                'label' => __d('monitors', 'Verify SSL Certificate'),
                'type' => 'checkbox',
                'checked' => $configuration['verify_ssl'] ?? true,
                'help' => __d('monitors', 'Disable for self-signed certificates'),
            ]) ?>
        </div>

        <div class="form-group">
            <?= $this->Form->control('configuration.follow_redirects', [
                'label' => __d('monitors', 'Follow Redirects'),
                'type' => 'checkbox',
                'checked' => $configuration['follow_redirects'] ?? true,
                'help' => __d('monitors', 'Follow HTTP 3xx redirects automatically'),
            ]) ?>
        </div>
    </div>

    <div class="form-group">
        <?= $this->Form->control('configuration.expected_content', [
            'label' => __d('monitors', 'Expected Content'),
            'type' => 'text',
            'class' => 'form-control',
            'value' => $configuration['expected_content'] ?? '',
            'placeholder' => __d('monitors', 'Text to search in response (optional)'),
            'help' => __d('monitors', 'Check will fail if this text is not found in response body'),
        ]) ?>
    </div>
</div>

<style>
.monitor-type-fields {
    background: var(--color-light);
    border-radius: var(--radius-md);
    padding: 24px;
    margin-top: 16px;
}

.type-fields-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-dark);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.type-fields-title .icon {
    font-size: 20px;
}

.form-group {
    margin-bottom: 16px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-control[type="checkbox"] {
    width: auto;
    margin-top: 8px;
}

.form-text {
    display: block;
    margin-top: 4px;
    font-size: 13px;
    color: var(--color-gray);
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
