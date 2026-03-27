<?php
/**
 * @var \App\View\AppView $this
 * @var array $summary
 * @var \Cake\Collection\CollectionInterface $activeIncidents
 * @var array $incidentsBySeverity
 * @var array $uptimeData
 * @var array $responseTimeData
 * @var \Cake\Collection\CollectionInterface $recentChecks
 * @var \Cake\Collection\CollectionInterface $recentAlerts
 */
$this->assign('title', 'Dashboard');
?>

<style>
.dashboard-header {
    margin-bottom: 32px;
}
.dashboard-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
}
.dashboard-header p {
    color: #999;
    font-size: 14px;
}

/* Summary Cards */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.summary-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    text-align: center;
}
.summary-card .card-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 8px;
}
.summary-card .card-value {
    font-size: 28px;
    font-weight: 700;
}
.summary-card .card-value.total { color: #3b82f6; }
.summary-card .card-value.up { color: #22c55e; }
.summary-card .card-value.down { color: #ef4444; }
.summary-card .card-value.degraded { color: #f59e0b; }
.summary-card .card-value.unknown { color: #999; }

/* Incidents summary */
.incidents-summary {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.incidents-summary h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}
.severity-badges {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.severity-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}
.severity-badge.critical { background: #fee2e2; color: #dc2626; }
.severity-badge.major { background: #fef3c7; color: #d97706; }
.severity-badge.minor { background: #dbeafe; color: #1d4ed8; }
.severity-badge.maintenance { background: #f3f4f6; color: #6b7280; }

/* Charts */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}
.chart-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.chart-card h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 16px;
    color: #333;
}
.chart-wrapper {
    position: relative;
    width: 100%;
    max-height: 300px;
}

/* Tables */
.tables-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}
.table-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.table-card h3 {
    font-size: 16px;
    font-weight: 600;
    padding: 16px 20px;
    margin: 0;
    border-bottom: 1px solid #e0e0e0;
    color: #333;
}
.table-card table {
    width: 100%;
    border-collapse: collapse;
}
.table-card th {
    background: #f8f9fa;
    padding: 10px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    border-bottom: 2px solid #e0e0e0;
}
.table-card td {
    padding: 10px 16px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
    vertical-align: middle;
}
.table-card tr:last-child td {
    border-bottom: none;
}
.table-card tbody tr:hover {
    background: #f8f9fa;
}

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}
.badge-success { background: #dcfce7; color: #16a34a; }
.badge-danger { background: #fee2e2; color: #dc2626; }
.badge-warning { background: #fef3c7; color: #d97706; }
.badge-info { background: #dbeafe; color: #1d4ed8; }
.badge-secondary { background: #f3f4f6; color: #6b7280; }

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
    font-size: 14px;
}

@media (max-width: 768px) {
    .summary-grid { grid-template-columns: repeat(2, 1fr); }
    .charts-grid { grid-template-columns: 1fr; }
    .tables-grid { grid-template-columns: 1fr; }
}
</style>

<div class="dashboard-header">
    <h1>Dashboard</h1>
    <p>Visao geral do sistema de monitoramento</p>
</div>

<!-- Summary Cards -->
<div class="summary-grid">
    <div class="summary-card">
        <div class="card-label">Total Monitores</div>
        <div class="card-value total"><?= number_format($summary['total']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label">Online</div>
        <div class="card-value up"><?= number_format($summary['up']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label">Offline</div>
        <div class="card-value down"><?= number_format($summary['down']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label">Degradado</div>
        <div class="card-value degraded"><?= number_format($summary['degraded']) ?></div>
    </div>
    <div class="summary-card">
        <div class="card-label">Desconhecido</div>
        <div class="card-value unknown"><?= number_format($summary['unknown']) ?></div>
    </div>
</div>

<!-- Active Incidents -->
<div class="incidents-summary">
    <h3>Incidentes Ativos (<?= $activeIncidents->count() ?>)</h3>
    <div class="severity-badges">
        <?php if ($incidentsBySeverity['critical'] > 0): ?>
            <span class="severity-badge critical"><?= $incidentsBySeverity['critical'] ?> Critico</span>
        <?php endif; ?>
        <?php if ($incidentsBySeverity['major'] > 0): ?>
            <span class="severity-badge major"><?= $incidentsBySeverity['major'] ?> Major</span>
        <?php endif; ?>
        <?php if ($incidentsBySeverity['minor'] > 0): ?>
            <span class="severity-badge minor"><?= $incidentsBySeverity['minor'] ?> Minor</span>
        <?php endif; ?>
        <?php if ($incidentsBySeverity['maintenance'] > 0): ?>
            <span class="severity-badge maintenance"><?= $incidentsBySeverity['maintenance'] ?> Manutencao</span>
        <?php endif; ?>
        <?php if ($activeIncidents->count() === 0): ?>
            <span style="color: #22c55e; font-weight: 600;">Nenhum incidente ativo</span>
        <?php endif; ?>
    </div>
</div>

<!-- Charts -->
<div class="charts-grid">
    <div class="chart-card">
        <h3>Uptime (Ultimas 24h)</h3>
        <div class="chart-wrapper">
            <canvas id="uptimeChart"></canvas>
        </div>
    </div>
    <div class="chart-card">
        <h3>Tempo de Resposta Medio (ms)</h3>
        <div class="chart-wrapper">
            <canvas id="responseTimeChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Tables -->
<div class="tables-grid">
    <!-- Recent Checks -->
    <div class="table-card">
        <h3>Verificacoes Recentes</h3>
        <?php if ($recentChecks->count() > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Monitor</th>
                        <th>Status</th>
                        <th>Tempo (ms)</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentChecks as $check): ?>
                        <tr>
                            <td><?= h($check->monitor->name ?? 'N/A') ?></td>
                            <td>
                                <?php
                                    $badgeClass = match($check->status) {
                                        'success' => 'badge-success',
                                        'failure', 'error' => 'badge-danger',
                                        'timeout' => 'badge-warning',
                                        default => 'badge-secondary',
                                    };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= h($check->status) ?></span>
                            </td>
                            <td><?= $check->response_time !== null ? number_format($check->response_time) : '-' ?></td>
                            <td><?= $check->checked_at ? h($check->checked_at->format('d/m H:i:s')) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">Nenhuma verificacao recente.</div>
        <?php endif; ?>
    </div>

    <!-- Recent Alerts -->
    <div class="table-card">
        <h3>Alertas Recentes</h3>
        <?php if ($recentAlerts->count() > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Monitor</th>
                        <th>Canal</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentAlerts as $alert): ?>
                        <tr>
                            <td><?= h($alert->monitor->name ?? 'N/A') ?></td>
                            <td><?= h($alert->channel ?? '-') ?></td>
                            <td>
                                <?php
                                    $alertBadge = match($alert->status) {
                                        'sent' => 'badge-success',
                                        'failed' => 'badge-danger',
                                        'queued' => 'badge-warning',
                                        default => 'badge-secondary',
                                    };
                                ?>
                                <span class="badge <?= $alertBadge ?>"><?= h($alert->status) ?></span>
                            </td>
                            <td><?= $alert->created ? h($alert->created->format('d/m H:i:s')) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">Nenhum alerta recente.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Pass data to JS -->
<script>
    window.dashboardData = {
        uptime: <?= json_encode($uptimeData) ?>,
        responseTime: <?= json_encode($responseTimeData) ?>
    };
</script>
<script src="/js/charts.js"></script>
