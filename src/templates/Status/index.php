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
        
        <?php if (!empty($logoUrl)): ?>
            <img src="<?= h($logoUrl) ?>" alt="<?= h($siteName) ?>" class="header-custom-logo">
        <?php else: ?>
            <h1><?= h($statusPageTitle) ?></h1>
        <?php endif; ?>
        
        
    </div>

    <!-- System Status Banner -->
    <div class="status-banner <?= $systemStatus ?>">
        <div class="status-icon"><?= $systemIcon ?></div>
        <h2 class="status-title"><?= h($systemMessage) ?></h2>
        <p class="status-description">
            <?= $onlineMonitors ?> <?= __('of') ?> <?= $totalMonitors ?> <?= __('services operational') ?>
            <?php if ($offlineMonitors > 0): ?>
                | <?= $offlineMonitors ?> <?= __('offline') ?>
            <?php endif; ?>
            <?php if ($degradedMonitors > 0): ?>
                | <?= $degradedMonitors ?> <?= __('degraded') ?>
            <?php endif; ?>
        </p>
    </div>

    <!-- Services List -->
    <div class="services-section">
        <h3 class="section-title">📊 <?= __('Service Status') ?></h3>

        <?php if ($monitors->count() > 0): ?>
            <?php foreach ($monitors as $monitor): ?>
                <?= $this->element('status/monitor_card', ['monitor' => $monitor, 'monitorsUptimeData' => $monitorsUptimeData]) ?>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <p><?= __('No services being monitored at this time.') ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Scheduled Maintenance (P3-014) -->
    <?php if (isset($maintenanceWindows) && $maintenanceWindows->count() > 0): ?>
        <div class="services-section">
            <h3 class="section-title"><?= __('Scheduled Maintenance') ?></h3>
            <?php foreach ($maintenanceWindows as $mw): ?>
                <div style="background: #fff; border: 1px solid #e0e0e0; border-left: 4px solid #FDD835; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <strong><?= h($mw->title) ?></strong>
                            <?php if ($mw->description): ?>
                                <p style="color: #666; font-size: 14px; margin: 4px 0 0;"><?= h($mw->description) ?></p>
                            <?php endif; ?>
                        </div>
                        <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase; background: <?= $mw->status === 'in_progress' ? '#FEF3C7' : '#DBEAFE' ?>; color: <?= $mw->status === 'in_progress' ? '#92400E' : '#1D4ED8' ?>;">
                            <?= h(ucfirst(str_replace('_', ' ', $mw->status))) ?>
                        </span>
                    </div>
                    <div style="margin-top: 8px; font-size: 13px; color: #999;">
                        <?= h($mw->starts_at->nice()) ?>
                        <?php if ($mw->ends_at): ?>
                            &mdash; <?= h($mw->ends_at->nice()) ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Recent Incidents -->
    <?= $this->element('status/incident_timeline', ['incidents' => $recentIncidents]) ?>

    <!-- Subscribe Section -->
    <?= $this->element('status/subscribe_form') ?>

</div>

<!-- Auto-reload Indicator -->
<div class="auto-reload-indicator">
    <span id="reload-message"><?= __('Next update in:') ?> <strong id="countdown">30</strong> <?= __('seconds') ?></span>
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
// Auto-refresh page every 30 seconds
const RELOAD_INTERVAL = 30; // seconds
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
    console.log('<?= __('Last update:') ?> ' + new Date().toLocaleString());
    console.log('<?= __('Next update in 30 seconds') ?>');

    // Smooth scroll to subscribe form
    const subscribeLink = document.querySelector('a[href="#subscribe-form"]');
    if (subscribeLink) {
        subscribeLink.addEventListener('click', function(e) {
            e.preventDefault();
            const subscribeForm = document.getElementById('subscribe-form');
            if (subscribeForm) {
                subscribeForm.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    }
});
</script>
