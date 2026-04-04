<?php
/**
 * KeepUp vs Pingdom comparison page
 *
 * @var \App\View\AppView $this
 */
$this->extend('/layout/marketing');

$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');

$this->assign('title', $brandName . ' vs Pingdom: Why Teams Are Switching - ' . $brandName);
$this->assign('meta_description', 'Compare KeepUp and Pingdom. KeepUp offers affordable uptime monitoring with better status pages, ISP integrations, and no Pingdom-level pricing.');
$this->assign('og_title', 'KeepUp vs Pingdom: Why Teams Are Switching');
$this->assign('og_url', 'https://usekeeup.com/alternatives/pingdom');
?>

<div class="mktg-hero">
    <h1><?= h($brandName) ?> vs Pingdom: Why Teams Are Switching</h1>
    <p>Pingdom is a veteran in monitoring. But its pricing has pushed many teams to look for alternatives. <?= h($brandName) ?> delivers enterprise-grade monitoring without the enterprise price tag.</p>
</div>

<div class="mktg-section" style="padding-top: 0;">
    <h2 class="mktg-h2">Feature Comparison</h2>

    <table class="comparison-table">
        <thead>
            <tr>
                <th>Feature</th>
                <th><?= h($brandName) ?></th>
                <th>Pingdom</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Starting Price</strong></td>
                <td class="check-yes">Free (5 monitors) / Pro from $19/mo</td>
                <td>From $15/mo (10 monitors)</td>
            </tr>
            <tr>
                <td><strong>Status Pages</strong></td>
                <td class="check-yes">Included in all plans, fully branded</td>
                <td class="check-no">Not included (requires separate tool)</td>
            </tr>
            <tr>
                <td><strong>Check Interval</strong></td>
                <td>30 seconds (Pro)</td>
                <td>60 seconds</td>
            </tr>
            <tr>
                <td><strong>Alert Channels</strong></td>
                <td class="check-yes">9 channels including PagerDuty, OpsGenie, Discord</td>
                <td>Email, SMS, Webhooks</td>
            </tr>
            <tr>
                <td><strong>Notification Policies</strong></td>
                <td class="check-yes">Escalation chains, cooldown periods, quiet hours</td>
                <td>Basic alerting rules</td>
            </tr>
            <tr>
                <td><strong>Multi-tenancy</strong></td>
                <td class="check-yes">Organizations with role-based access</td>
                <td>Team accounts (limited)</td>
            </tr>
            <tr>
                <td><strong>ISP Integrations</strong></td>
                <td class="check-yes">IXC, Zabbix native adapters</td>
                <td class="check-no">Not available</td>
            </tr>
            <tr>
                <td><strong>REST API</strong></td>
                <td class="check-yes">Full CRUD with JWT auth</td>
                <td class="check-yes">Full API</td>
            </tr>
            <tr>
                <td><strong>Custom Domains (Status Pages)</strong></td>
                <td class="check-yes">Included in Pro</td>
                <td class="check-no">No status pages</td>
            </tr>
            <tr>
                <td><strong>Heartbeat Monitoring</strong></td>
                <td class="check-yes">Included</td>
                <td class="check-no">Not available</td>
            </tr>
            <tr>
                <td><strong>Maintenance Windows</strong></td>
                <td class="check-yes">With subscriber notifications</td>
                <td class="check-yes">Basic support</td>
            </tr>
            <tr>
                <td><strong>Portuguese Support</strong></td>
                <td class="check-yes">Native PT-BR</td>
                <td class="check-no">English only</td>
            </tr>
            <tr>
                <td><strong>AI Assistant</strong></td>
                <td class="check-yes">AI-powered configuration + troubleshooting + incident diagnosis</td>
                <td class="check-no">No AI features</td>
            </tr>
            <tr>
                <td><strong>Voice Call Alerts</strong></td>
                <td class="check-yes">Automated calls with keypad acknowledge/escalate</td>
                <td class="check-no">Not available</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="mktg-section mktg-section-alt" style="padding: 80px 24px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 class="mktg-h2" style="margin-bottom: 32px;">Why <?= h($brandName) ?> Over Pingdom?</h2>

        <div class="mktg-grid-3">
            <div class="mktg-card">
                <h3 class="mktg-h3">Affordable Pricing</h3>
                <p class="mktg-text">Pingdom's pricing scales quickly as you add monitors. <?= h($brandName) ?> offers a generous free tier and predictable pricing that does not punish growth.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Status Pages Included</h3>
                <p class="mktg-text">Pingdom does monitoring but not status pages. With <?= h($brandName) ?>, you get beautiful, branded status pages included in every plan &mdash; no need for a separate StatusPage.io subscription.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">ISP-Native</h3>
                <p class="mktg-text">For ISPs and MSPs, <?= h($brandName) ?> offers native IXC and Zabbix integrations. Monitor your network infrastructure alongside your web services in one platform.</p>
            </div>
        </div>
    </div>
</div>

<div class="mktg-section">
    <h2 class="mktg-h2" style="margin-bottom: 32px;">Frequently Asked Questions</h2>

    <div class="faq-item">
        <div class="faq-q">Is <?= h($brandName) ?> really cheaper than Pingdom?</div>
        <div class="faq-a">Yes. <?= h($brandName) ?> includes status pages and advanced alerting in its base plans. With Pingdom, you would need to add StatusPage.io (from $29/mo) and possibly PagerDuty for advanced alerting. <?= h($brandName) ?> bundles everything together.</div>
    </div>
    <div class="faq-item">
        <div class="faq-q">Does <?= h($brandName) ?> have real-user monitoring (RUM)?</div>
        <div class="faq-a"><?= h($brandName) ?> focuses on synthetic monitoring (HTTP, Ping, Port, SSL, API checks). If you need RUM, Pingdom may be a better fit. But for uptime monitoring and status pages, <?= h($brandName) ?> offers more value.</div>
    </div>
    <div class="faq-item">
        <div class="faq-q">Can I try <?= h($brandName) ?> for free?</div>
        <div class="faq-a">Absolutely. Our free plan includes 5 monitors, email alerts, and a status page. No credit card required, no time limit. Use it as long as you need.</div>
    </div>
</div>

<div class="mktg-cta-section">
    <h2>Try <?= h($brandName) ?> Free &mdash; No Pingdom-level pricing required</h2>
    <p>Get monitoring, alerting, and status pages in one affordable platform.</p>
    <a href="/app/register" class="btn-cta">Start Free</a>
</div>
