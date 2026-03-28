<?php
/**
 * Subscribe Form Element
 *
 * @var \App\View\AppView $this
 */
?>

<div class="subscribe-section" id="subscribe-form">
    <div class="subscribe-header">
        <h3 class="subscribe-title">📧 <?= __('Receive Notifications') ?></h3>
        <p class="subscribe-description">
            <?= __('Subscribe to receive email updates about incidents and scheduled maintenance.') ?>
        </p>
    </div>

    <?= $this->Form->create(null, [
        'url' => ['controller' => 'Subscribers', 'action' => 'subscribe'],
        'class' => 'subscribe-form'
    ]) ?>
        <?= $this->Form->control('email', [
            'type' => 'email',
            'placeholder' => __('your@email.com'),
            'required' => true,
            'label' => false,
            'class' => 'subscribe-input',
            'autocomplete' => 'email'
        ]) ?>

        <button type="submit" class="subscribe-button">
            <span class="button-icon">📬</span>
            <span class="button-text"><?= __('Subscribe') ?></span>
        </button>

        <div class="subscribe-notice">
            <small>
                ℹ️ <?= __('You will only receive important alerts. You can unsubscribe at any time.') ?>
            </small>
        </div>
    <?= $this->Form->end() ?>
</div>
