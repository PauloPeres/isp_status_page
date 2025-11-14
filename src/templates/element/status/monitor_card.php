<?php
/**
 * Monitor Card Element
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Monitor $monitor
 */
?>

<div class="service-card">
    <div class="service-header">
        <div class="service-info">
            <span class="service-status-indicator <?= h($monitor->status) ?>"></span>
            <div class="service-content">
                <h4 class="service-name"><?= h($monitor->name) ?></h4>
                <?php if ($monitor->description): ?>
                    <p class="service-description"><?= h($monitor->description) ?></p>
                <?php endif; ?>
                <?php if ($monitor->last_check_at): ?>
                    <p class="service-last-check">
                        <?= __('Última verificação:') ?> <span class="local-datetime" data-utc="<?= $monitor->last_check_at->format('c') ?>"></span>
                        (<span class="time-ago" data-utc="<?= $monitor->last_check_at->format('c') ?>"></span>)
                    </p>
                <?php else: ?>
                    <p class="service-last-check">
                        <?= __('Aguardando primeira verificação') ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <div class="service-badges">
            <?php if ($monitor->uptime_percentage !== null): ?>
                <div class="uptime-badge">
                    <div class="uptime-percentage <?= $monitor->uptime_percentage >= 99 ? 'excellent' : ($monitor->uptime_percentage >= 95 ? 'good' : 'poor') ?>">
                        <?= number_format($monitor->uptime_percentage, 2) ?>%
                    </div>
                    <div class="uptime-label">Uptime</div>
                </div>
            <?php endif; ?>
            <span class="service-status-badge <?= $monitor->status === 'up' ? 'operational' : ($monitor->status === 'down' ? 'down' : 'degraded') ?>">
                <?php
                if ($monitor->status === 'up') {
                    echo __('Operacional');
                } elseif ($monitor->status === 'down') {
                    echo __('Offline');
                } else {
                    echo __('Degradado');
                }
                ?>
            </span>
        </div>
    </div>

    <div class="service-details">
        <div class="service-detail-item">
            <span class="detail-icon">🔍</span>
            <span class="detail-text"><?= h(ucfirst($monitor->type)) ?></span>
        </div>
        <?php if ($monitor->response_time): ?>
            <div class="service-detail-item">
                <span class="detail-icon">⚡</span>
                <span class="detail-text response-time <?= $monitor->response_time > 1000 ? 'slow' : ($monitor->response_time > 500 ? 'medium' : 'fast') ?>">
                    <?= number_format($monitor->response_time) ?>ms
                </span>
            </div>
        <?php endif; ?>
        <?php if ($monitor->target): ?>
            <div class="service-detail-item">
                <span class="detail-icon">🎯</span>
                <span class="detail-text target"><?= h($monitor->target) ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>
