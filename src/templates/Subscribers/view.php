<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Subscriber $subscriber
 * @var int $emailLogsCount
 */
$this->assign('title', 'Detalhes do Inscrito');
?>

<div class="subscriber-view">
    <div class="page-header">
        <div>
            <h1><?= h($subscriber->email) ?></h1>
            <p><?= h($subscriber->name) ?: 'Inscrito de notificações' ?></p>
        </div>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(
                'Voltar',
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>
    </div>

    <!-- Status Overview -->
    <div class="status-overview">
        <div class="status-card <?= $subscriber->canReceiveNotifications() ? 'active' : 'inactive' ?>">
            <div class="status-icon">
                <?= $subscriber->canReceiveNotifications() ? '🟢' : '🔴' ?>
            </div>
            <div class="status-content">
                <h2><?= $subscriber->canReceiveNotifications() ? 'Ativo e Verificado' : 'Inativo ou Não Verificado' ?></h2>
                <p>
                    <?php if ($subscriber->verified && $subscriber->active): ?>
                        Pode receber notificações
                    <?php elseif (!$subscriber->verified): ?>
                        Aguardando verificação de email
                    <?php else: ?>
                        Inscrito desativado
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📧</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($emailLogsCount) ?></div>
                <div class="stat-label">Emails Recebidos</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📬</div>
            <div class="stat-content">
                <div class="stat-value"><?= isset($subscriber->subscriptions) ? count($subscriber->subscriptions) : 0 ?></div>
                <div class="stat-label">Monitores Inscritos</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-value"><?= $subscriber->verified ? 'Sim' : 'Não' ?></div>
                <div class="stat-label">Verificado</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-content">
                <div class="stat-value"><?= $subscriber->active ? 'Ativo' : 'Inativo' ?></div>
                <div class="stat-label">Status</div>
            </div>
        </div>
    </div>

    <!-- Subscriber Details -->
    <div class="card">
        <div class="card-header">Detalhes do Inscrito</div>
        <div class="details-grid">
            <div class="detail-item">
                <span class="detail-label">Email:</span>
                <code><?= h($subscriber->email) ?></code>
            </div>

            <div class="detail-item">
                <span class="detail-label">Nome:</span>
                <span><?= h($subscriber->name) ?: '-' ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Status de Verificação:</span>
                <span class="badge badge-<?= $subscriber->verified ? 'success' : 'warning' ?>">
                    <?= $subscriber->verified ? 'Verificado' : 'Pendente' ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Status Ativo:</span>
                <span class="badge badge-<?= $subscriber->active ? 'success' : 'error' ?>">
                    <?= $subscriber->active ? 'Ativo' : 'Inativo' ?>
                </span>
            </div>

            <?php if ($subscriber->verified_at): ?>
                <div class="detail-item">
                    <span class="detail-label">Verificado em:</span>
                    <span><?= $subscriber->verified_at->format('d/m/Y H:i:s') ?></span>
                </div>
            <?php endif; ?>

            <div class="detail-item">
                <span class="detail-label">Data de Inscrição:</span>
                <span><?= $subscriber->created->format('d/m/Y H:i:s') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Última Atualização:</span>
                <span><?= $subscriber->modified->format('d/m/Y H:i:s') ?></span>
            </div>

            <?php if (!empty($subscriber->verification_token)): ?>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <span class="detail-label">Token de Verificação:</span>
                    <code style="word-break: break-all; font-size: 12px;"><?= h($subscriber->verification_token) ?></code>
                </div>
            <?php endif; ?>

            <?php if (!empty($subscriber->unsubscribe_token)): ?>
                <div class="detail-item" style="grid-column: 1 / -1;">
                    <span class="detail-label">Token de Cancelamento:</span>
                    <code style="word-break: break-all; font-size: 12px;"><?= h($subscriber->unsubscribe_token) ?></code>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Subscribed Monitors -->
    <?php if (isset($subscriber->subscriptions) && count($subscriber->subscriptions) > 0): ?>
        <div class="card">
            <div class="card-header">
                <span>Monitores Inscritos</span>
                <span class="badge badge-info"><?= count($subscriber->subscriptions) ?> monitor<?= count($subscriber->subscriptions) > 1 ? 'es' : '' ?></span>
            </div>
            <div class="monitors-list">
                <?php foreach ($subscriber->subscriptions as $subscription): ?>
                    <?php if (isset($subscription->monitor)): ?>
                        <div class="monitor-item">
                            <div class="monitor-header">
                                <h4><?= h($subscription->monitor->name) ?></h4>
                                <span class="badge badge-info">
                                    <?= h(strtoupper($subscription->monitor->type)) ?>
                                </span>
                            </div>
                            <p><?= h($subscription->monitor->description) ?: 'Monitor de serviço' ?></p>
                            <div class="monitor-meta">
                                <span>Inscrito em: <?= $subscription->created->format('d/m/Y H:i') ?></span>
                                <?= $this->Html->link(
                                    'Ver Monitor',
                                    ['controller' => 'Monitors', 'action' => 'view', $subscription->monitor->id],
                                    ['class' => 'btn-link']
                                ) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="empty-state">
                <div class="empty-state-icon">📬</div>
                <p>Nenhum monitor inscrito ainda.</p>
                <small class="text-muted">O inscrito não se inscreveu em nenhum monitor específico.</small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="card">
        <div class="card-header">Ações</div>
        <div class="actions-grid">
            <?php if (!$subscriber->verified): ?>
                <?= $this->Form->postLink(
                    'Reenviar Verificação',
                    ['action' => 'resendVerification', $subscriber->id],
                    [
                        'class' => 'btn btn-primary',
                        'confirm' => 'Tem certeza que deseja reenviar o email de verificação?'
                    ]
                ) ?>
            <?php endif; ?>

            <?= $this->Form->postLink(
                $subscriber->active ? 'Desativar Inscrito' : 'Ativar Inscrito',
                ['action' => 'toggle', $subscriber->id],
                [
                    'class' => 'btn ' . ($subscriber->active ? 'btn-secondary' : 'btn-success'),
                    'confirm' => 'Tem certeza que deseja ' . ($subscriber->active ? 'desativar' : 'ativar') . ' este inscrito?'
                ]
            ) ?>

            <?= $this->Form->postLink(
                'Excluir Inscrito',
                ['action' => 'delete', $subscriber->id],
                [
                    'class' => 'btn btn-error',
                    'confirm' => 'Tem certeza que deseja excluir este inscrito? Esta ação não pode ser desfeita e todas as assinaturas serão removidas.'
                ]
            ) ?>
        </div>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-header h1 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 4px;
}

