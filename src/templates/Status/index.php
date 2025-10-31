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
 * @var string $siteName
 * @var string $statusPageTitle
 */
$this->assign('title', $statusPageTitle);
?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header-status">
        <h1><?= h($statusPageTitle) ?></h1>
        <p class="site-name"><?= h($siteName) ?></p>
    </div>

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
                                <?php if ($monitor->last_check_at): ?>
                                    <p class="service-last-check">
                                        Última verificação: <span class="local-datetime" data-utc="<?= $monitor->last_check_at->format('c') ?>"></span>
                                        (<span class="time-ago" data-utc="<?= $monitor->last_check_at->format('c') ?>"></span>)
                                    </p>
                                <?php else: ?>
                                    <p class="service-last-check">
                                        Aguardando primeira verificação
                                    </p>
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

<!-- Auto-reload Indicator -->
<div class="auto-reload-indicator">
    <span id="reload-message">Próxima atualização em: <strong id="countdown">300</strong> segundos</span>
</div>

<style>
.auto-reload-indicator {
    position: fixed;
    top: 16px;
    left: 16px;
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    font-size: 12px;
    color: #666;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

#countdown {
    color: var(--color-primary);
    font-weight: 600;
}
</style>

<script>
// Auto-refresh page every 5 minutes (300 seconds)
const RELOAD_INTERVAL = 300; // seconds
let secondsRemaining = RELOAD_INTERVAL;
let countdownInterval;

// Function to update countdown display
function updateCountdown() {
    const countdownElement = document.getElementById('countdown');
    if (countdownElement) {
        countdownElement.textContent = secondsRemaining;
    }

    secondsRemaining--;

    if (secondsRemaining < 0) {
        clearInterval(countdownInterval);
        location.reload();
    }
}

// Start countdown when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Dates are converted automatically by datetime-utils.js

    // Update countdown every second
    countdownInterval = setInterval(updateCountdown, 1000);

    // Show last update time in console
    console.log('Última atualização: ' + new Date().toLocaleString('pt-BR'));
    console.log('Próxima atualização em 5 minutos');
});
</script>
