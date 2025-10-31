<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Incident $incident
 * @var array $timeline
 * @var \Cake\Collection\CollectionInterface $recentChecks
 */
$this->assign('title', 'Detalhes do Incidente');
?>

<div class="incident-view">
    <div class="page-header">
        <div>
            <h1>🚨 <?= h($incident->title) ?></h1>
            <p>Detalhes e histórico do incidente</p>
        </div>
        <div class="header-actions">
            <?= $this->Html->link('← Voltar', ['action' => 'index'], ['class' => 'btn btn-secondary']) ?>
            <?php if (!$incident->isResolved()): ?>
                <?= $this->Html->link('✏️ Editar', ['action' => 'edit', $incident->id], ['class' => 'btn btn-primary']) ?>
                <?= $this->Form->postLink(
                    '✅ Resolver',
                    ['action' => 'resolve', $incident->id'],
                    [
                        'class' => 'btn btn-success',
                        'confirm' => __('Tem certeza que deseja resolver este incidente?')
                    ]
                ) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="incident-details-grid">
        <!-- Main Information Card -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Informações Principais</h3>
            </div>
            <div class="card-body">
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="badge badge-<?= $incident->isResolved() ? 'success' : 'danger' ?> badge-lg">
                                <?= $incident->isResolved() ? '✅' : '⚠️' ?>
                                <?= h($incident->getStatusName()) ?>
                            </span>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Severidade</div>
                        <div class="detail-value">
                            <span class="badge badge-<?= $incident->getSeverityBadgeClass() ?> badge-lg">
                                <?= h(ucfirst($incident->severity)) ?>
                            </span>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Monitor Afetado</div>
                        <div class="detail-value">
                            <?= $this->Html->link(
                                h($incident->monitor->name),
                                ['controller' => 'Monitors', 'action' => 'view', $incident->monitor->id],
                                ['class' => 'monitor-link']
                            ) ?>
                            <div class="monitor-target"><?= h($incident->monitor->target) ?></div>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Criação</div>
                        <div class="detail-value">
                            <?= $incident->auto_created ? '🤖 Auto-criado' : '👤 Manual' ?>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-label">Iniciado em</div>
                        <div class="detail-value">
                            <?= h($incident->started_at->i18nFormat('dd/MM/yyyy HH:mm:ss')) ?>
                            <div class="time-ago">(<?= h($incident->started_at->timeAgoInWords()) ?>)</div>
                        </div>
                    </div>

                    <?php if ($incident->identified_at): ?>
                        <div class="detail-item">
                            <div class="detail-label">Identificado em</div>
                            <div class="detail-value">
                                <?= h($incident->identified_at->i18nFormat('dd/MM/yyyy HH:mm:ss')) ?>
                                <div class="time-ago">(<?= h($incident->identified_at->timeAgoInWords()) ?>)</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($incident->resolved_at): ?>
                        <div class="detail-item">
                            <div class="detail-label">Resolvido em</div>
                            <div class="detail-value">
                                <?= h($incident->resolved_at->i18nFormat('dd/MM/yyyy HH:mm:ss')) ?>
                                <div class="time-ago">(<?= h($incident->resolved_at->timeAgoInWords()) ?>)</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="detail-item">
                        <div class="detail-label">Duração</div>
                        <div class="detail-value">
                            <?php if ($incident->duration !== null): ?>
                                <strong><?php
                                $duration = $incident->duration;
                                if ($duration < 60) {
                                    echo "{$duration} segundos";
                                } elseif ($duration < 3600) {
                                    $minutes = floor($duration / 60);
                                    $seconds = $duration % 60;
                                    echo $seconds > 0 ? "{$minutes}m {$seconds}s" : "{$minutes} minutos";
                                } else {
                                    $hours = floor($duration / 3600);
                                    $minutes = floor(($duration % 3600) / 60);
                                    if ($hours < 24) {
                                        echo $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours} horas";
                                    } else {
                                        $days = floor($hours / 24);
                                        $remainingHours = $hours % 24;
                                        echo $remainingHours > 0 ? "{$days}d {$remainingHours}h" : "{$days} dias";
                                    }
                                }
                                ?></strong>
                            <?php else: ?>
                                <span class="text-muted">
                                    <?= $incident->isResolved() ? 'N/A' : '⏱️ Em andamento...' ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($incident->description): ?>
                    <div class="description-section">
                        <div class="detail-label">Descrição</div>
                        <div class="description-box">
                            <?= h($incident->description) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timeline Card -->
        <div class="card">
            <div class="card-header">
                <h3>⏱️ Timeline de Eventos</h3>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($timeline as $event): ?>
                        <div class="timeline-item timeline-<?= h($event['color']) ?>">
                            <div class="timeline-marker">
                                <span class="timeline-icon"><?= $event['icon'] ?></span>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-time">
                                    <?= h($event['timestamp']->i18nFormat('dd/MM/yyyy HH:mm:ss')) ?>
                                    <span class="time-ago">(<?= h($event['timestamp']->timeAgoInWords()) ?>)</span>
                                </div>
                                <div class="timeline-title"><?= h($event['title']) ?></div>
                                <div class="timeline-description"><?= h($event['description']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Monitor Checks -->
    <?php if ($recentChecks->count() > 0): ?>
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h3>📊 Verificações Recentes do Monitor</h3>
            </div>
            <div class="card-body">
                <div class="checks-grid">
                    <?php foreach ($recentChecks as $check): ?>
                        <div class="check-item check-<?= h($check->status) ?>">
                            <div class="check-status">
                                <?= $check->status === 'success' ? '✅' : '❌' ?>
                            </div>
                            <div class="check-details">
                                <div class="check-time">
                                    <?= h($check->checked_at->i18nFormat('HH:mm:ss')) ?>
                                </div>
                                <?php if ($check->response_time): ?>
                                    <div class="check-response-time">
                                        <?= number_format($check->response_time) ?>ms
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.incident-details-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-top: 24px;
}

