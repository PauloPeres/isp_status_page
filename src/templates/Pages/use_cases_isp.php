<?php
/**
 * Use case: ISP monitoring
 *
 * @var \App\View\AppView $this
 */
$this->extend('/layout/marketing');

$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');

$this->assign('title', 'Uptime Monitoring for Internet Service Providers - ' . $brandName);
$this->assign('meta_description', 'KeepUp is built for ISPs: native IXC and Zabbix integrations, PPPoE monitoring, LGPD compliance, Portuguese support. Join 50+ ISPs already using KeepUp.');
$this->assign('og_title', 'Uptime Monitoring for ISPs - KeepUp');
$this->assign('og_url', 'https://usekeeup.com/use-cases/isp');
?>

<div class="mktg-hero">
    <h1>Uptime Monitoring for Internet Service Providers</h1>
    <p>Built by ISP engineers, for ISP engineers. <?= h($brandName) ?> integrates natively with the tools you already use &mdash; IXC, Zabbix, and more.</p>
</div>

<div class="mktg-section" style="padding-top: 0;">
    <div class="mktg-grid-2">
        <div>
            <h2 class="mktg-h2">Why ISPs Choose <?= h($brandName) ?></h2>
            <p class="mktg-text">Managing ISP infrastructure is fundamentally different from monitoring a web application. You need to track PPPoE sessions, ONU equipment status, bandwidth utilization, and core router health &mdash; all while keeping thousands of subscribers happy.</p>
            <p class="mktg-text"><?= h($brandName) ?> was born from this exact environment. Our team has spent years operating ISP networks in Brazil, and we built the platform to solve the problems we faced every day.</p>
        </div>
        <div>
            <h2 class="mktg-h2">ISP-Specific Features</h2>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 12px 0; border-bottom: 1px solid var(--color-gray-200);"><strong style="color: var(--color-brand-500);">IXC Integration</strong> &mdash; Monitor services and equipment directly from your IXC provisioning system. No manual configuration per subscriber.</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--color-gray-200);"><strong style="color: var(--color-brand-500);">Zabbix Integration</strong> &mdash; Pull host availability and trigger states into <?= h($brandName) ?>. One dashboard for everything.</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--color-gray-200);"><strong style="color: var(--color-brand-500);">PPPoE Monitoring</strong> &mdash; Track session status and detect drops before subscribers call your NOC.</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--color-gray-200);"><strong style="color: var(--color-brand-500);">ONU/Equipment Checks</strong> &mdash; Monitor CPE equipment by serial number or ID through IXC.</li>
                <li style="padding: 12px 0;"><strong style="color: var(--color-brand-500);">Multi-Region Checks</strong> &mdash; Verify connectivity from multiple points to isolate network vs. service issues.</li>
            </ul>
        </div>
    </div>
</div>

<div class="mktg-section mktg-section-alt" style="padding: 80px 24px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 class="mktg-h2" style="text-align: center; margin-bottom: 48px;">Built for the Brazilian Market</h2>

        <div class="mktg-grid-3">
            <div class="mktg-card" style="text-align: center;">
                <h3 class="mktg-h3">LGPD Compliant</h3>
                <p class="mktg-text">Your subscriber data is protected. <?= h($brandName) ?> is designed with Brazil's data protection law (LGPD) in mind, with proper data isolation and retention policies.</p>
            </div>
            <div class="mktg-card" style="text-align: center;">
                <h3 class="mktg-h3">Portuguese Support</h3>
                <p class="mktg-text">Native PT-BR interface, alerts, status pages, and email notifications. Your support team does not need to translate English alerts at 3 AM.</p>
            </div>
            <div class="mktg-card" style="text-align: center;">
                <h3 class="mktg-h3">ABRINT-Ready</h3>
                <p class="mktg-text">Designed to meet the operational standards expected by Brazilian ISP associations. Professional status pages that build subscriber trust.</p>
            </div>
        </div>
    </div>
</div>

<div class="mktg-section">
    <h2 class="mktg-h2" style="margin-bottom: 16px;">How ISPs Use <?= h($brandName) ?></h2>
    <p class="mktg-text" style="margin-bottom: 32px;">A typical ISP setup includes:</p>

    <div class="mktg-grid-2">
        <div class="mktg-card">
            <h3 class="mktg-h3">Core Infrastructure Monitoring</h3>
            <p class="mktg-text">HTTP checks on your web portal, ping checks on core routers and switches, port checks on critical services (DNS, RADIUS, DHCP). Get alerted the moment something goes down.</p>
        </div>
        <div class="mktg-card">
            <h3 class="mktg-h3">IXC Service Monitoring</h3>
            <p class="mktg-text">Connect your IXC instance and monitor provisioned services automatically. Track service status, equipment health, and detect outages before your subscribers notice.</p>
        </div>
        <div class="mktg-card">
            <h3 class="mktg-h3">Zabbix Data Aggregation</h3>
            <p class="mktg-text">Already running Zabbix? <?= h($brandName) ?> pulls host and trigger data into a unified dashboard. No need to maintain separate monitoring workflows.</p>
        </div>
        <div class="mktg-card">
            <h3 class="mktg-h3">Public Status Page</h3>
            <p class="mktg-text">Give your subscribers a professional status page. When an outage happens, they check the status page instead of flooding your call center. Reduce support tickets by up to 40%.</p>
        </div>
    </div>
</div>

<div class="mktg-cta-section">
    <h2>Join 50+ ISPs already using <?= h($brandName) ?></h2>
    <p>Start with a free plan. Add IXC and Zabbix integrations when ready.</p>
    <a href="/app/register" class="btn-cta">Start Free</a>
    <a href="/pt/para-provedores" class="btn-cta-outline">Ver em Portugues</a>
</div>
