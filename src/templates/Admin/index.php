<?php
/**
 * @var \App\View\AppView $this
 * @var array $stats
 * @var \Cake\Collection\CollectionInterface $recentMonitors
 * @var \Cake\Collection\CollectionInterface $recentIncidents
 */
$this->assign('title', 'Dashboard');
?>

<div class="admin-dashboard">
    <div class="page-header">
        <h1>📊 Dashboard</h1>
        <p>Visão geral do sistema de monitoramento</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #E3F2FD;">
                <span style="font-size: 32px;">🖥️</span>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['monitors']['total']) ?></h3>
                <p>Monitores Total</p>
                <div class="stat-details">
                    <span class="badge badge-success"><?= $stats['monitors']['online'] ?> Online</span>
                    <span class="badge badge-error"><?= $stats['monitors']['offline'] ?> Offline</span>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #FFEBEE;">
                <span style="font-size: 32px;">🚨</span>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['incidents']['active']) ?></h3>
                <p>Incidentes Ativos</p>
                <div class="stat-details">
                    <span class="badge badge-success"><?= $stats['incidents']['resolved_today'] ?> Resolvidos Hoje</span>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #E8F5E9;">
                <span style="font-size: 32px;">📧</span>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['subscribers']['total']) ?></h3>
                <p>Inscritos Total</p>
                <div class="stat-details">
                    <span class="badge badge-success"><?= $stats['subscribers']['active'] ?> Ativos</span>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #FFF3E0;">
                <span style="font-size: 32px;">📈</span>
            </div>
            <div class="stat-content">
                <h3><?= number_format($stats['checks']['total_today']) ?></h3>
                <p>Verificações Hoje</p>
                <div class="stat-details">
                    <span class="badge badge-error"><?= $stats['checks']['failed_today'] ?> Falhas</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">
        <!-- Recent Monitors -->
        <div class="card">
            <div class="card-header">
                <span>🖥️ Monitores Recentes</span>
                <?= $this->Html->link('Ver Todos →', ['controller' => 'Monitors', 'action' => 'index'], ['class' => 'card-link']) ?>
            </div>
            <div class="card-body">
                <?php if ($recentMonitors->count() > 0): ?>
                    <div class="monitor-list">
                        <?php foreach ($recentMonitors as $monitor): ?>
                            <div class="monitor-item">
                                <div class="monitor-info">
                                    <span class="status-indicator status-<?= h($monitor->status) ?>"></span>
                                    <div>
                                        <strong><?= h($monitor->name) ?></strong>
                                        <small><?= h($monitor->type) ?> - <?= h($monitor->target) ?></small>
                                    </div>
                                </div>
                                <span class="badge badge-<?= $monitor->status === 'up' ? 'success' : 'error' ?>">
                                    <?= $monitor->status === 'up' ? 'Online' : 'Offline' ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Nenhum monitor cadastrado ainda.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Incidents -->
        <div class="card">
            <div class="card-header">
                <span>🚨 Incidentes Recentes</span>
                <?= $this->Html->link('Ver Todos →', ['controller' => 'Incidents', 'action' => 'index'], ['class' => 'card-link']) ?>
            </div>
            <div class="card-body">
                <?php if ($recentIncidents->count() > 0): ?>
                    <div class="incident-list">
                        <?php foreach ($recentIncidents as $incident): ?>
                            <div class="incident-item">
                                <div class="incident-info">
                                    <strong><?= h($incident->title) ?></strong>
                                    <small><?= $incident->created->timeAgoInWords() ?></small>
                                </div>
                                <span class="badge badge-<?= $incident->status === 'resolved' ? 'success' : 'warning' ?>">
                                    <?= h($incident->status) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Nenhum incidente registrado ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">⚡ Ações Rápidas</div>
        <div class="card-body">
            <div class="quick-actions">
                <?= $this->Html->link(
                    '➕ Novo Monitor',
                    ['controller' => 'Monitors', 'action' => 'add'],
                    ['class' => 'btn btn-primary']
                ) ?>
                <?= $this->Html->link(
                    '⚙️ Configurações',
                    ['controller' => 'Settings', 'action' => 'index'],
                    ['class' => 'btn btn-secondary']
                ) ?>
                <?= $this->Html->link(
                    '🌐 Ver Página Pública',
                    ['controller' => 'Status', 'action' => 'index'],
                    ['class' => 'btn btn-secondary', 'target' => '_blank']
                ) ?>
            </div>
        </div>
    </div>
</div>

<style>
.page-header {
    margin-bottom: 32px;
}

.page-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: var(--color-dark);
    margin-bottom: 8px;
}

.page-header p {
    color: var(--color-gray-medium);
    font-size: 15px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--color-white);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow-md);
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.stat-icon {
    width: 64px;
    height: 64px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-content h3 {
    font-size: 32px;
    font-weight: 700;
    color: var(--color-dark);
    margin-bottom: 4px;
}

.stat-content p {
    color: var(--color-gray-medium);
    font-size: 14px;
    margin-bottom: 12px;
}

.stat-details {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-link {
    font-size: 14px;
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 600;
}

.card-link:hover {
    color: var(--color-primary-hover);
}

.card-body {
    padding: 0;
}

.monitor-list, .incident-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.monitor-item, .incident-item {
    padding: 16px;
    border-radius: var(--radius-md);
    background: var(--color-gray-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.monitor-info, .incident-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.monitor-info div, .incident-info {
    display: flex;
    flex-direction: column;
}

.monitor-info strong, .incident-info strong {
    font-size: 15px;
    color: var(--color-dark);
}

.monitor-info small, .incident-info small {
    font-size: 13px;
    color: var(--color-gray-medium);
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.status-indicator.status-up {
    background: var(--color-success);
    animation: pulse 2s infinite;
}

.status-indicator.status-down {
    background: var(--color-error);
}

.empty-state {
    padding: 24px;
    text-align: center;
    color: var(--color-gray-medium);
    font-style: italic;
}

.quick-actions {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .content-grid {
        grid-template-columns: 1fr;
    }

    .quick-actions {
        flex-direction: column;
    }

    .quick-actions .btn {
        width: 100%;
    }
}
</style>
