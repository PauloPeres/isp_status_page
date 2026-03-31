<?php
/**
 * Public Status Page - Show
 *
 * Uses the status_page layout with new CSS classes.
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\StatusPage $statusPage
 * @var array $monitors
 * @var array $incidents
 * @var bool $requirePassword
 * @var string $overallStatus
 * @var string $overallStatusText
 * @var array $uptimeHistory
 * @var bool $showUptimeChart
 * @var bool $showIncidentHistory
 */
$this->assign('title', h($statusPage->name));
$this->assign('pageTitle', $statusPage->name);
$this->assign('footerText', $statusPage->footer_text ?? '');
$this->assign('slug', $statusPage->slug);
?>

<?php if ($requirePassword): ?>
    <div class="sp-password-card">
        <h2><?= __('This status page is password protected') ?></h2>
        <p><?= __('Please enter the password to view this page.') ?></p>
        <?= $this->Form->create(null, ['url' => ['action' => 'show', $statusPage->slug]]) ?>
        <div>
            <?= $this->Form->password('password', [
                'placeholder' => __('Enter password'),
                'required' => true,
                'autofocus' => true,
            ]) ?>
        </div>
        <div>
            <?= $this->Form->button(__('View Status Page'), []) ?>
        </div>
        <?= $this->Form->end() ?>
    </div>
<?php else: ?>

    <?php if ($statusPage->header_text): ?>
        <p class="sp-header-text"><?= h($statusPage->header_text) ?></p>
    <?php endif; ?>

    <!-- Overall Status Banner -->
    <div class="sp-overall sp-overall-<?= h($overallStatus) ?>">
        <h2><?= $overallStatusText ?></h2>
        <p class="sp-last-updated">Last updated: <?= date('H:i:s') ?></p>
    </div>

    <!-- Monitors -->
    <div class="sp-monitors" id="sp-monitors">
        <?php foreach ($monitors as $monitor): ?>
            <div class="sp-monitor" data-monitor-id="<?= $monitor->id ?>">
                <div class="sp-dot sp-dot-<?= h($monitor->status ?? 'unknown') ?>"></div>
                <span class="sp-monitor-name"><?= h($monitor->name) ?></span>
                <span class="sp-monitor-uptime"><?= number_format($monitor->uptime_percentage ?? 0, 1) ?>%</span>
            </div>
            <?php if ($showUptimeChart && !empty($uptimeHistory[$monitor->id])): ?>
                <div class="sp-uptime-bar">
                    <?php foreach ($uptimeHistory[$monitor->id] as $day): ?>
                        <div class="sp-uptime-day sp-day-<?= $day['status'] ?>" title="<?= $day['date'] ?>: <?= $day['uptime'] ?>%"></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Incidents -->
    <?php if ($showIncidentHistory && !empty($incidents)): ?>
    <div class="sp-incidents">
        <h3><?= __('Recent Incidents') ?></h3>
        <?php foreach ($incidents as $incident): ?>
            <div class="sp-incident">
                <div class="sp-incident-title">
                    <?php
                    $severity = $incident->severity ?? 'minor';
                    $incidentStatus = $incident->status ?? 'open';
                    ?>
                    <span class="sp-badge sp-badge-<?= h($severity) ?>"><?= h($severity) ?></span>
                    <?= h($incident->title) ?>
                    <span class="sp-incident-status sp-status-<?= h($incidentStatus) ?>"><?= h(ucfirst($incidentStatus)) ?></span>
                </div>
                <?php if (!empty($incident->description)): ?>
                    <p class="sp-incident-desc"><?= h($incident->description) ?></p>
                <?php endif; ?>
                <div class="sp-incident-meta">
                    <?= $incident->created->format('M j, Y H:i') ?>
                    <?php if (!empty($incident->monitor)): ?>
                        &mdash; <?= h($incident->monitor->name) ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($incident->incident_updates)): ?>
                    <div class="sp-incident-updates">
                        <?php foreach ($incident->incident_updates as $update): ?>
                            <div class="sp-update">
                                <div class="sp-update-header">
                                    <span class="sp-update-status"><?= h(ucfirst($update->status ?? 'update')) ?></span>
                                    <span class="sp-update-time"><?= $update->created->format('M j, H:i') ?></span>
                                </div>
                                <p class="sp-update-message"><?= h($update->message) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php elseif ($showIncidentHistory): ?>
    <div class="sp-incidents">
        <h3><?= __('Recent Incidents') ?></h3>
        <p style="color: #94A3B8; font-size: 0.9rem;"><?= __('No incidents reported.') ?></p>
    </div>
    <?php endif; ?>

<?php endif; ?>
