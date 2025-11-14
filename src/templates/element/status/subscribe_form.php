<?php
/**
 * Subscribe Form Element
 *
 * @var \App\View\AppView $this
 */
?>

<div class="subscribe-section" id="subscribe-form">
    <div class="subscribe-header">
        <h3 class="subscribe-title">📧 <?= __('Receba Notificações') ?></h3>
        <p class="subscribe-description">
            <?= __('Inscreva-se para receber atualizações por email sobre incidentes e manutenções programadas.') ?>
        </p>
    </div>

    <?= $this->Form->create(null, [
        'url' => ['controller' => 'Subscribers', 'action' => 'subscribe'],
        'class' => 'subscribe-form'
    ]) ?>
        <?= $this->Form->control('email', [
            'type' => 'email',
            'placeholder' => __('seu@email.com'),
            'required' => true,
            'label' => false,
            'class' => 'subscribe-input',
            'autocomplete' => 'email'
        ]) ?>

        <button type="submit" class="subscribe-button">
            <span class="button-icon">📬</span>
            <span class="button-text"><?= __('Inscrever-se') ?></span>
        </button>

        <div class="subscribe-notice">
            <small>
                ℹ️ <?= __('Você receberá apenas alertas importantes. Pode cancelar a qualquer momento.') ?>
            </small>
        </div>
    <?= $this->Form->end() ?>
</div>
