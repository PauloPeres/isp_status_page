<?php
/**
 * Use case: SaaS companies
 *
 * @var \App\View\AppView $this
 */
$this->extend('/layout/marketing');

$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');

$this->assign('title', 'Status Pages & Monitoring for SaaS Companies - ' . $brandName);
$this->assign('meta_description', 'KeepUp helps SaaS companies monitor uptime, publish branded status pages, and alert teams instantly. API monitoring, webhook alerts, and more.');
$this->assign('og_title', 'Status Pages & Monitoring for SaaS - KeepUp');
$this->assign('og_url', 'https://usekeeup.com/use-cases/saas');
?>

<div class="mktg-hero">
    <h1>Status Pages & Monitoring for SaaS Companies</h1>
    <p>Your customers expect 99.9% uptime. <?= h($brandName) ?> helps you deliver it &mdash; and proves it with beautiful, branded status pages.</p>
</div>

<div class="mktg-section" style="padding-top: 0;">
    <div class="mktg-grid-3">
        <div class="mktg-card">
            <h3 class="mktg-h3">Public Status Pages</h3>
            <p class="mktg-text">Give your customers a professional status page they can trust. Custom domains, your branding, subscriber notifications, and automatic updates when monitors detect issues.</p>
        </div>
        <div class="mktg-card">
            <h3 class="mktg-h3">API Monitoring</h3>
            <p class="mktg-text">Monitor your REST APIs with configurable validators: check status codes, validate JSON response paths, verify content. Know when your API is not just up, but working correctly.</p>
        </div>
        <div class="mktg-card">
            <h3 class="mktg-h3">Webhook Alerts</h3>
            <p class="mktg-text">Integrate with your existing workflow. <?= h($brandName) ?> sends webhook payloads to any endpoint, so you can trigger custom automations when incidents occur.</p>
        </div>
        <div class="mktg-card">
            <h3 class="mktg-h3">SSL Certificate Monitoring</h3>
            <p class="mktg-text">Never let an SSL certificate expire again. <?= h($brandName) ?> monitors certificate expiration and alerts you days before it becomes a problem for your customers.</p>
        </div>
        <div class="mktg-card">
            <h3 class="mktg-h3">Heartbeat Monitoring</h3>
            <p class="mktg-text">Monitor cron jobs, background workers, and scheduled tasks. If your heartbeat does not check in on time, <?= h($brandName) ?> alerts your team immediately.</p>
        </div>
        <div class="mktg-card">
            <h3 class="mktg-h3">Team Collaboration</h3>
            <p class="mktg-text">Invite your entire team with role-based access. Owners, admins, members, and viewers &mdash; everyone sees what they need, nothing they do not.</p>
        </div>
    </div>
</div>

<div class="mktg-section mktg-section-alt" style="padding: 80px 24px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 class="mktg-h2" style="text-align: center; margin-bottom: 16px;">Your Customers Deserve a Status Page</h2>
        <p class="mktg-text" style="text-align: center; max-width: 700px; margin: 0 auto 48px;">When an outage happens, your customers should not have to wonder what is going on. A well-maintained status page reduces support tickets, builds trust, and shows transparency.</p>

        <div class="mktg-grid-2">
            <div class="mktg-card">
                <h3 class="mktg-h3">Branded Experience</h3>
                <p class="mktg-text">Your status page looks like your product &mdash; not a generic third-party tool. Custom logo, colors, favicon, and domain. Your customers never leave your brand ecosystem.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Automatic Updates</h3>
                <p class="mktg-text">Connect monitors to status page components. When your API monitor detects an issue, the status page updates automatically. When it recovers, the page reflects that too. Zero manual work during incidents.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Subscriber Notifications</h3>
                <p class="mktg-text">Let your customers subscribe to updates. When an incident starts or resolves, they get an email. No more "is anyone else experiencing issues?" in your support queue.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Maintenance Windows</h3>
                <p class="mktg-text">Schedule maintenance windows and notify subscribers in advance. Show professionalism and reduce surprise-related support tickets.</p>
            </div>
        </div>
    </div>
</div>

<div class="mktg-section">
    <h2 class="mktg-h2" style="margin-bottom: 16px;">How SaaS Teams Use <?= h($brandName) ?></h2>
    <p class="mktg-text" style="margin-bottom: 32px;">A typical SaaS monitoring setup:</p>

    <ol style="max-width: 700px; margin: 0 auto;">
        <li style="padding: 16px 0; border-bottom: 1px solid var(--color-gray-200); color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Add monitors</strong> for your web app, API endpoints, and background services.</li>
        <li style="padding: 16px 0; border-bottom: 1px solid var(--color-gray-200); color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Create a status page</strong> and map monitors to components (Web App, API, Database, etc.).</li>
        <li style="padding: 16px 0; border-bottom: 1px solid var(--color-gray-200); color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Set up notification policies</strong> with Slack for warnings and PagerDuty for critical alerts.</li>
        <li style="padding: 16px 0; border-bottom: 1px solid var(--color-gray-200); color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Point your custom domain</strong> (status.yourapp.com) to your <?= h($brandName) ?> status page.</li>
        <li style="padding: 16px 0; color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Share with customers</strong> and let subscribers sign up for incident notifications.</li>
    </ol>
</div>

<div class="mktg-cta-section">
    <h2>Your customers deserve a status page</h2>
    <p>Start free. Set up in under 5 minutes. No credit card required.</p>
    <a href="/app/register" class="btn-cta">Start Free</a>
</div>
