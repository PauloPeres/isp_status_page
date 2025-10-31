<?php
/**
 * Subscribe Form Element
 *
 * @var \App\View\AppView $this
 */
?>

<div class="subscribe-section">
    <div class="subscribe-header">
        <h3 class="subscribe-title">📧 Receba Notificações</h3>
        <p class="subscribe-description">
            Inscreva-se para receber atualizações por email sobre incidentes e manutenções programadas.
        </p>
    </div>

    <?= $this->Form->create(null, [
        'url' => ['controller' => 'Subscribers', 'action' => 'subscribe'],
        'class' => 'subscribe-form'
    ]) ?>
        <?= $this->Form->control('email', [
            'type' => 'email',
            'placeholder' => 'seu@email.com',
            'required' => true,
            'label' => false,
            'class' => 'subscribe-input',
            'autocomplete' => 'email'
        ]) ?>

        <button type="submit" class="subscribe-button">
            <span class="button-icon">📬</span>
            <span class="button-text">Inscrever-se</span>
        </button>

        <div class="subscribe-notice">
            <small>
                ℹ️ Você receberá apenas alertas importantes. Pode cancelar a qualquer momento.
            </small>
        </div>
    <?= $this->Form->end() ?>
</div>
