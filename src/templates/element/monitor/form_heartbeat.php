<?php
/**
 * Heartbeat Monitor Type Form Fields
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */

$configuration = is_object($monitor) && method_exists($monitor, 'getConfiguration')
    ? $monitor->getConfiguration()
    : [];
?>

<div id="heartbeat-fields" class="monitor-type-fields">
    <h4 class="type-fields-title">
        <span class="icon">&#x1F493;</span>
        <?= __d('monitors', 'Heartbeat Configuration') ?>
    </h4>

    <?php if (!empty($monitor->id)): ?>
        <?php
        // Try to load the heartbeat token for this monitor
        $heartbeatsTable = \Cake\ORM\TableRegistry::getTableLocator()->get('Heartbeats');
        $heartbeat = $heartbeatsTable->find()->where(['monitor_id' => $monitor->id])->first();
        ?>
        <?php if ($heartbeat): ?>
            <div class="info-box">
                <p><strong><?= __d('monitors', 'Heartbeat Ping URL') ?>:</strong></p>
                <code class="ping-url"><?= $this->Url->build('/heartbeat/' . h($heartbeat->token), ['fullBase' => true]) ?></code>
                <small class="form-help"><?= __d('monitors', 'Send a GET or POST request to this URL at regular intervals to report that your service is alive.') ?></small>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="info-box">
            <p><strong><?= __d('monitors', 'Note') ?>:</strong></p>
            <p><?= __d('monitors', 'The heartbeat ping URL will be generated after the monitor is created.') ?></p>
        </div>
    <?php endif; ?>

    <div class="form-row">
        <div class="form-group">
            <label>
                <?= __d('monitors', 'Expected Interval (seconds)') ?> *
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.heartbeat_interval') ?: __d('monitors', 'How often this service should send a ping')]) ?>
            </label>
            <?= $this->Form->number('configuration.expected_interval', [
                'min' => 10,
                'max' => 86400,
                'value' => $configuration['expected_interval'] ?? 300,
                'required' => false,
                'class' => 'form-control heartbeat-required',
            ]) ?>
            <small class="form-help"><?= __d('monitors', 'How often your service should ping (in seconds). E.g. 300 = every 5 minutes.') ?></small>
        </div>

        <div class="form-group">
            <label>
                <?= __d('monitors', 'Grace Period (seconds)') ?> *
                <?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.heartbeat_grace') ?: __d('monitors', 'Extra time allowed before marking as down')]) ?>
            </label>
            <?= $this->Form->number('configuration.grace_period', [
                'min' => 0,
                'max' => 86400,
                'value' => $configuration['grace_period'] ?? 60,
                'required' => false,
                'class' => 'form-control heartbeat-required',
            ]) ?>
            <small class="form-help"><?= __d('monitors', 'Extra time allowed before the monitor is marked as down.') ?></small>
        </div>
    </div>

    <div class="info-box">
        <p><strong><?= __d('monitors', 'How to integrate') ?>:</strong></p>
        <ul>
            <li><?= __d('monitors', 'Add a cron job or scheduled task that sends an HTTP request to the ping URL.') ?></li>
            <li><?= __d('monitors', 'Example: curl -fsS --retry 3 YOUR_PING_URL > /dev/null') ?></li>
            <li><?= __d('monitors', 'If no ping is received within the expected interval + grace period, the monitor will be marked as down.') ?></li>
        </ul>
    </div>
</div>

<style>
.ping-url {
    display: block;
    background: #263238;
    color: #80CBC4;
    padding: 12px;
    border-radius: 6px;
    font-size: 13px;
    margin: 8px 0;
    word-break: break-all;
    user-select: all;
}
</style>
