<?php
/**
 * Changelog page
 *
 * @var \App\View\AppView $this
 */
$this->extend('/layout/marketing');

$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');

$this->assign('title', 'What\'s New in ' . $brandName . ' - Changelog');
$this->assign('meta_description', 'See what is new in KeepUp. Public changelog with all product updates, new features, and improvements.');
$this->assign('og_title', 'KeepUp Changelog - Product Updates');
$this->assign('og_url', 'https://usekeeup.com/changelog');
?>

<div class="mktg-hero">
    <h1>What's New in <?= h($brandName) ?></h1>
    <p>A public log of everything we ship. We believe in building in the open.</p>
</div>

<div class="mktg-section" style="max-width: 760px; padding-top: 0;">

    <div class="changelog-entry">
        <div class="changelog-date">April 2026</div>
        <h3 class="changelog-title"><?= h($brandName) ?> Launch</h3>
        <ul class="changelog-items">
            <li>Official public launch of <?= h($brandName) ?> SaaS platform</li>
            <li>Brand new UI with Navy + Electric Blue design system</li>
            <li>Marketing website, blog, and comparison pages</li>
            <li>Free, Pro, and Business plans available</li>
            <li>Full REST API v2 with JWT authentication</li>
            <li>Portuguese (PT-BR) and English language support</li>
        </ul>
    </div>

    <div class="changelog-entry">
        <div class="changelog-date">March 2026</div>
        <h3 class="changelog-title">Notification Channels and Policies</h3>
        <ul class="changelog-items">
            <li>9 notification channels: Email, Slack, Discord, Telegram, PagerDuty, OpsGenie, Webhooks, SMS, Microsoft Teams</li>
            <li>Notification policies with escalation chains and cooldown periods</li>
            <li>Public status pages with custom domains and branding</li>
            <li>SSL certificate monitoring with expiry alerts</li>
            <li>Heartbeat monitoring for cron jobs and background tasks</li>
            <li>Maintenance windows with subscriber notifications</li>
        </ul>
    </div>

    <div class="changelog-entry">
        <div class="changelog-date">February 2026</div>
        <h3 class="changelog-title">Multi-Tenancy and Billing</h3>
        <ul class="changelog-items">
            <li>Multi-tenant architecture with organization isolation</li>
            <li>Stripe billing integration with free, pro, and business plans</li>
            <li>REST API v2 with full CRUD for monitors, incidents, and status pages</li>
            <li>Organization invitations and role-based access control (owner, admin, member, viewer)</li>
            <li>API keys per organization with scoped permissions</li>
            <li>Redis-backed sessions and caching for improved performance</li>
        </ul>
    </div>

    <div class="changelog-entry">
        <div class="changelog-date">January 2026</div>
        <h3 class="changelog-title">Core Monitoring Engine</h3>
        <ul class="changelog-items">
            <li>HTTP, Ping, Port, and SSL certificate monitoring</li>
            <li>IXC integration for Brazilian ISP equipment monitoring</li>
            <li>Zabbix integration for host and trigger monitoring</li>
            <li>Generic REST API checker with JSON path validation</li>
            <li>Automatic incident creation and resolution</li>
            <li>Incident acknowledgement via email and admin panel</li>
            <li>Email alert service with throttling and cooldown</li>
        </ul>
    </div>

</div>

<div class="mktg-cta-section">
    <h2>Want to see these features in action?</h2>
    <p>Start monitoring for free. No credit card required.</p>
    <a href="/app/register" class="btn-cta">Try <?= h($brandName) ?> Free</a>
</div>
