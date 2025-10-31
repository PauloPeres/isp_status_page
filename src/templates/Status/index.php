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
                <?= $this->element('status/monitor_card', ['monitor' => $monitor]) ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p>Nenhum serviço sendo monitorado no momento.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Incidents -->
    <?= $this->element('status/incident_timeline', ['incidents' => $recentIncidents]) ?>

    <!-- Subscribe Section -->
    <?= $this->element('status/subscribe_form') ?>

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
