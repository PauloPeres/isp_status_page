<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AlertRule $alertRule
 * @var array $monitors
 * @var array $channels
 * @var array $triggers
 */
$this->assign('title', __('New Alert Rule'));
?>

<?= $this->element('admin/breadcrumb', ['breadcrumbs' => [
    ['title' => __('Alert Rules'), 'url' => $this->Url->build(['controller' => 'AlertRules', 'action' => 'index'])],
    ['title' => __('New Rule'), 'url' => null],
]]) ?>

<div class="monitors-header">
    <h2><?= __('New Alert Rule') ?></h2>
    <?= $this->Html->link(__('Back to List'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
</div>

<div class="card">
    <?= $this->Form->create($alertRule) ?>

    <div class="form-group">
        <label for="monitor-id"><?= __('Monitor') ?></label>
        <?= $this->Form->select('monitor_id', $monitors, [
            'id' => 'monitor-id',
            'class' => 'form-control',
            'empty' => __('-- Select Monitor --'),
            'required' => true,
        ]) ?>
        <?php if ($alertRule->getError('monitor_id')): ?>
            <div class="error-message"><?= implode(', ', $alertRule->getError('monitor_id')) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="trigger-on"><?= __('Trigger Type') ?></label>
        <?= $this->Form->select('trigger_on', $triggers, [
            'id' => 'trigger-on',
            'class' => 'form-control',
            'required' => true,
        ]) ?>
        <small class="form-text text-muted"><?= __('When should this alert fire?') ?></small>
        <?php if ($alertRule->getError('trigger_on')): ?>
            <div class="error-message"><?= implode(', ', $alertRule->getError('trigger_on')) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="channel"><?= __('Channel') ?></label>
        <?= $this->Form->select('channel', $channels, [
            'id' => 'channel',
            'class' => 'form-control',
            'required' => true,
        ]) ?>
        <small class="form-text text-muted"><?= __('How should the alert be delivered?') ?></small>
        <?php if ($alertRule->getError('channel')): ?>
            <div class="error-message"><?= implode(', ', $alertRule->getError('channel')) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="recipients-text"><?= __('Recipients') ?></label>
        <?php
        $recipientsText = '';
        if ($alertRule->recipients) {
            $list = $alertRule->getRecipients();
            $recipientsText = implode("\n", $list);
        }
        ?>
        <textarea name="recipients_text" id="recipients-text" class="form-control" rows="4"
                  placeholder="<?= __('One recipient per line (e.g. email@example.com)') ?>"
                  required><?= h($recipientsText) ?></textarea>
        <small class="form-text text-muted"><?= __('Enter one recipient per line. For email: addresses. For webhook: URLs. For Slack: channel names.') ?></small>
        <?php if ($alertRule->getError('recipients')): ?>
            <div class="error-message"><?= implode(', ', $alertRule->getError('recipients')) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="throttle-minutes"><?= __('Cooldown (minutes)') ?></label>
        <?= $this->Form->control('throttle_minutes', [
            'type' => 'number',
            'id' => 'throttle-minutes',
            'label' => false,
            'class' => 'form-control',
            'min' => 0,
            'value' => $alertRule->throttle_minutes ?? 5,
            'required' => true,
        ]) ?>
        <small class="form-text text-muted"><?= __('Minimum minutes between repeated alerts for this rule. Set to 0 for no cooldown.') ?></small>
        <?php if ($alertRule->getError('throttle_minutes')): ?>
            <div class="error-message"><?= implode(', ', $alertRule->getError('throttle_minutes')) ?></div>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label>
            <?= $this->Form->checkbox('active', ['checked' => $alertRule->active ?? true]) ?>
            <?= __('Active') ?>
        </label>
        <small class="form-text text-muted"><?= __('Inactive rules will not trigger alerts.') ?></small>
    </div>

    <div class="form-actions">
        <?= $this->Form->button(__('Create Alert Rule'), ['class' => 'btn btn-primary']) ?>
        <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?= $this->Form->end() ?>
</div>
