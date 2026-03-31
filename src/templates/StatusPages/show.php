<?php
/**
 * Public Status Page - Show
 *
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\StatusPage $statusPage
 * @var array $monitors
 * @var array $incidents
 * @var array $maintenanceWindows
 * @var array $timeline
 * @var array $theme
 * @var bool $requirePassword
 * @var string $overallStatus
 * @var string $overallStatusText
 * @var array $uptimeHistory
 * @var bool $showUptimeChart
 * @var bool $showIncidentHistory
 */
// i18n translations for the status page
$lang = $statusPage->language ?? 'en';
$t = [
    'en' => [
        'all_operational' => 'All Systems Operational',
        'some_down' => 'Some Systems Are Down',
        'some_degraded' => 'Some Systems Are Degraded',
        'no_monitors' => 'No monitors configured',
        'subscribe' => 'Subscribe to Updates',
        'subscribe_placeholder' => 'your@email.com',
        'subscribe_btn' => 'Subscribe',
        'subscribed' => 'Subscribed! You will receive incident updates.',
        'subscribe_fail' => 'Failed to subscribe. Please try again.',
        'scheduled_maintenance' => 'Scheduled Maintenance',
        'recent_incidents' => 'Recent Incidents',
        'past_incidents' => 'Past Incidents',
        'no_incidents' => 'No incidents reported.',
        'last_updated' => 'Last updated',
        'password_title' => 'This status page is password protected',
        'password_prompt' => 'Please enter the password to view this page.',
        'password_btn' => 'View Status Page',
        'powered_by' => 'Powered by',
    ],
    'pt_BR' => [
        'all_operational' => 'Todos os Sistemas Operacionais',
        'some_down' => 'Alguns Sistemas Estão Fora do Ar',
        'some_degraded' => 'Alguns Sistemas Estão Degradados',
        'no_monitors' => 'Nenhum monitor configurado',
        'subscribe' => 'Inscrever-se para Atualizações',
        'subscribe_placeholder' => 'seu@email.com',
        'subscribe_btn' => 'Inscrever',
        'subscribed' => 'Inscrito! Você receberá atualizações de incidentes.',
        'subscribe_fail' => 'Falha ao inscrever. Tente novamente.',
        'scheduled_maintenance' => 'Manutenção Programada',
        'recent_incidents' => 'Incidentes Recentes',
        'past_incidents' => 'Incidentes Anteriores',
        'no_incidents' => 'Nenhum incidente reportado.',
        'last_updated' => 'Última atualização',
        'password_title' => 'Esta página de status é protegida por senha',
        'password_prompt' => 'Por favor, insira a senha para visualizar.',
        'password_btn' => 'Ver Página de Status',
        'powered_by' => 'Desenvolvido por',
    ],
    'es' => [
        'all_operational' => 'Todos los Sistemas Operativos',
        'some_down' => 'Algunos Sistemas Están Caídos',
        'some_degraded' => 'Algunos Sistemas Están Degradados',
        'no_monitors' => 'No hay monitores configurados',
        'subscribe' => 'Suscribirse a Actualizaciones',
        'subscribe_placeholder' => 'tu@email.com',
        'subscribe_btn' => 'Suscribirse',
        'subscribed' => '¡Suscrito! Recibirás actualizaciones de incidentes.',
        'subscribe_fail' => 'Error al suscribirse. Inténtalo de nuevo.',
        'scheduled_maintenance' => 'Mantenimiento Programado',
        'recent_incidents' => 'Incidentes Recientes',
        'past_incidents' => 'Incidentes Anteriores',
        'no_incidents' => 'No se reportaron incidentes.',
        'last_updated' => 'Última actualización',
        'password_title' => 'Esta página de estado está protegida por contraseña',
        'password_prompt' => 'Ingrese la contraseña para ver esta página.',
        'password_btn' => 'Ver Página de Estado',
        'powered_by' => 'Desarrollado por',
    ],
];
$i = $t[$lang] ?? $t['en'];

// Override overall status text with translation
if ($overallStatus === 'up') $overallStatusText = $i['all_operational'];
elseif ($overallStatus === 'down') $overallStatusText = $i['some_down'];
elseif ($overallStatus === 'degraded') $overallStatusText = $i['some_degraded'];
elseif ($overallStatus === 'unknown') $overallStatusText = $i['no_monitors'];

$this->assign('title', h($statusPage->name));
$this->assign('pageTitle', $statusPage->name);
$this->assign('footerText', $statusPage->footer_text ?? '');
$this->assign('slug', $statusPage->slug);
$this->assign('poweredBy', $i['powered_by']);

