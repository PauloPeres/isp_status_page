<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\MonitorCheck $check
 * @var iterable<\App\Model\Entity\MonitorCheck> $previousChecks
 * @var iterable<\App\Model\Entity\MonitorCheck> $nextChecks
 * @var array $monitorStats
 */
$this->assign('title', 'Detalhes da Verificação');
?>

<style>
    .check-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .back-link {
        color: #3b82f6;
        text-decoration: none;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    .check-details {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .status-banner {
        padding: 16px 20px;
        border-radius: 8px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 16px;
        font-weight: 600;
    }

    .status-banner.success {
        background: #dcfce7;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }

    .status-banner.danger {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .detail-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        font-weight: 600;
    }

    .detail-value {
        font-size: 16px;
        color: #333;
        font-weight: 500;
    }

    .detail-value a {
        color: #3b82f6;
        text-decoration: none;
    }

    .detail-value a:hover {
        text-decoration: underline;
    }

    .message-box {
        background: #f8f9fa;
        border-left: 4px solid #3b82f6;
        padding: 16px;
        border-radius: 4px;
        margin-bottom: 24px;
    }

    .message-box.error {
        background: #fef2f2;
        border-left-color: #dc2626;
    }

    .message-box h4 {
        margin: 0 0 8px 0;
        font-size: 14px;
        color: #666;
        font-weight: 600;
    }

    .message-box pre {
        margin: 0;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .stats-section {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .stats-section h3 {
        margin: 0 0 16px 0;
        font-size: 18px;
        color: #333;
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .stat-item {
        text-align: center;
        padding: 16px;
        background: #f8f9fa;
        border-radius: 6px;
    }

    .stat-item-value {
        font-size: 24px;
        font-weight: bold;
        color: #333;
        margin-bottom: 4px;
    }

    .stat-item-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
    }

    .context-section {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 24px;
    }

    .context-section h3 {
        margin: 0 0 16px 0;
        font-size: 18px;
        color: #333;
    }

    .checks-timeline {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .timeline-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: 6px;
        transition: background 0.2s;
    }

    .timeline-item:hover {
        background: #f8f9fa;
    }

    .timeline-item.current {
        background: #eff6ff;
        border: 2px solid #3b82f6;
    }

    .timeline-status {
        font-size: 20px;
    }

    .timeline-content {
        flex: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .timeline-time {
        font-size: 13px;
        color: #666;
    }

    .timeline-response {
        font-family: 'Courier New', monospace;
        font-size: 12px;
        color: #666;
    }

    .no-context {
        text-align: center;
        padding: 20px;
        color: #999;
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

    @media (max-width: 768px) {
        .details-grid {
            grid-template-columns: 1fr;
        }

        .stats-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="check-header">
    <h2>📊 Detalhes da Verificação</h2>
    <div>
        <?= $this->Html->link('← Voltar', ['action' => 'index'], ['class' => 'back-link']) ?>
    </div>
</div>

<!-- Status Banner -->
<div class="status-banner <?= $check->status === 'success' ? 'success' : 'danger' ?>">
    <span style="font-size: 24px;"><?= $check->status === 'success' ? '✅' : '❌' ?></span>
    <span>
        <?= $check->status === 'success' ? 'Verificação bem-sucedida' : 'Verificação falhou' ?>
    </span>
</div>

<!-- Check Details -->
<div class="check-details">
    <h3 style="margin: 0 0 20px 0; font-size: 20px;">Informações da Verificação</h3>

    <div class="details-grid">
        <div class="detail-item">
            <span class="detail-label">Monitor</span>
            <span class="detail-value">
                <?= $this->Html->link(
                    h($check->monitor->name),
                    ['controller' => 'Monitors', 'action' => 'view', $check->monitor->id]
                ) ?>
            </span>
        </div>

        <div class="detail-item">
            <span class="detail-label">Tipo</span>
            <span class="detail-value"><?= h(strtoupper($check->monitor->type)) ?></span>
        </div>

        <div class="detail-item">
            <span class="detail-label">Data/Hora</span>
            <span class="detail-value">
                <?= h($check->checked_at->i18nFormat('dd/MM/yyyy HH:mm:ss')) ?>
            </span>
        </div>

        <div class="detail-item">
            <span class="detail-label">Status</span>
            <span class="detail-value">
                <span class="badge badge-<?= $check->status === 'success' ? 'success' : 'danger' ?>">
                    <?= h($check->status) ?>
                </span>
            </span>
        </div>

        <?php if ($check->response_time !== null): ?>
            <div class="detail-item">
                <span class="detail-label">Tempo de Resposta</span>
                <span class="detail-value" style="font-family: 'Courier New', monospace;">
                    <?= number_format($check->response_time, 2) ?>ms
                </span>
            </div>
        <?php endif; ?>

        <?php if ($check->status_code !== null): ?>
            <div class="detail-item">
                <span class="detail-label">Status Code</span>
                <span class="detail-value" style="font-family: 'Courier New', monospace;">
                    <?= h($check->status_code) ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Message -->
    <?php if ($check->message): ?>
        <div class="message-box <?= $check->status === 'failed' ? 'error' : '' ?>">
            <h4><?= $check->status === 'failed' ? 'Mensagem de Erro' : 'Mensagem' ?></h4>
            <pre><?= h($check->message) ?></pre>
        </div>
    <?php endif; ?>

    <!-- Response Details (if available) -->
    <?php if ($check->response_details): ?>
        <div class="message-box">
            <h4>Detalhes da Resposta</h4>
            <pre><?= h(json_encode(json_decode($check->response_details), JSON_PRETTY_PRINT)) ?></pre>
        </div>
    <?php endif; ?>
</div>

<!-- Monitor Statistics -->
<div class="stats-section">
    <h3>📈 Estatísticas do Monitor</h3>
    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-item-value"><?= number_format($monitorStats['totalChecks']) ?></div>
            <div class="stat-item-label">Total de Checks</div>
        </div>
        <div class="stat-item">
            <div class="stat-item-value" style="color: #22c55e;">
                <?= number_format($monitorStats['successChecks']) ?>
            </div>
            <div class="stat-item-label">Checks Bem-sucedidos</div>
        </div>
        <div class="stat-item">
            <div class="stat-item-value" style="color: #3b82f6;">
                <?php
                    $successRate = $monitorStats['totalChecks'] > 0
                        ? round(($monitorStats['successChecks'] / $monitorStats['totalChecks']) * 100, 2)
                        : 0;
                    echo number_format($successRate, 1);
                ?>%
            </div>
            <div class="stat-item-label">Taxa de Sucesso</div>
        </div>
        <div class="stat-item">
            <div class="stat-item-value">
                <?php if ($monitorStats['avgResponseTime'] && $monitorStats['avgResponseTime']->avg): ?>
                    <?= number_format($monitorStats['avgResponseTime']->avg, 0) ?>ms
                <?php else: ?>
                    <span style="font-size: 14px; color: #999;">N/A</span>
                <?php endif; ?>
            </div>
            <div class="stat-item-label">Tempo Médio</div>
        </div>
    </div>
</div>

<!-- Context: Surrounding Checks -->
<div class="context-section">
    <h3>🕒 Verificações Próximas</h3>

    <?php if ($previousChecks->count() > 0 || $nextChecks->count() > 0): ?>
        <div class="checks-timeline">
            <!-- Previous checks (reversed to show chronologically) -->
            <?php foreach (array_reverse($previousChecks->toArray()) as $prevCheck): ?>
                <div class="timeline-item">
                    <span class="timeline-status">
                        <?= $prevCheck->status === 'success' ? '✅' : '❌' ?>
                    </span>
                    <div class="timeline-content">
                        <span class="timeline-time">
                            <?= h($prevCheck->checked_at->i18nFormat('dd/MM/yyyy HH:mm:ss')) ?>
                        </span>
                        <?php if ($prevCheck->response_time !== null): ?>
                            <span class="timeline-response">
                                <?= number_format($prevCheck->response_time, 0) ?>ms
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Current check -->
            <div class="timeline-item current">
                <span class="timeline-status">
                    <?= $check->status === 'success' ? '✅' : '❌' ?>
                </span>
                <div class="timeline-content">
                    <span class="timeline-time" style="font-weight: 600;">
                        <?= h($check->checked_at->i18nFormat('dd/MM/yyyy HH:mm:ss')) ?> (atual)
                    </span>
                    <?php if ($check->response_time !== null): ?>
                        <span class="timeline-response" style="font-weight: 600;">
                            <?= number_format($check->response_time, 0) ?>ms
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Next checks -->
            <?php foreach ($nextChecks as $nextCheck): ?>
                <div class="timeline-item">
                    <span class="timeline-status">
                        <?= $nextCheck->status === 'success' ? '✅' : '❌' ?>
                    </span>
                    <div class="timeline-content">
                        <span class="timeline-time">
                            <?= h($nextCheck->checked_at->i18nFormat('dd/MM/yyyy HH:mm:ss')) ?>
                        </span>
                        <?php if ($nextCheck->response_time !== null): ?>
                            <span class="timeline-response">
                                <?= number_format($nextCheck->response_time, 0) ?>ms
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-context">
            <p>Não há verificações próximas disponíveis</p>
        </div>
    <?php endif; ?>
</div>

<div style="margin-top: 24px;">
    <?= $this->Html->link('← Voltar para Verificações', ['action' => 'index'], [
        'class' => 'btn btn-secondary',
        'style' => 'display: inline-block; padding: 10px 20px; background: #6b7280; color: white; text-decoration: none; border-radius: 6px;'
    ]) ?>
    <?= $this->Html->link('Ver Monitor', ['controller' => 'Monitors', 'action' => 'view', $check->monitor->id], [
        'class' => 'btn btn-primary',
        'style' => 'display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px; margin-left: 8px;'
    ]) ?>
</div>
