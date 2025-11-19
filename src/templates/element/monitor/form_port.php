<?php
/**
 * Port Monitor Type Form Fields
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */

$configuration = is_object($monitor) && method_exists($monitor, 'getConfiguration')
    ? $monitor->getConfiguration()
    : [];
?>

<div id="port-fields" class="monitor-type-fields">
    <h4 class="type-fields-title">
        <span class="icon">🔌</span>
        <?= __d('monitors', 'Port (TCP/UDP) Configuration') ?>
    </h4>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'Host or IP Address') ?> *
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.port_host')]) ?>
        </label>
        <?= $this->Form->text('configuration.host', [
            'placeholder' => 'example.com or 192.168.1.1',
            'value' => $configuration['host'] ?? '',
            'required' => false,
            'class' => 'form-control port-required',
        ]) ?>
        <small class="form-help"><?= __d('monitors', 'Hostname or IP address to connect') ?></small>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>
                <?= __d('monitors', 'Port Number') ?> *
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.port_number')]) ?>
            </label>
            <?= $this->Form->number('configuration.port', [
                'min' => 1,
                'max' => 65535,
                'placeholder' => '80',
                'value' => $configuration['port'] ?? '',
                'required' => false,
                'class' => 'form-control port-required',
            ]) ?>
            <small class="form-help"><?= __d('monitors', 'TCP/UDP port (1-65535)') ?></small>
        </div>

        <div class="form-group">
            <label>
                <?= __d('monitors', 'Protocol') ?> *
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.port_protocol')]) ?>
            </label>
            <?= $this->Form->select('configuration.protocol', [
                'tcp' => 'TCP',
                'udp' => 'UDP',
            ], [
                'value' => $configuration['protocol'] ?? 'tcp',
                'required' => true,
                'class' => 'form-control',
            ]) ?>
            <small class="form-help"><?= __d('monitors', 'Connection protocol') ?></small>
        </div>
    </div>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'Send Data (optional)') ?>
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.port_send_data')]) ?>
        </label>
        <?= $this->Form->textarea('configuration.send_data', [
            'rows' => 3,
            'class' => 'form-control',
            'value' => $configuration['send_data'] ?? '',
            'placeholder' => __d('monitors', 'Data to send after connection (optional)'),
        ]) ?>
        <small class="form-help"><?= __d('monitors', 'Useful for testing application protocols') ?></small>
    </div>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'Expected Response (optional)') ?>
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.port_expected_response')]) ?>
        </label>
        <?= $this->Form->text('configuration.expected_response', [
            'class' => 'form-control',
            'value' => $configuration['expected_response'] ?? '',
            'placeholder' => __d('monitors', 'Text to search in response'),
        ]) ?>
        <small class="form-help"><?= __d('monitors', 'Check will fail if this text is not found in server response') ?></small>
    </div>

    <div class="info-box">
        <p><strong>ℹ️ <?= __d('monitors', 'Common Ports') ?>:</strong></p>
        <div class="ports-grid">
            <div class="port-item">
                <strong>HTTP:</strong> 80
            </div>
            <div class="port-item">
                <strong>HTTPS:</strong> 443
            </div>
            <div class="port-item">
                <strong>SSH:</strong> 22
            </div>
            <div class="port-item">
                <strong>FTP:</strong> 21
            </div>
            <div class="port-item">
                <strong>SMTP:</strong> 25, 587
            </div>
            <div class="port-item">
                <strong>MySQL:</strong> 3306
            </div>
            <div class="port-item">
                <strong>PostgreSQL:</strong> 5432
            </div>
            <div class="port-item">
                <strong>Redis:</strong> 6379
            </div>
        </div>
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

.info-box {
    background: #FFF3E0;
    border-left: 4px solid #FF9800;
    padding: 16px;
    border-radius: var(--radius-md);
    margin-top: 20px;
}

.info-box p {
    margin: 0 0 12px 0;
    font-weight: 600;
    color: #E65100;
}

.ports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 8px;
}

.port-item {
    background: white;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 13px;
    color: #666;
}

.port-item strong {
    color: #333;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .ports-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
