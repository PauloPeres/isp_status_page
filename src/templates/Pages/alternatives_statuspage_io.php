<?php
/**
 * KeepUp vs StatusPage.io (Atlassian) comparison page
 *
 * @var \App\View\AppView $this
 */
$this->extend('/layout/marketing');

$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');

$this->assign('title', $brandName . ' vs StatusPage.io (Atlassian): Built-in Monitoring Included - ' . $brandName);
$this->assign('meta_description', 'Compare KeepUp and Atlassian StatusPage.io. KeepUp combines uptime monitoring AND status pages in one tool. No more paying for two separate services.');
$this->assign('og_title', 'KeepUp vs StatusPage.io: Built-in Monitoring Included');
$this->assign('og_url', 'https://usekeeup.com/alternatives/statuspage-io');
?>

<div class="mktg-hero">
    <h1><?= h($brandName) ?> vs StatusPage.io (Atlassian): Built-in Monitoring Included</h1>
    <p>StatusPage.io is a great status page tool. But it is <em>only</em> a status page &mdash; you still need a separate monitoring service. <?= h($brandName) ?> gives you both in one platform.</p>
</div>

<div class="mktg-section" style="padding-top: 0;">
    <h2 class="mktg-h2">Feature Comparison</h2>

    <table class="comparison-table">
        <thead>
            <tr>
                <th>Feature</th>
                <th><?= h($brandName) ?></th>
                <th>StatusPage.io (Atlassian)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Uptime Monitoring</strong></td>
                <td class="check-yes">HTTP, Ping, Port, SSL, Heartbeat, API checks</td>
                <td class="check-no">Not included (requires separate tool)</td>
            </tr>
            <tr>
                <td><strong>Status Pages</strong></td>
                <td class="check-yes">Branded, custom domains, i18n, password protection</td>
                <td class="check-yes">Branded, custom domains, subscriber notifications</td>
            </tr>
            <tr>
                <td><strong>Alert Channels</strong></td>
                <td class="check-yes">9 channels with escalation policies</td>
                <td>Email and webhook notifications for subscribers</td>
            </tr>
            <tr>
                <td><strong>Incident Management</strong></td>
                <td class="check-yes">Automatic + manual incidents, acknowledgement, updates</td>
                <td class="check-yes">Manual incident creation and updates</td>
            </tr>
            <tr>
                <td><strong>Automatic Status Updates</strong></td>
                <td class="check-yes">Monitors auto-update status page components</td>
                <td class="check-no">Manual updates only (or requires integration)</td>
            </tr>
            <tr>
                <td><strong>Starting Price</strong></td>
                <td class="check-yes">Free (includes monitoring + status page)</td>
                <td>From $29/mo (status page only)</td>
            </tr>
            <tr>
                <td><strong>Subscriber Notifications</strong></td>
                <td class="check-yes">Email notifications on incidents</td>
                <td class="check-yes">Email, SMS, webhook</td>
            </tr>
            <tr>
                <td><strong>Maintenance Windows</strong></td>
                <td class="check-yes">Scheduled with notifications</td>
                <td class="check-yes">Scheduled maintenance</td>
            </tr>
            <tr>
                <td><strong>Multi-language Status Pages</strong></td>
                <td class="check-yes">PT-BR and EN supported</td>
                <td>English primarily</td>
            </tr>
            <tr>
                <td><strong>ISP Integrations</strong></td>
                <td class="check-yes">IXC, Zabbix native</td>
                <td class="check-no">Not available</td>
            </tr>
            <tr>
                <td><strong>REST API</strong></td>
                <td class="check-yes">Full CRUD API</td>
                <td class="check-yes">Full API</td>
            </tr>
            <tr>
                <td><strong>Total Cost (monitoring + status page)</strong></td>
                <td class="check-yes">One subscription covers everything</td>
                <td>StatusPage.io + UptimeRobot/Pingdom = 2 subscriptions</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="mktg-section mktg-section-alt" style="padding: 80px 24px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 class="mktg-h2" style="margin-bottom: 32px;">The Case for a Combined Platform</h2>

        <div class="mktg-grid-2">
            <div class="mktg-card">
                <h3 class="mktg-h3">One Tool, Not Two</h3>
                <p class="mktg-text">With StatusPage.io, you need a separate monitoring tool (UptimeRobot, Pingdom, Datadog) to detect outages, then somehow connect it to update your status page. With <?= h($brandName) ?>, monitors feed directly into your status page. When a service goes down, the page updates automatically. When it recovers, so does the page.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">One Bill, Not Two</h3>
                <p class="mktg-text">StatusPage.io starts at $29/month for just status pages. Add a monitoring tool and you are easily at $50-100/month. <?= h($brandName) ?> includes both monitoring and status pages starting at $0/month on the free plan.</p>
            </div>
        </div>
    </div>
</div>

<div class="mktg-section">
    <h2 class="mktg-h2" style="margin-bottom: 32px;">Frequently Asked Questions</h2>

    <div class="faq-item">
        <div class="faq-q">Are <?= h($brandName) ?> status pages as customizable as StatusPage.io?</div>
        <div class="faq-a"><?= h($brandName) ?> status pages support custom domains, branding (logo, colors), multiple languages, subscriber notifications, password protection, and maintenance windows. For most teams, this covers everything they need.</div>
    </div>
    <div class="faq-item">
        <div class="faq-q">Can I use <?= h($brandName) ?> just for status pages?</div>
        <div class="faq-a">Absolutely. You can create status pages with manually managed components. But the real power is in connecting monitors to components for automatic status updates.</div>
    </div>
    <div class="faq-item">
        <div class="faq-q">Does <?= h($brandName) ?> support third-party metrics on status pages?</div>
        <div class="faq-a">Currently, <?= h($brandName) ?> displays data from its own monitors on status pages. Third-party metric integration is on our roadmap for 2026.</div>
    </div>
</div>

<div class="mktg-cta-section">
    <h2>Get monitoring AND status pages in one tool</h2>
    <p>Stop paying for two services. <?= h($brandName) ?> does it all.</p>
    <a href="/app/register" class="btn-cta">Start Free</a>
</div>
