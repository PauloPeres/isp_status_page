<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $incidents
 * @var array $stats
 * @var array $monitors
 */
$this->assign('title', __d('incidents', 'Incidentes'));
?>

<style>
    .incidents-header {
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
    .stat-value.warning { color: #f59e0b; }

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
        margin-bottom: 16px;
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

    .filter-group input,
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
        text-decoration: none;
        display: inline-block;
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

    .btn-clear:hover {
        background: #f8f9fa;
    }

    .incidents-table {
        width: 100%;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }

    .incidents-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .incidents-table th {
        background: #f8f9fa;
        padding: 12px 16px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        border-bottom: 2px solid #e0e0e0;
    }

    .incidents-table th a {
        color: #666;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .incidents-table th a:hover {
        color: #3b82f6;
    }

    .incidents-table th a::after {
        content: '⇅';
        opacity: 0.3;
        font-size: 12px;
    }

    .incidents-table th a.asc::after {
        content: '↑';
        opacity: 1;
        color: #3b82f6;
    }

    .incidents-table th a.desc::after {
        content: '↓';
        opacity: 1;
        color: #3b82f6;
    }

    .incidents-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
        vertical-align: top;
    }

    .incidents-table tr:last-child td {
        border-bottom: none;
    }

    .incidents-table tbody tr:hover {
        background: #f8f9fa;
    }

    .incidents-table tbody tr.resolved {
        opacity: 0.65;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
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
        color: #1d4ed8;
    }

    .badge-secondary {
        background: #f3f4f6;
        color: #6b7280;
    }

    .incident-title {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .incident-description {
        font-size: 13px;
        color: #666;
        margin-top: 4px;
        max-width: 400px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .monitor-link {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
    }

    .monitor-link:hover {
        text-decoration: underline;
    }

    .action-buttons {
        display: flex;
        gap: 4px;
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

    .btn-action-edit {
        background: #f59e0b;
        color: white;
    }

    .btn-action-edit:hover {
        background: #d97706;
    }

    .btn-action-resolve {
        background: #22c55e;
        color: white;
    }

    .btn-action-resolve:hover {
        background: #16a34a;
    }

    .no-incidents {
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

        .incidents-table {
            overflow-x: auto;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="incidents-header">
    <h2>🚨 <?= __d('incidents', 'Incidentes') ?></h2>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('incidents', 'Total') ?></div>
        <div class="stat-value info"><?= number_format($stats['total']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('incidents', 'Ativos') ?></div>
        <div class="stat-value error"><?= number_format($stats['active']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('incidents', 'Resolvidos') ?></div>
        <div class="stat-value success"><?= number_format($stats['resolved']) ?></div>
    </div>
    <div class="stat-card-mini">
        <div class="stat-label"><?= __d('incidents', 'Críticos') ?></div>
        <div class="stat-value warning"><?= number_format($stats['critical']) ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filters-card">
    <?= $this->Form->create(null, ['type' => 'get', 'id' => 'filters-form']) ?>
    <div class="filters-row">
        <div class="filter-group">
            <label><?= __d('incidents', 'Buscar') ?></label>
            <?= $this->Form->control('search', [
                'label' => false,
                'placeholder' => __d('incidents', 'Título ou descrição...'),
                'value' => $this->request->getQuery('search'),
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('incidents', 'Status') ?></label>
            <?= $this->Form->control('status', [
                'label' => false,
                'options' => [
                    '' => __d('incidents', 'Todos'),
                    'active' => __d('incidents', 'Ativos'),
                    'investigating' => __d('incidents', 'Investigando'),
                    'identified' => __d('incidents', 'Identificado'),
                    'monitoring' => __d('incidents', 'Monitorando'),
                    'resolved' => __d('incidents', 'Resolvido'),
                ],
                'value' => $this->request->getQuery('status'),
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('incidents', 'Severidade') ?></label>
            <?= $this->Form->control('severity', [
                'label' => false,
                'options' => [
                    '' => __d('incidents', 'Todas'),
                    'critical' => __d('incidents', 'Crítica'),
                    'major' => __d('incidents', 'Major'),
                    'minor' => __d('incidents', 'Minor'),
                    'maintenance' => __d('incidents', 'Manutenção'),
                ],
                'value' => $this->request->getQuery('severity'),
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>

        <div class="filter-group">
            <label><?= __d('incidents', 'Monitor') ?></label>
            <?= $this->Form->control('monitor_id', [
                'label' => false,
                'options' => ['' => __d('incidents', 'Todos os Monitores')] + $monitors,
                'value' => $this->request->getQuery('monitor_id'),
                'empty' => false,
                'class' => 'form-control',
            ]) ?>
        </div>
    </div>

    <div class="filter-buttons">
        <?= $this->Form->button(__d('incidents', 'Filtrar'), ['type' => 'submit', 'class' => 'btn-filter']) ?>
        <?= $this->Html->link(__d('incidents', 'Limpar'), ['action' => 'index'], ['class' => 'btn-clear']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>

<!-- Incidents Table -->
<div class="incidents-table">
    <?php if ($incidents->count() > 0): ?>
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('status', __d('incidents', 'Status')) ?></th>
                    <th><?= $this->Paginator->sort('title', __d('incidents', 'Incidente')) ?></th>
                    <th><?= $this->Paginator->sort('monitor_id', __d('incidents', 'Monitor')) ?></th>
                    <th><?= $this->Paginator->sort('severity', __d('incidents', 'Severidade')) ?></th>
                    <th><?= $this->Paginator->sort('started_at', __d('incidents', 'Iniciado')) ?></th>
                    <th><?= $this->Paginator->sort('duration', __d('incidents', 'Duração')) ?></th>
                    <th style="text-align: right;"><?= __d('incidents', 'Ações') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($incidents as $incident): ?>
                    <tr class="<?= $incident->isResolved() ? 'resolved' : '' ?>">
                        <td>
                            <span class="badge badge-<?= $incident->isResolved() ? 'success' : 'danger' ?>">
                                <?= $incident->isResolved() ? '✅' : '⚠️' ?>
                                <?= h($incident->getStatusName()) ?>
                            </span>
                        </td>
                        <td>
                            <div class="incident-title">
                                <?= h($incident->title) ?>
                                <?php if ($incident->auto_created): ?>
                                    <span class="badge badge-secondary" title="Auto-criado">🤖</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($incident->description): ?>
                                <div class="incident-description" title="<?= h($incident->description) ?>">
                                    <?= h($incident->description) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $this->Html->link(
                                h($incident->monitor->name),
                                ['controller' => 'Monitors', 'action' => 'view', $incident->monitor->id],
                                ['class' => 'monitor-link']
                            ) ?>
                            <br>
                            <span style="color: #999; font-size: 12px;">
                                <?= h(strtoupper($incident->monitor->type)) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?= $incident->getSeverityBadgeClass() ?>">
                                <?= h(ucfirst($incident->severity)) ?>
                            </span>
                        </td>
                        <td>
                            <span class="local-datetime" data-utc="<?= $incident->started_at->format('c') ?>"></span>
                        </td>
                        <td>
                            <?php if ($incident->duration !== null): ?>
                                <span style="font-family: 'Courier New', monospace; color: #666;">
                                    <?php
                                    $duration = $incident->duration;
                                    if ($duration < 60) {
                                        echo "{$duration}s";
                                    } elseif ($duration < 3600) {
                                        $minutes = floor($duration / 60);
                                        echo "{$minutes}m";
                                    } else {
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        echo $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
                                    }
                                    ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #999; font-size: 13px;">
                                    <?= $incident->isResolved() ? __d('incidents', 'N/A') : __d('incidents', 'Em andamento') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <div class="action-buttons">
                                <?= $this->Html->link(
                                    __d('incidents', 'Ver'),
                                    ['action' => 'view', $incident->id],
                                    ['class' => 'btn-action btn-action-view', 'title' => __d('incidents', 'Ver detalhes')]
                                ) ?>
                                <?php if (!$incident->isResolved()): ?>
                                    <?= $this->Html->link(
                                        __d('incidents', 'Editar'),
                                        ['action' => 'edit', $incident->id],
                                        ['class' => 'btn-action btn-action-edit', 'title' => __d('incidents', 'Editar')]
                                    ) ?>
                                    <?= $this->Form->postLink(
                                        __d('incidents', 'Resolver'),
                                        ['action' => 'resolve', $incident->id],
                                        [
                                            'class' => 'btn-action btn-action-resolve',
                                            'title' => __d('incidents', 'Resolver'),
                                            'confirm' => __d('incidents', 'Tem certeza que deseja resolver este incidente?')
                                        ]
                                    ) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-incidents">
            <p style="font-size: 18px; margin-bottom: 8px;">📭 <?= __d('incidents', 'Nenhum incidente encontrado') ?></p>
            <p><?= __d('incidents', 'Tente ajustar os filtros ou aguarde novos incidentes.') ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($incidents->count() > 0): ?>
    <div class="pagination">
        <?= $this->Paginator->first(__d('incidents', '« Primeira')) ?>
        <?= $this->Paginator->prev(__d('incidents', '‹ Anterior')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__d('incidents', 'Próxima ›')) ?>
        <?= $this->Paginator->last(__d('incidents', 'Última »')) ?>
    </div>
    <div class="pagination-info">
        <?= $this->Paginator->counter(__d('incidents', 'Página {{page}} de {{pages}}, mostrando {{current}} registro(s) de {{count}} no total')) ?>
    </div>
<?php endif; ?>
