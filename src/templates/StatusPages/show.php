<?php
/**
 * Public Status Page - Show
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\StatusPage $statusPage
 * @var array $monitors
 * @var array $incidents
 * @var bool $requirePassword
 */
$this->assign('title', h($statusPage->name));
?>

<?php
// Apply custom branding from theme JSON (P3-011)
$themeConfig = $statusPage->getThemeConfig();
$brandPrimaryColor = $themeConfig['primary_color'] ?? null;
$brandLogoUrl = $themeConfig['logo_url'] ?? null;
$brandCustomCss = $themeConfig['custom_css'] ?? null;
?>

<?php if ($brandPrimaryColor): ?>
<style>
.public-status-page .status-page-header h1 { color: <?= h($brandPrimaryColor) ?>; }
.public-status-page .btn-primary { background: <?= h($brandPrimaryColor) ?>; border-color: <?= h($brandPrimaryColor) ?>; }
.public-status-page .monitors-list h2,
.public-status-page .incidents-section h2 { color: <?= h($brandPrimaryColor) ?>; }
</style>
<?php endif; ?>

<?php if ($brandCustomCss): ?>
<style><?= $brandCustomCss ?></style>
<?php endif; ?>

<div class="public-status-page">
    <?php if ($brandLogoUrl): ?>
        <div class="status-page-header" style="text-align: center;">
            <img src="<?= h($brandLogoUrl) ?>" alt="<?= h($statusPage->name) ?>" style="max-height: 60px; margin-bottom: 12px;">
            <?php if (!empty($statusPage->header_text)): ?>
                <div><?= $statusPage->header_text ?></div>
            <?php endif; ?>
        </div>
    <?php elseif (!empty($statusPage->header_text)): ?>
        <div class="status-page-header">
            <?= $statusPage->header_text ?>
        </div>
    <?php else: ?>
        <div class="status-page-header">
            <h1><?= h($statusPage->name) ?></h1>
        </div>
    <?php endif; ?>

    <?php if ($requirePassword): ?>
        <div class="card password-card">
            <h2><?= __('This status page is password protected') ?></h2>
            <p><?= __('Please enter the password to view this page.') ?></p>
            <?= $this->Form->create(null, ['url' => ['action' => 'show', $statusPage->slug]]) ?>
            <div class="form-group">
                <?= $this->Form->password('password', [
                    'class' => 'form-control',
                    'placeholder' => __('Enter password'),
                    'required' => true,
                    'autofocus' => true,
                ]) ?>
            </div>
            <div class="form-actions">
                <?= $this->Form->button(__('View Status Page'), ['class' => 'btn btn-primary']) ?>
            </div>
            <?= $this->Form->end() ?>
        </div>
    <?php else: ?>
        <?php
        // Calculate overall status
        $allUp = true;
        $anyDown = false;
        foreach ($monitors as $monitor) {
            if ($monitor->status === 'down') {
                $anyDown = true;
                $allUp = false;
            } elseif ($monitor->status !== 'up') {
                $allUp = false;
            }
        }

        if (empty($monitors)) {
            $overallStatus = 'unknown';
            $overallLabel = __('No monitors configured');
            $overallClass = 'status-unknown';
        } elseif ($allUp) {
            $overallStatus = 'up';
            $overallLabel = __('All Systems Operational');
            $overallClass = 'status-up';
        } elseif ($anyDown) {
            $overallStatus = 'down';
            $overallLabel = __('Some Systems Are Down');
            $overallClass = 'status-down';
        } else {
            $overallStatus = 'degraded';
            $overallLabel = __('Some Systems Are Degraded');
            $overallClass = 'status-degraded';
        }
        ?>

        <div class="overall-status <?= $overallClass ?>">
            <span class="overall-status-text"><?= $overallLabel ?></span>
        </div>

        <?php if (!empty($monitors)): ?>
            <div class="card monitors-list">
                <h2><?= __('Services') ?></h2>
                <?php foreach ($monitors as $monitor): ?>
                    <div class="monitor-row">
                        <div class="monitor-info">
                            <span class="monitor-name"><?= h($monitor->name) ?></span>
                            <?php if (!empty($monitor->description)): ?>
                                <span class="monitor-description"><?= h($monitor->description) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="monitor-status">
                            <?php
                            $badgeClass = match ($monitor->status) {
                                'up' => 'badge-success',
                                'down' => 'badge-error',
                                'degraded' => 'badge-warning',
                                default => 'badge-secondary',
                            };
                            $statusLabel = match ($monitor->status) {
                                'up' => __('Operational'),
                                'down' => __('Down'),
                                'degraded' => __('Degraded'),
                                default => __('Unknown'),
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                            <?php if ($statusPage->show_uptime_chart && $monitor->uptime_percentage !== null): ?>
                                <span class="uptime-value"><?= number_format($monitor->uptime_percentage, 2) ?>%</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($statusPage->show_incident_history && !empty($incidents)): ?>
            <div class="card incidents-section">
                <h2><?= __('Recent Incidents') ?></h2>
                <?php foreach ($incidents as $incident): ?>
                    <div class="incident-row">
                        <div class="incident-info">
                            <strong><?= h($incident->title) ?></strong>
                            <?php if (!empty($incident->description)): ?>
                                <p class="incident-description"><?= h($incident->description) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="incident-meta">
                            <?php
                            $incidentBadge = match ($incident->status ?? 'open') {
                                'resolved' => 'badge-success',
                                'investigating' => 'badge-warning',
                                'identified' => 'badge-warning',
                                default => 'badge-error',
                            };
                            ?>
                            <span class="badge <?= $incidentBadge ?>"><?= h(ucfirst($incident->status ?? 'open')) ?></span>
                            <span class="incident-date"><?= h($incident->created->format('M j, Y H:i')) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($statusPage->show_incident_history): ?>
            <div class="card incidents-section">
                <h2><?= __('Recent Incidents') ?></h2>
                <p class="no-incidents"><?= __('No incidents reported.') ?></p>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <?php if (!empty($statusPage->footer_text)): ?>
        <div class="status-page-footer">
            <?= $statusPage->footer_text ?>
        </div>
    <?php endif; ?>
</div>

<style>
.public-status-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 24px 16px;
}

