<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\AlertLog> $emailLogs
 * @var array $stats
 * @var string $period
 */
$this->assign('title', __('Logs de Emails'));
?>

<style>
    .email-logs-header {
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

    .filter-group select,
    .filter-group input {
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
        text-decoration: none;
        display: inline-block;
    }

    .email-logs-table {
        width: 100%;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .email-logs-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .email-logs-table th {
        background: #f8f9fa;
        padding: 12px 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        border-bottom: 2px solid #e0e0e0;
    }

    .email-logs-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }

    .email-logs-table tr:last-child td {
        border-bottom: none;
    }

    .email-logs-table tr:hover {
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

    .badge-warning {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-info {
        background: #dbeafe;
        color: #2563eb;
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

    .no-emails {
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

        .email-logs-table {
            overflow-x: auto;
        }
    }
</style>

<div class="email-logs-header">
    <h2><?= __('Logs de Emails') ?></h2>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Total Enviados') ?></div>
        <div class="stat-value info"><?= number_format($stats['total']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Sucesso') ?></div>
        <div class="stat-value success"><?= number_format($stats['sent']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Falhas') ?></div>
        <div class="stat-value error"><?= number_format($stats['failed']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Taxa de Sucesso') ?></div>
        <div class="stat-value <?= $stats['successRate'] >= 95 ? 'success' : ($stats['successRate'] >= 80 ? 'info' : 'error') ?>">
            <?= number_format($stats['successRate'], 1) ?>%
        </div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __('Hoje') ?></div>
        <div class="stat-value info"><?= number_format($stats['today']) ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'filters-form']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label><?= __('Buscar') ?></label>
            <?= $this->Form->control('search', [
                'label' => false,
                'placeholder' => __('Email ou assunto...'),
                'value' => $this->request->getQuery('search'),
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __('Status') ?></label>
            <?= $this->Form->control('status', [
                'options' => [
                    '' => __('Todos'),
                    'sent' => __('Enviados'),
                    'failed' => __('Falhas'),
                    'queued' => __('Na Fila'),
                ],
                'default' => $this->request->getQuery('status'),
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __('Período') ?></label>
            <?= $this->Form->control('period', [
                'options' => [
                    '24h' => __('Últimas 24 horas'),
                    '7d' => __('Últimos 7 dias'),
                    '30d' => __('Últimos 30 dias'),
                    'all' => __('Todos'),
                ],
                'default' => $period,
                'label' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-buttons">
            <?= $this->Form->button(__('Filtrar'), ['type' => 'submit', 'class' => 'btn-filter']) ?>
            <?= $this->Html->link(__('Limpar'), ['action' => 'index'], ['class' => 'btn-clear']) ?>
        </div>
    </div>
    <?= $this->Form->end() ?>
</div>

<!-- Email Logs Table -->
<div class="email-logs-table">
    <?php if ($emailLogs->count() > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('Data/Hora') ?></th>
                    <th><?= __('Destinatário') ?></th>
                    <th><?= __('Assunto') ?></th>
                    <th><?= __('Status') ?></th>
                    <th><?= __('Monitor') ?></th>
                    <th style="text-align: right;"><?= __('Ações') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($emailLogs as $log): ?>
                    <tr>
                        <td>
                            <span class="local-datetime" data-utc="<?= $log->created->format('c') ?>"></span>
                        </td>
                        <td>
                            <strong><?= h($log->recipient) ?></strong>
                        </td>
                        <td>
                            <?php if (isset($log->monitor)): ?>
                                <span><?= h($log->monitor->name) ?></span>
                                <?php if (isset($log->incident)): ?>
                                    <br><span style="color: #999; font-size: 12px;"><?= __('Incidente') ?> #<?= h($log->incident->id) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($log->status === 'sent'): ?>
                                <span class="badge badge-success"><?= __('Enviado') ?></span>
                                <?php if ($log->sent_at): ?>
                                    <br><span style="color: #999; font-size: 12px;">
                                        <span class="local-datetime" data-utc="<?= $log->sent_at->format('c') ?>"></span>
                                    </span>
                                <?php endif; ?>
                            <?php elseif ($log->status === 'failed'): ?>
                                <span class="badge badge-danger"><?= __('Falha') ?></span>
                            <?php elseif ($log->status === 'queued'): ?>
                                <span class="badge badge-warning"><?= __('Na Fila') ?></span>
                            <?php else: ?>
                                <span class="badge badge-info"><?= h(ucfirst($log->status)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($log->monitor)): ?>
                                <?= $this->Html->link(
                                    h($log->monitor->name),
                                    ['controller' => 'Monitors', 'action' => 'view', $log->monitor->id],
                                    ['style' => 'color: #3b82f6; text-decoration: none; font-weight: 500;']
                                ) ?>
                                <br>
                                <span style="color: #999; font-size: 12px;">
                                    <?= h(strtoupper($log->monitor->type)) ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-buttons">
                                <?= $this->Html->link(
                                    __('Ver'),
                                    ['action' => 'view', $log->id],
                                    ['class' => 'btn-action btn-action-view', 'title' => __('Ver detalhes')]
                                ) ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-emails">
            <p style="font-size: 18px; margin-bottom: 8px;">📭 <?= __('Nenhum email encontrado') ?></p>
            <p><?= __('Tente ajustar os filtros ou aguarde o envio de novos emails.') ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($emailLogs->count() > 0): ?>
    <div class="pagination">
        <?= $this->Paginator->first(__('« Primeira')) ?>
        <?= $this->Paginator->prev(__('‹ Anterior')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('Próxima ›')) ?>
        <?= $this->Paginator->last(__('Última »')) ?>
    </div>
    <div class="pagination-info">
        <?= $this->Paginator->counter(__('Página {{page}} de {{pages}}, mostrando {{current}} registro(s) de {{count}} no total')) ?>
    </div>
<?php endif; ?>
