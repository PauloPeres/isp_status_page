<?php
/**
 * Ping Monitor Type Form Fields
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */

$configuration = is_object($monitor) && method_exists($monitor, 'getConfiguration')
    ? $monitor->getConfiguration()
    : [];
?>

<div id="ping-fields" class="monitor-type-fields">
    <h4 class="type-fields-title">
        <span class="icon">📡</span>
        <?= __d('monitors', 'Ping (ICMP) Configuration') ?>
    </h4>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'Host or IP Address') ?> *
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.ping_host')]) ?>
        </label>
        <?= $this->Form->text('configuration.host', [
            'placeholder' => 'example.com or 192.168.1.1',
            'value' => $configuration['host'] ?? '',
            'required' => false,
            'class' => 'form-control ping-required',
        ]) ?>
        <small class="form-help"><?= __d('monitors', 'Hostname or IP address to ping') ?></small>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>
                <?= __d('monitors', 'Packet Count') ?>
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.ping_packet_count')]) ?>
            </label>
            <?= $this->Form->number('configuration.packet_count', [
                'min' => 1,
                'max' => 10,
                'value' => $configuration['packet_count'] ?? 4,
                'class' => 'form-control',
            ]) ?>
            <small class="form-help"><?= __d('monitors', 'Number of ping packets to send (1-10)') ?></small>
        </div>

        <div class="form-group">
            <label>
                <?= __d('monitors', 'Max Packet Loss (%)') ?>
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.ping_packet_loss')]) ?>
            </label>
            <?= $this->Form->number('configuration.max_packet_loss', [
                'min' => 0,
                'max' => 100,
                'value' => $configuration['max_packet_loss'] ?? 25,
                'class' => 'form-control',
            ]) ?>
            <small class="form-help"><?= __d('monitors', 'Alert if packet loss exceeds this percentage') ?></small>
        </div>
    </div>

    <div class="form-group">
        <label>
            <?= __d('monitors', 'Max Latency (ms)') ?>
            <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.ping_latency')]) ?>
        </label>
        <?= $this->Form->number('configuration.max_latency', [
            'min' => 1,
            'value' => $configuration['max_latency'] ?? 1000,
            'class' => 'form-control',
        ]) ?>
        <small class="form-help"><?= __d('monitors', 'Alert if average latency exceeds this value (in milliseconds)') ?></small>
    </div>

    <div class="info-box">
        <p><strong>ℹ️ <?= __d('monitors', 'Note') ?>:</strong></p>
        <ul>
            <li><?= __d('monitors', 'ICMP ping requires appropriate system permissions') ?></li>
            <li><?= __d('monitors', 'Some firewalls may block ICMP packets') ?></li>
            <li><?= __d('monitors', 'Average latency is calculated from all packets') ?></li>
        </ul>
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
    background: #E3F2FD;
    border-left: 4px solid #1E88E5;
    padding: 16px;
    border-radius: var(--radius-md);
    margin-top: 20px;
}

.info-box p {
    margin: 0 0 8px 0;
    font-weight: 600;
    color: #1565C0;
}

.info-box ul {
    margin: 0;
    padding-left: 20px;
}

.info-box li {
    margin: 4px 0;
    color: #666;
    font-size: 13px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>