.status-page-header {
    text-align: center;
    margin-bottom: 24px;
}

.status-page-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: var(--color-dark, #1a1a2e);
}

.overall-status {
    text-align: center;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 18px;
    font-weight: 600;
}

.overall-status.status-up {
    background: #E8F5E9;
    color: #2E7D32;
    border: 1px solid #43A047;
}

.overall-status.status-down {
    background: #FFEBEE;
    color: #C62828;
    border: 1px solid #E53935;
}

.overall-status.status-degraded {
    background: #FFF8E1;
    color: #F57F17;
    border: 1px solid #FDD835;
}

.overall-status.status-unknown {
    background: #F5F5F5;
    color: #757575;
    border: 1px solid #BDBDBD;
}

.monitors-list h2,
.incidents-section h2 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 16px;
    color: var(--color-dark, #1a1a2e);
}

.monitor-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #eee;
}

.monitor-row:last-child {
    border-bottom: none;
}

.monitor-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.monitor-name {
    font-weight: 500;
    color: var(--color-dark, #1a1a2e);
}

.monitor-description {
    font-size: 13px;
    color: #666;
}

.monitor-status {
    display: flex;
    align-items: center;
    gap: 8px;
}

.uptime-value {
    font-size: 13px;
    color: #666;
}

.incident-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.incident-row:last-child {
    border-bottom: none;
}

.incident-description {
    font-size: 13px;
    color: #666;
    margin: 4px 0 0 0;
}

.incident-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

.incident-date {
    font-size: 12px;
    color: #999;
}

.no-incidents {
    color: #666;
    font-style: italic;
}

.password-card {
    max-width: 400px;
    margin: 40px auto;
    text-align: center;
}

.password-card h2 {
    font-size: 20px;
    margin-bottom: 8px;
}

.password-card p {
    color: #666;
    margin-bottom: 20px;
}

.status-page-footer {
    text-align: center;
    margin-top: 32px;
    padding-top: 16px;
    border-top: 1px solid #eee;
    color: #666;
    font-size: 14px;
}

@media (max-width: 768px) {
    .monitor-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .incident-row {
        flex-direction: column;
        gap: 8px;
    }

    .incident-meta {
        align-items: flex-start;
    }
}
</style>
