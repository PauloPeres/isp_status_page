<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 * @var float $uptime
 * @var float $avgResponseTime
 * @var int $totalChecks
 */
$this->assign('title', 'Detalhes do Monitor');
?>

<div class="monitors-view">
    <div class="page-header">
        <div>
            <h1>🖥️ <?= h($monitor->name) ?></h1>
            <p><?= h($monitor->description) ?: 'Monitor de serviço' ?></p>
        </div>
        <div style="display: flex; gap: 8px;">
            <?= $this->Html->link(
                'Editar',
                ['action' => 'edit', $monitor->id],
                ['class' => 'btn btn-primary']
            ) ?>
            <?= $this->Html->link(
                'Voltar',
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>
    </div>

    <!-- Status Overview -->
    <div class="status-overview">
        <div class="status-card <?= $monitor->status ?>">
            <div class="status-icon">
                <?= $monitor->status === 'up' ? '🟢' : ($monitor->status === 'down' ? '🔴' : '⚪') ?>
            </div>
            <div class="status-content">
                <h2><?= h(ucfirst($monitor->status)) ?></h2>
                <p><?= $monitor->active ? 'Monitoramento ativo' : 'Monitoramento pausado' ?></p>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($uptime, 2) ?>%</div>
                <div class="stat-label">Uptime (24h)</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-content">
                <div class="stat-value"><?= $avgResponseTime ? number_format($avgResponseTime) . 'ms' : '-' ?></div>
                <div class="stat-label">Tempo Médio</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔍</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($totalChecks) ?></div>
                <div class="stat-label">Verificações (24h)</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🕐</div>
            <div class="stat-content">
                <div class="stat-value"><?= $monitor->interval ?>s</div>
                <div class="stat-label">Intervalo</div>
            </div>
        </div>
    </div>

    <!-- Monitor Details -->
    <div class="card">
        <div class="card-header">📋 Detalhes do Monitor</div>
        <div class="details-grid">
            <div class="detail-item">
                <span class="detail-label">Tipo:</span>
                <span class="badge badge-info"><?= h(strtoupper($monitor->type)) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Alvo:</span>
                <code><?= h($monitor->target) ?></code>
            </div>

            <?php if ($monitor->type === 'http' && $monitor->expected_status_code): ?>
                <div class="detail-item">
                    <span class="detail-label">Status HTTP Esperado:</span>
                    <code><?= h($monitor->expected_status_code) ?></code>
                </div>
            <?php endif; ?>

            <?php if ($monitor->type === 'port' && $monitor->port): ?>
                <div class="detail-item">
                    <span class="detail-label">Porta:</span>
                    <code><?= h($monitor->port) ?></code>
                </div>
            <?php endif; ?>

            <div class="detail-item">
                <span class="detail-label">Intervalo:</span>
                <span><?= $monitor->interval ?> segundos</span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Timeout:</span>
                <span><?= $monitor->timeout ?> segundos</span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Última Verificação:</span>
                <span><?= $monitor->last_check ? $monitor->last_check->format('d/m/Y H:i:s') : 'Nunca' ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Tempo de Resposta:</span>
                <span><?= $monitor->response_time ? number_format($monitor->response_time) . ' ms' : '-' ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Criado em:</span>
                <span><?= $monitor->created->format('d/m/Y H:i:s') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Última Atualização:</span>
                <span><?= $monitor->modified->format('d/m/Y H:i:s') ?></span>
            </div>
        </div>
    </div>

    <!-- Recent Checks -->
    <?php if ($monitor->monitor_checks && count($monitor->monitor_checks) > 0): ?>
        <div class="card">
            <div class="card-header">
                <span>📊 Últimas Verificações</span>
                <span class="badge badge-info"><?= count($monitor->monitor_checks) ?> registros</span>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Data/Hora</th>
                            <th>Tempo de Resposta</th>
                            <th>Código de Status</th>
                            <th>Mensagem de Erro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monitor->monitor_checks as $check): ?>
                            <tr>
                                <td>
                                    <span class="status-indicator status-<?= h($check->status) ?>"></span>
                                </td>
                                <td><?= $check->created->format('d/m/Y H:i:s') ?></td>
                                <td>
                                    <?php if ($check->response_time): ?>
                                        <span class="<?= $check->response_time > 1000 ? 'text-error' : ($check->response_time > 500 ? 'text-warning' : 'text-success') ?>">
                                            <?= number_format($check->response_time) ?> ms
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $check->status_code ? h($check->status_code) : '-' ?>
                                </td>
                                <td>
                                    <?php if ($check->error_message): ?>
                                        <span class="text-error" title="<?= h($check->error_message) ?>">
                                            <?= h(mb_substr($check->error_message, 0, 50)) ?>
                                            <?= mb_strlen($check->error_message) > 50 ? '...' : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-success">✓ OK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="empty-state">
                <div class="empty-state-icon">📊</div>
                <p>Nenhuma verificação realizada ainda.</p>
                <small class="text-muted">As verificações começarão automaticamente se o monitor estiver ativo.</small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Incidents -->
    <?php if ($monitor->incidents && count($monitor->incidents) > 0): ?>
        <div class="card">
            <div class="card-header">
                <span>🚨 Incidentes Recentes</span>
                <span class="badge badge-error"><?= count($monitor->incidents) ?> registros</span>
            </div>
            <div class="incidents-list">
                <?php foreach ($monitor->incidents as $incident): ?>
                    <div class="incident-item">
                        <div class="incident-header">
                            <h4><?= h($incident->title) ?></h4>
                            <span class="badge badge-<?= $incident->status === 'resolved' ? 'success' : 'error' ?>">
                                <?= h($incident->status) ?>
                            </span>
                        </div>
                        <p><?= h($incident->description) ?></p>
                        <div class="incident-meta">
                            <span>📅 Iniciado: <?= $incident->started_at->format('d/m/Y H:i') ?></span>
                            <?php if ($incident->resolved_at): ?>
                                <span>✅ Resolvido: <?= $incident->resolved_at->format('d/m/Y H:i') ?></span>
                                <span>⏱️ Duração: <?= gmdate('H:i:s', $incident->duration ?? 0) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="card">
        <div class="card-header">⚙️ Ações</div>
        <div class="actions-grid">
            <?= $this->Form->postLink(
                $monitor->active ? 'Pausar Monitoramento' : 'Ativar Monitoramento',
                ['action' => 'toggle', $monitor->id],
                [
                    'class' => 'btn ' . ($monitor->active ? 'btn-secondary' : 'btn-success'),
                    'confirm' => 'Tem certeza que deseja ' . ($monitor->active ? 'pausar' : 'ativar') . ' este monitor?'
                ]
            ) ?>

            <?= $this->Html->link(
                'Editar Configurações',
                ['action' => 'edit', $monitor->id],
                ['class' => 'btn btn-primary']
            ) ?>

            <?= $this->Form->postLink(
                'Excluir Monitor',
                ['action' => 'delete', $monitor->id],
                [
                    'class' => 'btn btn-error',
                    'confirm' => 'Tem certeza que deseja excluir este monitor? Esta ação não pode ser desfeita e todos os dados históricos serão perdidos.'
                ]
            ) ?>
        </div>
    </div>
</div>

<style>
.status-overview {
    margin-bottom: 24px;
}

.status-card {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: center;
    gap: 24px;
    border-left: 4px solid var(--color-gray-medium);
}

.status-card.up {
    border-left-color: var(--color-success);
    background: linear-gradient(135deg, #ffffff 0%, #E8F5E9 100%);
}

.status-card.down {
    border-left-color: var(--color-error);
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
    color: var(--color-gray-medium);
    font-size: 15px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: var(--color-white);
    padding: 20px;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
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
    color: var(--color-dark);
}

.stat-label {
    font-size: 13px;
    color: var(--color-gray-medium);
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
    color: var(--color-gray-medium);
}

.incidents-list {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.incident-item {
    padding: 16px;
    border-radius: var(--radius-md);
    background: var(--color-gray-light);
}

.incident-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.incident-header h4 {
    margin: 0;
    font-size: 16px;
}

.incident-meta {
    margin-top: 12px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 13px;
    color: var(--color-gray-medium);
}

.actions-grid {
    padding: 24px;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.text-success {
    color: var(--color-success);
}

.text-warning {
    color: var(--color-warning);
}

.text-error {
    color: var(--color-error);
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
