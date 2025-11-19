<?php
/**
 * HTTP Monitor Type Form Fields
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */

$configuration = is_object($monitor) && method_exists($monitor, 'getConfiguration')
    ? $monitor->getConfiguration()
    : [];
?>

<div id="http-fields" class="monitor-type-fields">
    <h4 class="type-fields-title">
        <span class="icon">🌐</span>
        <?= __d('monitors', 'HTTP/HTTPS Configuration') ?>
    </h4>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'URL') ?> *
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.http_url')]) ?>
        </label>
        <?= $this->Form->url('configuration.url', [
            'placeholder' => 'https://example.com',
            'value' => $configuration['url'] ?? '',
            'required' => false,
            'class' => 'form-control http-required',
        ]) ?>
        <small class="form-help"><?= __d('monitors', 'Full URL including protocol (http:// or https://)') ?></small>
    </div>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'HTTP Method') ?> *
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.http_method')]) ?>
        </label>
        <?= $this->Form->select('configuration.method', [
            'GET' => 'GET',
            'POST' => 'POST',
            'PUT' => 'PUT',
            'DELETE' => 'DELETE',
            'HEAD' => 'HEAD',
            'OPTIONS' => 'OPTIONS',
            'PATCH' => 'PATCH',
        ], [
            'value' => $configuration['method'] ?? 'GET',
            'required' => true,
            'class' => 'form-control',
        ]) ?>
    </div>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'Expected HTTP Status Code') ?> *
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.http_status_code')]) ?>
        </label>
        <?= $this->Form->number('configuration.expected_status_code', [
            'min' => 100,
            'max' => 599,
            'value' => $configuration['expected_status_code'] ?? 200,
            'required' => true,
            'class' => 'form-control',
        ]) ?>
        <small class="form-help"><?= __d('monitors', 'Expected HTTP status code (e.g. 200=OK, 301=Redirect, 404=Not Found)') ?></small>
    </div>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'Custom Headers') ?>
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.http_headers')]) ?>
        </label>
        <?= $this->Form->textarea('configuration.headers', [
            'rows' => 4,
            'class' => 'form-control',
            'placeholder' => '{
  "User-Agent": "ISP Status Monitor/1.0",
  "Authorization": "Bearer your-token-here"
}',
            'value' => !empty($configuration['headers']) ? json_encode($configuration['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '',
        ]) ?>
        <small class="form-help"><?= __d('monitors', 'Leave empty for no custom headers') ?></small>
    </div>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'Request Body') ?>
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.http_body')]) ?>
        </label>
        <?= $this->Form->textarea('configuration.body', [
            'rows' => 3,
            'class' => 'form-control',
            'value' => $configuration['body'] ?? '',
            'placeholder' => __d('monitors', 'Request body for POST/PUT/PATCH (optional)'),
        ]) ?>
        <small class="form-help"><?= __d('monitors', 'Only used for POST, PUT, PATCH methods') ?></small>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>
                <?= $this->Form->checkbox('configuration.verify_ssl', ['checked' => $configuration['verify_ssl'] ?? true]) ?>
                <?= __d('monitors', 'Verify SSL Certificate') ?>
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.http_verify_ssl')]) ?>
            </label>
            <small class="form-help"><?= __d('monitors', 'Disable for self-signed certificates') ?></small>
        </div>

        <div class="form-group">
            <label>
                <?= $this->Form->checkbox('configuration.follow_redirects', ['checked' => $configuration['follow_redirects'] ?? true]) ?>
                <?= __d('monitors', 'Follow Redirects') ?>
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.http_follow_redirects')]) ?>
            </label>
            <small class="form-help"><?= __d('monitors', 'Follow HTTP 3xx redirects automatically') ?></small>
        </div>
    </div>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'Expected Content') ?>
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.http_expected_content')]) ?>
        </label>
        <?= $this->Form->text('configuration.expected_content', [
            'class' => 'form-control',
            'value' => $configuration['expected_content'] ?? '',
            'placeholder' => __d('monitors', 'Text to search in response (optional)'),
        ]) ?>
        <small class="form-help"><?= __d('monitors', 'Check will fail if this text is not found in response body') ?></small>
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
