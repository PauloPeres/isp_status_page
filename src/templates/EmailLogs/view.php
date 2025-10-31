<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\AlertLog $emailLog
 */
$this->assign('title', 'Detalhes do Email');
?>

<div class="email-log-view">
    <div class="page-header">
        <div>
            <h1>Detalhes do Email</h1>
            <p>Email #<?= h($emailLog->id) ?> - <?= h($emailLog->recipient) ?></p>
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
        <div class="status-card <?= $emailLog->status === 'sent' ? 'sent' : 'failed' ?>">
            <div class="status-icon">
                <?= $emailLog->status === 'sent' ? '✅' : ($emailLog->status === 'failed' ? '❌' : '⏳') ?>
            </div>
            <div class="status-content">
                <h2>
                    <?php if ($emailLog->status === 'sent'): ?>
                        Email Enviado
                    <?php elseif ($emailLog->status === 'failed'): ?>
                        Falha no Envio
                    <?php elseif ($emailLog->status === 'queued'): ?>
                        Na Fila de Envio
                    <?php else: ?>
                        <?= h(ucfirst($emailLog->status)) ?>
                    <?php endif; ?>
                </h2>
                <p>
                    <?php if ($emailLog->sent_at): ?>
                        Enviado em <span class="local-datetime" data-utc="<?= $emailLog->sent_at->format('c') ?>"></span>
                    <?php else: ?>
                        Criado em <span class="local-datetime" data-utc="<?= $emailLog->created->format('c') ?>"></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Email Details -->
    <div class="card">
        <div class="card-header">Informações do Email</div>
        <div class="details-grid">
            <div class="detail-item">
                <span class="detail-label">Destinatário:</span>
                <code><?= h($emailLog->recipient) ?></code>
            </div>

            <div class="detail-item">
                <span class="detail-label">Canal:</span>
                <span class="badge badge-info"><?= h(strtoupper($emailLog->channel)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Status:</span>
                <span class="badge badge-<?= $emailLog->status === 'sent' ? 'success' : ($emailLog->status === 'failed' ? 'danger' : 'warning') ?>">
                    <?= h(ucfirst($emailLog->status)) ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Data de Criação:</span>
                <span class="local-datetime" data-utc="<?= $emailLog->created->format('c') ?>"></span>
            </div>

            <?php if ($emailLog->sent_at): ?>
                <div class="detail-item">
                    <span class="detail-label">Data de Envio:</span>
                    <span class="local-datetime" data-utc="<?= $emailLog->sent_at->format('c') ?>"></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Tempo de Processamento:</span>
                    <span>
                        <?php
                        $diff = $emailLog->created->diff($emailLog->sent_at);
                        echo $diff->format('%H:%I:%S');
                        ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Monitor Information -->
    <?php if (isset($emailLog->monitor)): ?>
        <div class="card">
            <div class="card-header">Monitor Relacionado</div>
            <div class="monitor-info">
                <div class="monitor-item">
                    <h4><?= h($emailLog->monitor->name) ?></h4>
                    <span class="badge badge-info"><?= h(strtoupper($emailLog->monitor->type)) ?></span>
                    <?php if ($emailLog->monitor->description): ?>
                        <p><?= h($emailLog->monitor->description) ?></p>
                    <?php endif; ?>
                    <div class="monitor-meta">
                        <?= $this->Html->link(
                            'Ver Monitor',
                            ['controller' => 'Monitors', 'action' => 'view', $emailLog->monitor->id],
                            ['class' => 'btn-link']
                        ) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Incident Information -->
    <?php if (isset($emailLog->incident)): ?>
        <div class="card">
            <div class="card-header">Incidente Relacionado</div>
            <div class="incident-info">
                <div class="incident-item">
                    <h4><?= h($emailLog->incident->title) ?></h4>
                    <span class="badge badge-<?= $emailLog->incident->status === 'resolved' ? 'success' : 'danger' ?>">
                        <?= h(ucfirst($emailLog->incident->status)) ?>
                    </span>
                    <?php if ($emailLog->incident->description): ?>
                        <p><?= h($emailLog->incident->description) ?></p>
                    <?php endif; ?>
                    <div class="incident-meta">
                        <span>Iniciado: <span class="local-datetime" data-utc="<?= $emailLog->incident->started_at->format('c') ?>"></span></span>
                        <?php if ($emailLog->incident->resolved_at): ?>
                            <span>Resolvido: <span class="local-datetime" data-utc="<?= $emailLog->incident->resolved_at->format('c') ?>"></span></span>
                        <?php endif; ?>
                        <?= $this->Html->link(
                            'Ver Incidente',
                            ['controller' => 'Incidents', 'action' => 'view', $emailLog->incident->id],
                            ['class' => 'btn-link']
                        ) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Alert Rule Information -->
    <?php if (isset($emailLog->alert_rule)): ?>
        <div class="card">
            <div class="card-header">Regra de Alerta</div>
            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-label">Nome:</span>
                    <span><?= h($emailLog->alert_rule->name ?? 'N/A') ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Error Information -->
    <?php if ($emailLog->error_message): ?>
        <div class="card error-card">
            <div class="card-header" style="background: #fee2e2; color: #dc2626; border-color: #fecaca;">
                ⚠️ Mensagem de Erro
            </div>
            <div class="error-content">
                <code style="display: block; padding: 16px; background: #fef2f2; border-radius: 4px; white-space: pre-wrap; word-break: break-word;">
                    <?= h($emailLog->error_message) ?>
                </code>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <?php if ($emailLog->status === 'failed'): ?>
        <div class="card">
            <div class="card-header">Ações</div>
            <div class="actions-grid">
                <?= $this->Form->postLink(
                    'Reenviar Email',
                    ['action' => 'resend', $emailLog->id],
                    [
                        'class' => 'btn btn-primary',
                        'confirm' => 'Tem certeza que deseja reenviar este email?'
                    ]
                ) ?>
            </div>
        </div>
    <?php endif; ?>
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
    color: #666;
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

.status-card.sent {
    border-left-color: #22c55e;
    background: linear-gradient(135deg, #ffffff 0%, #E8F5E9 100%);
}

.status-card.failed {
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

.badge-danger {
    background: #fee2e2;
    color: #dc2626;
}

.badge-warning {
    background: #fef3c7;
    color: #d97706;
}

.badge-info {
    background: #dbeafe;
    color: #2563eb;
}

.monitor-info,
.incident-info {
    padding: 24px;
}

.monitor-item,
.incident-item {
    padding: 16px;
    border-radius: 6px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
}

.monitor-item h4,
.incident-item h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
}

.monitor-meta,
.incident-meta {
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

.error-content {
    padding: 24px;
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
