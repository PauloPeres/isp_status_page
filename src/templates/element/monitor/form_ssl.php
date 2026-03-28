<?php
/**
 * SSL Certificate Monitor Type Form Fields
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */

$configuration = is_object($monitor) && method_exists($monitor, 'getConfiguration')
    ? $monitor->getConfiguration()
    : [];
?>

<div id="ssl-fields" class="monitor-type-fields">
    <h4 class="type-fields-title">
        <span class="icon">&#x1F512;</span>
        <?= __d('monitors', 'SSL Certificate Configuration') ?>
    </h4>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'Host / Domain') ?> *
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.ssl_host') ?: __d('monitors', 'The domain name to check the SSL certificate for')]) ?>
        </label>
        <?= $this->Form->text('configuration.host', [
            'placeholder' => 'example.com',
            'value' => $configuration['host'] ?? '',
            'required' => false,
            'class' => 'form-control ssl-required',
        ]) ?>
        <small class="form-help"><?= __d('monitors', 'Domain name without protocol (e.g. example.com, not https://example.com)') ?></small>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>
                <?= __d('monitors', 'Port') ?>
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.ssl_port') ?: __d('monitors', 'The port to connect to for SSL certificate check')]) ?>
            </label>
            <?= $this->Form->number('configuration.port', [
                'min' => 1,
                'max' => 65535,
                'value' => $configuration['port'] ?? 443,
                'class' => 'form-control',
            ]) ?>
            <small class="form-help"><?= __d('monitors', 'Default: 443 (standard HTTPS port)') ?></small>
        </div>

        <div class="form-group">
            <label>
                <?= __d('monitors', 'Warning Days') ?>
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.ssl_warning_days') ?: __d('monitors', 'Alert when the certificate expires within this many days')]) ?>
            </label>
            <?= $this->Form->number('configuration.warning_days', [
                'min' => 1,
                'max' => 365,
                'value' => $configuration['warning_days'] ?? 30,
                'class' => 'form-control',
            ]) ?>
            <small class="form-help"><?= __d('monitors', 'Alert when certificate expires within this many days (default: 30)') ?></small>
        </div>
    </div>

    <div class="info-box">
        <p><strong><?= __d('monitors', 'Note') ?>:</strong></p>
        <ul>
            <li><?= __d('monitors', 'The SSL checker will connect to the host and verify the certificate validity.') ?></li>
            <li><?= __d('monitors', 'If the certificate expires within the warning period, the status will be marked as degraded.') ?></li>
            <li><?= __d('monitors', 'Expired certificates will be marked as down.') ?></li>
        </ul>
    </div>
</div>