@media (max-width: 992px) {
    .incident-details-grid {
        grid-template-columns: 1fr;
    }
}

.header-actions {
    display: flex;
    gap: 12px;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

@media (max-width: 768px) {
    .detail-grid {
        grid-template-columns: 1fr;
    }
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.detail-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-gray-dark);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 16px;
    color: var(--color-dark);
}

.monitor-link {
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 600;
    font-size: 18px;
}

.monitor-link:hover {
    text-decoration: underline;
}

.monitor-target {
    font-size: 14px;
    color: var(--color-gray-medium);
    margin-top: 4px;
    font-family: 'Courier New', monospace;
}

.time-ago {
    font-size: 14px;
    color: var(--color-gray-medium);
    margin-top: 4px;
}

.badge-lg {
    font-size: 16px;
    padding: 8px 16px;
}

.description-section {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid var(--color-gray-light);
}

.description-box {
    background-color: var(--color-gray-light);
    padding: 16px;
    border-radius: 8px;
    margin-top: 8px;
    white-space: pre-wrap;
    font-size: 15px;
    line-height: 1.6;
}

/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: var(--color-gray-light);
}

.timeline-item {
    position: relative;
    padding-bottom: 32px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -40px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border: 3px solid var(--color-gray-medium);
}

.timeline-danger .timeline-marker {
    border-color: var(--color-error);
    background-color: #fff5f5;
}

.timeline-warning .timeline-marker {
    border-color: var(--color-warning);
    background-color: #fffbf0;
}

.timeline-success .timeline-marker {
    border-color: var(--color-success);
    background-color: #f0f9f4;
}

.timeline-icon {
    font-size: 16px;
}

.timeline-content {
    background-color: white;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid var(--color-gray-light);
}

.timeline-time {
    font-size: 13px;
    color: var(--color-gray-medium);
    margin-bottom: 8px;
}

.timeline-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-dark);
    margin-bottom: 4px;
}

.timeline-description {
    font-size: 14px;
    color: var(--color-gray-dark);
}

/* Checks Grid */
.checks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 12px;
}

.check-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px;
    border-radius: 8px;
    border: 2px solid var(--color-gray-light);
    background-color: white;
}

.check-item.check-success {
    border-color: #d4edda;
    background-color: #f0f9f4;
}

.check-item.check-failure {
    border-color: #f8d7da;
    background-color: #fff5f5;
}

.check-status {
    font-size: 24px;
    margin-bottom: 8px;
}

.check-details {
    text-align: center;
}

.check-time {
    font-size: 13px;
    font-weight: 600;
    color: var(--color-dark);
}

.check-response-time {
    font-size: 12px;
    color: var(--color-gray-medium);
    margin-top: 4px;
}
</style>
