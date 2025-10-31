<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $monitors
 * @var array $stats
 */
$this->assign('title', 'Monitores');
?>

<div class="monitors-index">
    <div class="page-header">
        <div>
            <h1>🖥️ Monitores</h1>
            <p>Gerencie todos os serviços monitorados</p>
        </div>
        <?= $this->Html->link(
            '➕ Novo Monitor',
            ['action' => 'add'],
            ['class' => 'btn btn-primary']
        ) ?>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid" style="margin-bottom: 32px;">
        <div class="stat-card-mini">
            <div class="stat-label">Total</div>
            <div class="stat-value"><?= number_format($stats['total']) ?></div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label">Ativos</div>
            <div class="stat-value success"><?= number_format($stats['active']) ?></div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label">Online</div>
            <div class="stat-value success"><?= number_format($stats['online']) ?></div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label">Offline</div>
            <div class="stat-value error"><?= number_format($stats['offline']) ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom: 24px;">
        <?= $this->Form->create(null, ['type' => 'get', 'class' => 'filter-form']) ?>
        <div class="filter-grid">
            <?= $this->Form->control('search', [
                'label' => 'Buscar',
                'placeholder' => 'Nome, alvo ou descrição...',
                'value' => $this->request->getQuery('search'),
            ]) ?>

            <?= $this->Form->control('type', [
                'label' => 'Tipo',
                'options' => [
                    '' => 'Todos',
                    'http' => 'HTTP',
                    'ping' => 'Ping',
                    'port' => 'Port',
                ],
                'value' => $this->request->getQuery('type'),
                'empty' => false,
            ]) ?>

            <?= $this->Form->control('status', [
                'label' => 'Status',
                'options' => [
                    '' => 'Todos',
                    'up' => 'Online',
                    'down' => 'Offline',
                    'unknown' => 'Desconhecido',
                ],
                'value' => $this->request->getQuery('status'),
                'empty' => false,
            ]) ?>

            <?= $this->Form->control('active', [
                'label' => 'Estado',
                'options' => [
                    '' => 'Todos',
                    '1' => 'Ativos',
                    '0' => 'Inativos',
                ],
                'value' => $this->request->getQuery('active'),
                'empty' => false,
            ]) ?>
        </div>

        <div class="filter-actions">
            <?= $this->Form->button('🔍 Filtrar', ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link(
                '🔄 Limpar',
                ['action' => 'index'],
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>

    <!-- Monitors Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Alvo</th>
                        <th>Última Verificação</th>
                        <th>Tempo de Resposta</th>
                        <th>Estado</th>
                        <th class="actions">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($monitors->count() > 0): ?>
                        <?php foreach ($monitors as $monitor): ?>
                            <tr>
                                <td>
                                    <span class="status-indicator status-<?= h($monitor->status) ?>"
                                          title="<?= h($monitor->status) ?>">
                                    </span>
                                </td>
                                <td>
                                    <strong><?= h($monitor->name) ?></strong>
                                    <?php if ($monitor->description): ?>
                                        <br><small class="text-muted"><?= h($monitor->description) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= h(strtoupper($monitor->type)) ?></span>
                                </td>
                                <td>
                                    <code><?= h($monitor->target) ?></code>
                                </td>
                                <td>
                                    <?php if ($monitor->last_check): ?>
                                        <?= $monitor->last_check->timeAgoInWords() ?>
                                    <?php else: ?>
                                        <span class="text-muted">Nunca</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($monitor->response_time): ?>
                                        <?= number_format($monitor->response_time) ?> ms
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($monitor->active): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #B0BEC5;">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <?= $this->Html->link('👁️', ['action' => 'view', $monitor->id], [
                                        'title' => 'Ver',
                                        'class' => 'action-btn'
                                    ]) ?>
                                    <?= $this->Html->link('✏️', ['action' => 'edit', $monitor->id], [
                                        'title' => 'Editar',
                                        'class' => 'action-btn'
                                    ]) ?>
                                    <?= $this->Form->postLink(
                                        $monitor->active ? '⏸️' : '▶️',
                                        ['action' => 'toggle', $monitor->id],
                                        [
                                            'title' => $monitor->active ? 'Desativar' : 'Ativar',
                                            'class' => 'action-btn',
                                            'confirm' => 'Tem certeza que deseja ' . ($monitor->active ? 'desativar' : 'ativar') . ' este monitor?'
                                        ]
                                    ) ?>
                                    <?= $this->Form->postLink('🗑️', ['action' => 'delete', $monitor->id], [
                                        'title' => 'Excluir',
                                        'class' => 'action-btn action-btn-danger',
                                        'confirm' => 'Tem certeza que deseja excluir este monitor? Esta ação não pode ser desfeita.'
                                    ]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <p>Nenhum monitor encontrado.</p>
                                <?= $this->Html->link(
                                    'Criar Primeiro Monitor',
                                    ['action' => 'add'],
                                    ['class' => 'btn btn-primary']
                                ) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($monitors->count() > 0): ?>
            <div class="pagination-wrapper">
                <div class="pagination">
                    <?= $this->Paginator->prev('‹ Anterior') ?>
                    <?= $this->Paginator->numbers() ?>
                    <?= $this->Paginator->next('Próximo ›') ?>
                </div>
                <div class="pagination-info">
                    <?= $this->Paginator->counter('Página {{page}} de {{pages}}, exibindo {{current}} de {{count}} monitores') ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 32px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
}

.stat-card-mini {
    background: var(--color-white);
    padding: 16px;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    text-align: center;
}

.stat-label {
    font-size: 13px;
    color: var(--color-gray-medium);
    margin-bottom: 8px;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--color-dark);
}

.stat-value.success {
    color: var(--color-success);
}

.stat-value.error {
    color: var(--color-error);
}

.filter-form {
    padding: 0;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.filter-actions {
    display: flex;
    gap: 8px;
}

.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
}

.table thead {
    background: var(--color-gray-light);
}

.table th,
.table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--color-gray-light);
}

.table th {
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    color: var(--color-gray-medium);
}

.table tbody tr:hover {
    background: var(--color-gray-light);
}

.table .actions {
    text-align: right;
    white-space: nowrap;
}

.action-btn {
    display: inline-block;
    padding: 4px 8px;
    margin: 0 2px;
    text-decoration: none;
    border-radius: var(--radius-sm);
    transition: all 0.2s ease;
}

.action-btn:hover {
    background: var(--color-gray-light);
    transform: scale(1.1);
}

.action-btn-danger:hover {
    background: var(--color-error);
    color: var(--color-white);
}

.text-muted {
    color: var(--color-gray-medium);
    font-size: 14px;
}

.pagination-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-top: 1px solid var(--color-gray-light);
}

.pagination {
    display: flex;
    gap: 8px;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    border-radius: var(--radius-sm);
    text-decoration: none;
    color: var(--color-dark);
}

.pagination a:hover {
    background: var(--color-gray-light);
}

.pagination .active {
    background: var(--color-primary);
    color: var(--color-white);
    font-weight: 600;
}

.pagination-info {
    font-size: 14px;
    color: var(--color-gray-medium);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 16px;
    }

    .filter-grid {
        grid-template-columns: 1fr;
    }

    .pagination-wrapper {
        flex-direction: column;
        gap: 16px;
    }
}
</style>
