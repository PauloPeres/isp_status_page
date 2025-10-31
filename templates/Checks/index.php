<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\MonitorCheck> $checks
 * @var array $stats
 * @var array $monitors
 * @var string $period
 */
$this->assign('title', 'Verificações de Monitores');
?>

<style>
    .checks-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .stat-card-mini {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .stat-label {
        font-size: 12px;
        color: #666;
        text-transform: uppercase;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #333;
    }

    .stat-value.success { color: #22c55e; }
    .stat-value.error { color: #ef4444; }
    .stat-value.info { color: #3b82f6; }

    .filters-card {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
    }

    .filters-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        align-items: end;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-size: 13px;
        font-weight: 600;
        color: #444;
        margin-bottom: 6px;
    }

    .filter-group select {
        padding: 8px 12px;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        font-size: 14px;
        background: white;
    }

    .filter-buttons {
        display: flex;
        gap: 8px;
    }

    .btn-filter {
        padding: 8px 16px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
    }

    .btn-filter:hover {
        background: #2563eb;
    }

    .btn-clear {
        padding: 8px 16px;
        background: white;
        color: #666;
        border: 1px solid #d0d0d0;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
    }

    .checks-table {
        width: 100%;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .checks-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .checks-table th {
        background: #f8f9fa;
        padding: 12px 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        border-bottom: 2px solid #e0e0e0;
    }

    .checks-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }

    .checks-table tr:last-child td {
        border-bottom: none;
    }

    .checks-table tr:hover {
        background: #f8f9fa;
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

    .response-time {
        font-family: 'Courier New', monospace;
        color: #666;
        font-size: 13px;
    }

    .monitor-link {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
    }

    .monitor-link:hover {
        text-decoration: underline;
    }

    .check-message {
        color: #666;
        font-size: 13px;
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .action-buttons {
        display: flex;
        gap: 4px;
        justify-content: flex-end;
    }

    .btn-action {
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-weight: 500;
        display: inline-block;
    }

    .btn-action-view {
        background: #3b82f6;
        color: white;
    }

    .btn-action-view:hover {
        background: #2563eb;
    }

    .no-checks {
        text-align: center;
        padding: 60px 20px;
        color: #999;
    }

    .pagination {
        margin-top: 24px;
        display: flex;
        justify-content: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .pagination a,
    .pagination span {
        padding: 8px 12px;
        border: 1px solid #d0d0d0;
        border-radius: 4px;
        color: #666;
        text-decoration: none;
        font-size: 14px;
    }

    .pagination a:hover {
        background: #f8f9fa;
        border-color: #3b82f6;
        color: #3b82f6;
    }

    .pagination .active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .pagination .disabled {
        color: #ccc;
        cursor: not-allowed;
    }

    .pagination-info {
        text-align: center;
        margin-top: 12px;
        font-size: 13px;
        color: #666;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .filters-row {
            grid-template-columns: 1fr;
        }

        .checks-table {
            overflow-x: auto;
        }
    }
</style>

<div class="checks-header">
    <h2>📈 Verificações de Monitores</h2>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card-mini">
        <div class="stat-label">Total Checks</div>
        <div class="stat-value info"><?= number_format($stats['total']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label">Sucesso</div>
        <div class="stat-value success"><?= number_format($stats['success']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label">Falhas</div>
        <div class="stat-value error"><?= number_format($stats['failed']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label">Taxa de Sucesso</div>
        <div class="stat-value <?= $stats['successRate'] >= 95 ? 'success' : ($stats['successRate'] >= 80 ? 'info' : 'error') ?>">
            <?= number_format($stats['successRate'], 1) ?>%
        </div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label">Tempo Médio</div>
        <div class="stat-value">
            <?php if ($stats['avgResponseTime']): ?>
                <?= number_format($stats['avgResponseTime'], 0) ?>ms
            <?php else: ?>
                <span style="font-size: 18px; color: #999;">N/A</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'filters-form']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label>Monitor</label>
            <?= $this->Form->control('monitor_id', [
                'options' => ['' => 'Todos os Monitores'] + $monitors,
                'default' => $this->request->getQuery('monitor_id'),
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label>Status</label>
            <?= $this->Form->control('status', [
                'options' => [
                    '' => 'Todos',
                    'success' => 'Sucesso',
                    'failed' => 'Falha',
                ],
                'default' => $this->request->getQuery('status'),
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label>Período</label>
            <?= $this->Form->control('period', [
                'options' => [
                    '24h' => 'Últimas 24 horas',
                    '7d' => 'Últimos 7 dias',
                    '30d' => 'Últimos 30 dias',
                    'all' => 'Todos',
                ],
                'default' => $period,
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-buttons">
            <?= $this->Form->button('Filtrar', ['type' => 'submit', 'class' => 'btn-filter']) ?>
            <?= $this->Html->link('Limpar', ['action' => 'index'], ['class' => 'btn-clear']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<!-- Checks Table -->
<div class="checks-table">
    <?php if ($checks->count() > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Monitor</th>
                    <th>Status</th>
                    <th>Tempo Resposta</th>
                    <th>Mensagem</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $check): ?>
                    <tr>
                        <td>
                            <strong><?= h($check->checked_at->i18nFormat('dd/MM/yyyy')) ?></strong><br>
                            <span style="color: #666; font-size: 13px;">
                                <?= h($check->checked_at->i18nFormat('HH:mm:ss')) ?>
                            </span>
                        </td>
                        <td>
                            <?= $this->Html->link(
                                h($check->monitor->name),
                                ['controller' => 'Monitors', 'action' => 'view', $check->monitor->id],
                                ['class' => 'monitor-link']
                            ) ?>
                            <br>
                            <span style="color: #999; font-size: 12px;">
                                <?= h(strtoupper($check->monitor->type)) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?= $check->status === 'success' ? 'success' : 'danger' ?>">
                                <?= $check->status === 'success' ? '✅ Sucesso' : '❌ Falha' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($check->response_time !== null): ?>
                                <span class="response-time"><?= number_format($check->response_time, 0) ?>ms</span>
                            <?php else: ?>
                                <span style="color: #ccc;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($check->message): ?>
                                <span class="check-message" title="<?= h($check->message) ?>">
                                    <?= h($check->message) ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #ccc;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-buttons">
                                <?= $this->Html->link(
                                    'Ver',
                                    ['action' => 'view', $check->id],
                                    ['class' => 'btn-action btn-action-view', 'title' => 'Ver detalhes']
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-checks">
            <p style="font-size: 18px; margin-bottom: 8px;">📭 Nenhuma verificação encontrada</p>
            <p>Tente ajustar os filtros ou aguarde as próximas verificações.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($checks->count() > 0): ?>
    <div class="pagination">
        <?= $this->Paginator->first('« Primeira') ?>
        <?= $this->Paginator->prev('‹ Anterior') ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next('Próxima ›') ?>
        <?= $this->Paginator->last('Última »') ?>
    </div>
    <div class="pagination-info">
        <?= $this->Paginator->counter('Página {{page}} de {{pages}}, mostrando {{current}} registro(s) de {{count}} no total') ?>
    </div>
<?php endif; ?>
