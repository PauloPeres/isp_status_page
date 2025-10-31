<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $incidents
 * @var array $stats
 * @var array $monitors
 */
$this->assign('title', 'Incidentes');
?>

<div class="incidents-index">
    <div class="page-header">
        <div>
            <h1>🚨 Incidentes</h1>
            <p>Visualize e gerencie todos os incidentes</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid" style="margin-bottom: 32px;">
        <div class="stat-card-mini">
            <div class="stat-label">Total</div>
            <div class="stat-value"><?= number_format($stats['total']) ?></div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label">Ativos</div>
            <div class="stat-value error"><?= number_format($stats['active']) ?></div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label">Resolvidos</div>
            <div class="stat-value success"><?= number_format($stats['resolved']) ?></div>
        </div>
        <div class="stat-card-mini">
            <div class="stat-label">Críticos</div>
            <div class="stat-value error"><?= number_format($stats['critical']) ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom: 24px;">
        <?= $this->Form->create(null, ['type' => 'get', 'class' => 'filter-form']) ?>
        <div class="filter-grid">
            <?= $this->Form->control('search', [
                'label' => 'Buscar',
                'placeholder' => 'Título ou descrição...',
                'value' => $this->request->getQuery('search'),
            ]) ?>

            <?= $this->Form->control('status', [
                'label' => 'Status',
                'options' => [
                    '' => 'Todos',
                    'active' => 'Ativos',
                    'investigating' => 'Investigando',
                    'identified' => 'Identificado',
                    'monitoring' => 'Monitorando',
                    'resolved' => 'Resolvido',
                ],
                'value' => $this->request->getQuery('status'),
                'empty' => false,
            ]) ?>

            <?= $this->Form->control('severity', [
                'label' => 'Severidade',
                'options' => [
                    '' => 'Todas',
                    'critical' => 'Crítica',
                    'major' => 'Major',
                    'minor' => 'Minor',
                    'maintenance' => 'Manutenção',
                ],
                'value' => $this->request->getQuery('severity'),
                'empty' => false,
            ]) ?>

            <?= $this->Form->control('monitor_id', [
                'label' => 'Monitor',
                'options' => ['' => 'Todos'] + $monitors,
                'value' => $this->request->getQuery('monitor_id'),
                'empty' => false,
            ]) ?>
        </div>

        <div class="filter-actions">
            <?= $this->Form->button('🔍 Filtrar', ['class' => 'btn btn-primary']) ?>
            <?= $this->Html->link('🔄 Limpar', ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>

    <!-- Incidents List -->
    <div class="card">
        <div class="table-responsive">
            <table class="table incidents-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Incidente</th>
                        <th>Monitor</th>
                        <th>Severidade</th>
                        <th>Iniciado</th>
                        <th>Duração</th>
                        <th class="actions">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($incidents->count() > 0): ?>
                        <?php foreach ($incidents as $incident): ?>
                            <tr class="incident-row <?= $incident->isResolved() ? 'resolved' : 'active' ?>">
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
                                        <div class="incident-description">
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
                                </td>
                                <td>
                                    <span class="badge badge-<?= $incident->getSeverityBadgeClass() ?>">
                                        <?= h(ucfirst($incident->severity)) ?>
                                    </span>
                                </td>
                                <td>
                                    <time datetime="<?= h($incident->started_at->toIso8601String()) ?>" title="<?= h($incident->started_at->i18nFormat('dd/MM/yyyy HH:mm:ss')) ?>">
                                        <?= h($incident->started_at->timeAgoInWords()) ?>
                                    </time>
                                </td>
                                <td>
                                    <?php if ($incident->duration !== null): ?>
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
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <?= $incident->isResolved() ? 'N/A' : 'Em andamento' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <?= $this->Html->link(
                                        '👁️',
                                        ['action' => 'view', $incident->id],
                                        ['class' => 'btn btn-sm btn-secondary', 'title' => 'Ver detalhes']
                                    ) ?>
                                    <?php if (!$incident->isResolved()): ?>
                                        <?= $this->Html->link(
                                            '✏️',
                                            ['action' => 'edit', $incident->id],
                                            ['class' => 'btn btn-sm btn-primary', 'title' => 'Editar']
                                        ) ?>
                                        <?= $this->Form->postLink(
                                            '✅',
                                            ['action' => 'resolve', $incident->id],
                                            [
                                                'class' => 'btn btn-sm btn-success',
                                                'title' => 'Resolver',
                                                'confirm' => __('Tem certeza que deseja resolver este incidente?')
                                            ]
                                        ) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted" style="padding: 48px;">
                                <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
                                <p style="font-size: 18px; margin-bottom: 8px;">Nenhum incidente encontrado</p>
                                <p style="font-size: 14px;">Tente ajustar os filtros ou aguarde novos incidentes</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($incidents->count() > 0): ?>
            <div class="pagination-wrapper">
                <div class="paginator">
                    <ul class="pagination">
                        <?= $this->Paginator->first('<< ' . __('First')) ?>
                        <?= $this->Paginator->prev('< ' . __('Previous')) ?>
                        <?= $this->Paginator->numbers() ?>
                        <?= $this->Paginator->next(__('Next') . ' >') ?>
                        <?= $this->Paginator->last(__('Last') . ' >>') ?>
                    </ul>
                    <p class="pagination-info">
                        <?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.incidents-table {
    width: 100%;
}

.incident-row {
    transition: background-color 0.2s;
}

.incident-row:hover {
    background-color: var(--color-gray-light);
}

.incident-row.resolved {
    opacity: 0.7;
}

.incident-title {
    font-weight: 600;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.incident-description {
    font-size: 14px;
    color: var(--color-gray-dark);
    margin-top: 4px;
    max-width: 400px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.monitor-link {
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 500;
}

.monitor-link:hover {
    text-decoration: underline;
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
    background-color: #d4edda;
    color: #155724;
}

.badge-danger {
    background-color: #f8d7da;
    color: #721c24;
}

.badge-warning {
    background-color: #fff3cd;
    color: #856404;
}

.badge-info {
    background-color: #d1ecf1;
    color: #0c5460;
}

.badge-secondary {
    background-color: #e2e3e5;
    color: #383d41;
}

.text-muted {
    color: var(--color-gray-medium);
}
</style>