// Pass theme to layout
if (!empty($theme['primary_color'])) {
    $this->assign('primaryColor', $theme['primary_color']);
}
if (!empty($theme['logo_url'])) {
    $this->assign('logoUrl', $theme['logo_url']);
}
if (!empty($theme['custom_css'])) {
    $this->assign('customCss', $theme['custom_css']);
}
?>

<?php if ($requirePassword): ?>
    <div class="sp-password-card">
        <h2><?= $i['password_title'] ?></h2>
        <p><?= $i['password_prompt'] ?></p>
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

    <!-- Subscribe Button -->
    <div class="sp-subscribe-row">
        <button class="sp-subscribe-btn" onclick="document.getElementById('sp-subscribe-form').style.display = document.getElementById('sp-subscribe-form').style.display === 'none' ? 'block' : 'none'">
            <?= $i['subscribe'] ?>
        </button>
    </div>
    <div id="sp-subscribe-form" class="sp-subscribe-form" style="display: none">
        <form onsubmit="return spSubscribe(event)">
            <input type="email" id="sp-subscribe-email" placeholder="<?= $i['subscribe_placeholder'] ?>" required>
            <button type="submit"><?= $i['subscribe_btn'] ?></button>
        </form>
        <p id="sp-subscribe-msg" class="sp-subscribe-msg"></p>
    </div>

    <!-- Overall Status Banner -->
    <div class="sp-overall sp-overall-<?= h($overallStatus) ?>">
        <h2><?= $overallStatusText ?></h2>
        <p class="sp-last-updated">Last updated: <?= date('H:i:s') ?></p>
    </div>

    <!-- Upcoming Maintenance -->
    <?php if (!empty($maintenanceWindows)): ?>
    <div class="sp-maintenance">
        <h3><?= $i['scheduled_maintenance'] ?></h3>
        <?php foreach ($maintenanceWindows as $mw): ?>
            <div class="sp-maintenance-item">
                <div class="sp-maintenance-title"><?= h($mw->title) ?></div>
                <?php if (!empty($mw->description)): ?>
                    <p class="sp-maintenance-desc"><?= h($mw->description) ?></p>
                <?php endif; ?>
                <div class="sp-maintenance-time">
                    <?php if ($mw->is_recurring && !empty($mw->recurrence_time_start)): ?>
                        Recurring: <?= h($mw->recurrence_pattern ?? 'weekly') ?> <?= h($mw->recurrence_time_start) ?> - <?= h($mw->recurrence_time_end) ?>
                    <?php else: ?>
                        <?= $mw->starts_at->format('M j, H:i') ?> &mdash; <?= $mw->ends_at->format('M j, H:i') ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

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

    <!-- 14-Day Timeline -->
    <?php if ($showIncidentHistory && !empty($timeline)): ?>
    <div class="sp-timeline">
        <h3><?= $i['past_incidents'] ?></h3>
        <?php foreach ($timeline as $date => $entries): ?>
            <div class="sp-timeline-day">
                <div class="sp-timeline-date"><?= date('l, F j, Y', strtotime($date)) ?></div>
                <?php if (empty($entries)): ?>
                    <p class="sp-timeline-none"><?= $i['no_incidents'] ?></p>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <div class="sp-timeline-entry">
                            <div class="sp-timeline-entry-header">
                                <span class="sp-badge sp-badge-<?= h($entry['severity']) ?>"><?= h($entry['severity']) ?></span>
                                <strong><?= h($entry['title']) ?></strong>
                                <span class="sp-incident-status sp-status-<?= h($entry['status']) ?>"><?= h(ucfirst($entry['status'])) ?></span>
                            </div>
                            <?php if (!empty($entry['description'])): ?>
                                <p class="sp-timeline-desc"><?= h($entry['description']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($entry['monitor_name'])): ?>
                                <span class="sp-timeline-monitor"><?= h($entry['monitor_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($entry['updates'])): ?>
                                <div class="sp-incident-updates">
                                    <?php foreach ($entry['updates'] as $update): ?>
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
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php endif; ?>

<script>
function spSubscribe(e) {
    e.preventDefault();
    var email = document.getElementById('sp-subscribe-email').value;
    var msg = document.getElementById('sp-subscribe-msg');
    fetch('/api/v2/public/status/<?= h($statusPage->slug) ?>/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email })
    }).then(r => r.json()).then(d => {
        if (d.success) {
            msg.textContent = '<?= addslashes($i['subscribed']) ?>';
            msg.style.color = '#065F46';
            document.getElementById('sp-subscribe-email').value = '';
        } else {
            msg.textContent = d.message || '<?= addslashes($i['subscribe_fail']) ?>';
            msg.style.color = '#991B1B';
        }
    }).catch(() => {
        msg.textContent = '<?= addslashes($i['subscribe_fail']) ?>';
        msg.style.color = '#991B1B';
    });
    return false;
}
</script>
