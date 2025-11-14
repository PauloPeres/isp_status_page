<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Subscriber $subscriber
 * @var bool $success
 */
$this->assign('title', __d('subscribers', 'Cancelar Inscrição'));
?>

<div class="unsubscribe-page">
    <div class="unsubscribe-container">
        <?php if (isset($success) && $success): ?>
            <!-- Success State -->
            <div class="unsubscribe-icon success">
                ✅
            </div>

            <h1 class="unsubscribe-title"><?= __d('subscribers', 'Inscrição Cancelada') ?></h1>

            <p class="unsubscribe-message">
                <?= __d('subscribers', 'Você foi desinscrito com sucesso. Não receberá mais notificações no email') ?> <strong><?= h($subscriber->email) ?></strong>
            </p>

            <div class="unsubscribe-info">
                <p><?= __d('subscribers', 'Sentiremos sua falta! 😢') ?></p>
                <p><?= __d('subscribers', 'Se você mudar de ideia, pode se inscrever novamente a qualquer momento na página de status.') ?></p>
            </div>

            <div class="unsubscribe-actions">
                <?= $this->Html->link(
                    '← ' . __d('subscribers', 'Voltar para Status'),
                    ['controller' => 'Status', 'action' => 'index'],
                    ['class' => 'btn btn-primary']
                ) ?>
            </div>

        <?php else: ?>
            <!-- Confirmation State -->
            <div class="unsubscribe-icon warning">
                ⚠️
            </div>

            <h1 class="unsubscribe-title"><?= __d('subscribers', 'Cancelar Inscrição?') ?></h1>

            <p class="unsubscribe-message">
                <?= __d('subscribers', 'Tem certeza que deseja cancelar as notificações para') ?> <strong><?= h($subscriber->email) ?></strong>?
            </p>

            <div class="unsubscribe-info">
                <h3><?= __d('subscribers', 'Você deixará de receber:') ?></h3>
                <ul>
                    <li>✗ <?= __d('subscribers', 'Notificações sobre incidentes') ?></li>
                    <li>✗ <?= __d('subscribers', 'Alertas de serviços offline') ?></li>
                    <li>✗ <?= __d('subscribers', 'Informações sobre resoluções de problemas') ?></li>
                </ul>
            </div>

            <div class="unsubscribe-actions">
                <?= $this->Form->create(null, ['class' => 'unsubscribe-form']) ?>
                    <button type="submit" class="btn btn-danger">
                        <?= __d('subscribers', 'Sim, cancelar inscrição') ?>
                    </button>
                    <?= $this->Html->link(
                        __d('subscribers', 'Não, manter inscrição'),
                        ['controller' => 'Status', 'action' => 'index'],
                        ['class' => 'btn btn-secondary']
                    ) ?>
                <?= $this->Form->end() ?>
            </div>

            <div class="unsubscribe-footer">
                <p>
                    <small>
                        <?= __d('subscribers', 'Você pode se reinscrever a qualquer momento pela página de status.') ?>
                    </small>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.unsubscribe-page {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-2xl) var(--space-md);
}

.unsubscribe-container {
    max-width: 600px;
    width: 100%;
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: var(--space-2xl);
    box-shadow: var(--shadow-lg);
    text-align: center;
}

.unsubscribe-icon {
    font-size: 80px;
    margin-bottom: var(--space-lg);
}

.unsubscribe-icon.warning {
    animation: shake 0.5s ease;
}

.unsubscribe-icon.success {
    animation: bounce 1s ease;
}

@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25% {
        transform: translateX(-10px);
    }
    75% {
        transform: translateX(10px);
    }
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-20px);
    }
}

.unsubscribe-title {
    font-size: 32px;
    font-weight: 700;
    color: var(--color-dark);
    margin-bottom: var(--space-md);
}

.unsubscribe-message {
    font-size: 16px;
    color: var(--color-gray-dark);
    margin-bottom: var(--space-xl);
}

.unsubscribe-message strong {
    color: var(--color-error);
}

.unsubscribe-info {
    background: var(--color-gray-light);
    border-radius: var(--radius-md);
    padding: var(--space-lg);
    margin-bottom: var(--space-xl);
    text-align: left;
}

.unsubscribe-info h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-dark);
    margin-bottom: var(--space-md);
    text-align: center;
}

.unsubscribe-info p {
    font-size: 14px;
    color: var(--color-gray-dark);
    margin: var(--space-sm) 0;
    text-align: center;
}

.unsubscribe-info ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.unsubscribe-info li {
    padding: var(--space-xs) 0;
    color: var(--color-gray-dark);
    font-size: 14px;
}

.unsubscribe-form {
    display: flex;
    gap: var(--space-md);
    justify-content: center;
    flex-wrap: wrap;
}

.unsubscribe-actions {
    margin-bottom: var(--space-lg);
}

.btn {
    display: inline-block;
    padding: var(--space-md) var(--space-xl);
    border-radius: var(--radius-md);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 15px;
}

.btn-primary {
    background: var(--color-primary);
    color: var(--color-white);
}

.btn-primary:hover {
    background: #1565C0;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background: var(--color-gray-medium);
    color: var(--color-white);
}

.btn-secondary:hover {
    background: var(--color-gray-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-danger {
    background: var(--color-error);
    color: var(--color-white);
}

.btn-danger:hover {
    background: #c62828;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.unsubscribe-footer {
    border-top: 1px solid var(--color-gray-light);
    padding-top: var(--space-md);
}

.unsubscribe-footer p {
    font-size: 13px;
    color: var(--color-gray-medium);
    margin: 0;
}

@media (max-width: 768px) {
    .unsubscribe-title {
        font-size: 24px;
    }

    .unsubscribe-icon {
        font-size: 60px;
    }

    .unsubscribe-form {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }
}
</style>
