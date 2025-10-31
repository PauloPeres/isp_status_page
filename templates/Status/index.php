<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\Collection\CollectionInterface $monitors
 * @var string $systemStatus
 * @var string $systemMessage
 * @var string $systemIcon
 * @var int $totalMonitors
 * @var int $onlineMonitors
 * @var int $offlineMonitors
 * @var int $degradedMonitors
 * @var \Cake\Collection\CollectionInterface $recentIncidents
 */
$this->assign('title', 'Status dos Serviços');
?>

<div class="container">
    <!-- System Status Banner -->
    <div class="status-banner <?= $systemStatus ?>">
        <div class="status-icon"><?= $systemIcon ?></div>
        <h2 class="status-title"><?= h($systemMessage) ?></h2>
        <p class="status-description">
            <?= $onlineMonitors ?> de <?= $totalMonitors ?> serviços operacionais
            <?php if ($offlineMonitors > 0): ?>
                | <?= $offlineMonitors ?> offline
            <?php endif; ?>
            <?php if ($degradedMonitors > 0): ?>
                | <?= $degradedMonitors ?> degradados
            <?php endif; ?>
        </p>
    </div>

    <!-- Services List -->
    <div class="services-section">
        <h3 class="section-title">📊 Status dos Serviços</h3>

        <?php if ($monitors->count() > 0): ?>
            <?php foreach ($monitors as $monitor): ?>
                <div class="service-card">
                    <div class="service-header">
                        <div class="service-info">
                            <span class="service-status-indicator <?= h($monitor->status) ?>"></span>
                            <div>
                                <h4 class="service-name"><?= h($monitor->name) ?></h4>
                                <?php if ($monitor->description): ?>
                                    <p class="service-description"><?= h($monitor->description) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="service-status-badge <?= $monitor->status === 'up' ? 'operational' : ($monitor->status === 'down' ? 'down' : 'degraded') ?>">
                            <?php
                            if ($monitor->status === 'up') {
                                echo 'Operacional';
                            } elseif ($monitor->status === 'down') {
                                echo 'Offline';
                            } else {
                                echo 'Degradado';
                            }
                            ?>
                        </span>
                    </div>

                    <div class="service-details">
                        <div class="service-detail-item">
                            <span>🔍</span>
                            <span><?= h(ucfirst($monitor->type)) ?></span>
                        </div>
                        <?php if ($monitor->last_check): ?>
                            <div class="service-detail-item">
                                <span>🕐</span>
                                <span>Última verificação: <?= $monitor->last_check->timeAgoInWords() ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($monitor->response_time): ?>
                            <div class="service-detail-item">
                                <span>⚡</span>
                                <span><?= number_format($monitor->response_time) ?>ms</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>Nenhum serviço sendo monitorado no momento.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Incidents -->
    <?php if ($recentIncidents->count() > 0): ?>
        <div class="incidents-section">
            <h3 class="section-title">🚨 Incidentes Recentes</h3>

            <?php foreach ($recentIncidents as $incident): ?>
                <div class="incident-card <?= h($incident->status) ?>">
                    <div class="incident-header">
                        <h4 class="incident-title"><?= h($incident->title) ?></h4>
                        <span class="incident-status <?= h($incident->status) ?>">
                            <?= h($incident->status) ?>
                        </span>
                    </div>

                    <div class="incident-body">
                        <?= nl2br(h($incident->description)) ?>
                    </div>

                    <div class="incident-meta">
                        📅 <?= $incident->created->format('d/m/Y H:i') ?>
                        <?php if ($incident->resolved_at): ?>
                            | ✅ Resolvido em <?= $incident->resolved_at->format('d/m/Y H:i') ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="text-align: center; margin-top: 24px;">
                <?= $this->Html->link(
                    'Ver Histórico Completo →',
                    ['action' => 'history'],
                    ['class' => 'btn btn-secondary', 'style' => 'display: inline-block; padding: 12px 24px; text-decoration: none; background: var(--color-gray-light); color: var(--color-dark); border-radius: var(--radius-md); transition: all 0.3s ease;']
                ) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Subscribe Section -->
    <div class="subscribe-section">
        <h3 class="subscribe-title">📧 Receba Notificações</h3>
        <p class="subscribe-description">
            Inscreva-se para receber atualizações por email sobre incidentes e manutenções programadas.
        </p>

        <?= $this->Form->create(null, [
            'url' => ['controller' => 'Subscribers', 'action' => 'subscribe'],
            'class' => 'subscribe-form'
        ]) ?>
            <?= $this->Form->control('email', [
                'type' => 'email',
                'placeholder' => 'seu@email.com',
                'required' => true,
                'label' => false,
                'class' => 'subscribe-input'
            ]) ?>
            <button type="submit" class="subscribe-button">
                Inscrever-se
            </button>
        <?= $this->Form->end() ?>
    </div>
</div>

<script>
// Auto-refresh page every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);

// Show last update time
console.log('Última atualização: ' + new Date().toLocaleString('pt-BR'));
</script>
