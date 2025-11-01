<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Subscriber $subscriber
 */
$this->assign('title', 'Email Verificado');
?>

<div class="verify-page">
    <div class="verify-container">
        <div class="verify-icon success">
            ✅
        </div>

        <h1 class="verify-title">Email Verificado com Sucesso!</h1>

        <p class="verify-message">
            Obrigado por verificar seu email <strong><?= h($subscriber->email) ?></strong>
        </p>

        <div class="verify-info">
            <h3>O que acontece agora?</h3>
            <ul>
                <li>✓ Você começará a receber notificações sobre incidentes</li>
                <li>✓ Alertas serão enviados quando serviços ficarem offline</li>
                <li>✓ Você será informado quando os problemas forem resolvidos</li>
            </ul>
        </div>

        <div class="verify-actions">
            <?= $this->Html->link(
                '← Voltar para Status',
                ['controller' => 'Status', 'action' => 'index'],
                ['class' => 'btn btn-primary']
            ) ?>
        </div>

        <div class="verify-footer">
            <p>
                Não quer mais receber notificações?
                <?= $this->Html->link(
                    'Cancelar inscrição',
                    ['action' => 'unsubscribe', $subscriber->unsubscribe_token],
                    ['class' => 'unsubscribe-link']
                ) ?>
            </p>
        </div>
    </div>
</div>

<style>
.verify-page {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-2xl) var(--space-md);
}

.verify-container {
    max-width: 600px;
    width: 100%;
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: var(--space-2xl);
    box-shadow: var(--shadow-lg);
    text-align: center;
}

.verify-icon {
    font-size: 80px;
    margin-bottom: var(--space-lg);
    animation: bounce 1s ease;
}

@keyframes bounce {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-20px);
    }
}

.verify-title {
    font-size: 32px;
    font-weight: 700;
    color: var(--color-dark);
    margin-bottom: var(--space-md);
}

.verify-message {
    font-size: 16px;
    color: var(--color-gray-dark);
    margin-bottom: var(--space-xl);
}

.verify-message strong {
    color: var(--color-primary);
}

.verify-info {
    background: var(--color-gray-light);
    border-radius: var(--radius-md);
    padding: var(--space-lg);
    margin-bottom: var(--space-xl);
    text-align: left;
}

.verify-info h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-dark);
    margin-bottom: var(--space-md);
}

.verify-info ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.verify-info li {
    padding: var(--space-xs) 0;
    color: var(--color-gray-dark);
    font-size: 14px;
}

.verify-actions {
    margin-bottom: var(--space-lg);
}

.btn {
    display: inline-block;
    padding: var(--space-md) var(--space-xl);
    border-radius: var(--radius-md);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
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

.verify-footer {
    border-top: 1px solid var(--color-gray-light);
    padding-top: var(--space-md);
}

.verify-footer p {
    font-size: 13px;
    color: var(--color-gray-medium);
    margin: 0;
}

.unsubscribe-link {
    color: var(--color-error);
    text-decoration: none;
    font-weight: 500;
}

.unsubscribe-link:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .verify-title {
        font-size: 24px;
    }

    .verify-icon {
        font-size: 60px;
    }
}
</style>