.page-header p {
    color: var(--color-gray-medium, #666);
    font-size: 14px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

.btn-success {
    background: #22c55e;
    color: white;
}

.btn-success:hover {
    background: #16a34a;
}

.btn-error {
    background: #ef4444;
    color: white;
}

.btn-error:hover {
    background: #dc2626;
}

.status-overview {
    margin-bottom: 24px;
}

.status-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 24px;
    border-left: 4px solid #6b7280;
}

.status-card.active {
    border-left-color: #22c55e;
    background: linear-gradient(135deg, #ffffff 0%, #E8F5E9 100%);
}

.status-card.inactive {
    border-left-color: #ef4444;
    background: linear-gradient(135deg, #ffffff 0%, #FFEBEE 100%);
}

.status-icon {
    font-size: 64px;
}

.status-content h2 {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 4px;
}

.status-content p {
    color: #666;
    font-size: 15px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    font-size: 40px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #333;
}

.stat-label {
    font-size: 13px;
    color: #666;
}

.card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 24px;
    overflow: hidden;
}

.card-header {
    background: #f8f9fa;
    padding: 16px 24px;
    font-size: 16px;
    font-weight: 600;
    color: #333;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 16px;
    padding: 24px;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.detail-label {
    font-size: 13px;
    font-weight: 600;
    color: #666;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-success {
    background: #dcfce7;
    color: #16a34a;
}

.badge-warning {
    background: #fef3c7;
    color: #d97706;
}

.badge-error {
    background: #fee2e2;
    color: #dc2626;
}

.badge-info {
    background: #dbeafe;
    color: #2563eb;
}

.monitors-list {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.monitor-item {
    padding: 16px;
    border-radius: 6px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
}

.monitor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.monitor-header h4 {
    margin: 0;
    font-size: 16px;
}

.monitor-meta {
    margin-top: 12px;
    display: flex;
    gap: 16px;
    align-items: center;
    font-size: 13px;
    color: #666;
}

.btn-link {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 500;
}

.btn-link:hover {
    text-decoration: underline;
}

.empty-state {
    padding: 60px 20px;
    text-align: center;
    color: #999;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.text-muted {
    color: #999;
    font-size: 13px;
}

.actions-grid {
    padding: 24px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .status-card {
        flex-direction: column;
        text-align: center;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .actions-grid {
        flex-direction: column;
    }

    .actions-grid .btn {
        width: 100%;
    }
}
</style>
