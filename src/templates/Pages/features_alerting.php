<?php
/**
 * Feature page: Alerting
 *
 * @var \App\View\AppView $this
 */
$this->extend('/layout/marketing');

$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');

$this->assign('title', 'Never Miss a Downtime Alert Again - ' . $brandName);
$this->assign('meta_description', 'KeepUp alerts you via 9 channels: Email, Slack, Discord, Telegram, PagerDuty, OpsGenie, Webhooks, SMS, and Teams. Escalation chains, cooldown periods, and more.');
$this->assign('og_title', 'Never Miss a Downtime Alert - KeepUp Alerting');
$this->assign('og_url', 'https://usekeeup.com/features/alerting');
?>

<div class="mktg-hero">
    <h1>Never Miss a Downtime Alert Again</h1>
    <p>9 notification channels. Escalation chains. Cooldown periods. <?= h($brandName) ?> ensures the right person gets the right alert at the right time.</p>
</div>

<div class="mktg-section" style="padding-top: 0;">
    <h2 class="mktg-h2" style="text-align: center; margin-bottom: 48px;">9 Alert Channels</h2>

    <div class="mktg-grid-3">
        <div class="mktg-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 12px;">&#9993;</div>
            <h3 class="mktg-h3">Email</h3>
            <p class="mktg-text">Reliable email notifications with rich HTML templates. Down alerts in red, recovery alerts in green. Clear, actionable information.</p>
        </div>
        <div class="mktg-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 12px;">#</div>
            <h3 class="mktg-h3">Slack</h3>
            <p class="mktg-text">Rich Slack messages posted to any channel. Includes monitor name, status, response time, and a direct link to the incident.</p>
        </div>
        <div class="mktg-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 12px;">&#9672;</div>
            <h3 class="mktg-h3">Discord</h3>
            <p class="mktg-text">Discord webhook embeds with color-coded severity. Perfect for teams that live in Discord.</p>
        </div>
        <div class="mktg-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 12px;">&#9992;</div>
            <h3 class="mktg-h3">Telegram</h3>
            <p class="mktg-text">Instant Telegram messages to your on-call bot or group chat. Cuts through notification noise.</p>
        </div>
        <div class="mktg-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 12px;">&#9888;</div>
            <h3 class="mktg-h3">PagerDuty</h3>
            <p class="mktg-text">Trigger PagerDuty incidents directly from <?= h($brandName) ?>. Integrates with your existing on-call schedules and escalation policies.</p>
        </div>
        <div class="mktg-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 12px;">&#9873;</div>
            <h3 class="mktg-h3">OpsGenie</h3>
            <p class="mktg-text">Create OpsGenie alerts with priority mapping. Your team gets paged through the tool they already trust.</p>
        </div>
        <div class="mktg-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 12px;">{}</div>
            <h3 class="mktg-h3">Webhooks</h3>
            <p class="mktg-text">Send JSON payloads to any HTTP endpoint. Build custom automations, update dashboards, or trigger runbooks.</p>
        </div>
        <div class="mktg-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 12px;">&#9742;</div>
            <h3 class="mktg-h3">SMS</h3>
            <p class="mktg-text">When all else fails, an SMS gets through. Critical alerts delivered directly to your phone.</p>
        </div>
        <div class="mktg-card" style="text-align: center;">
            <div style="font-size: 2rem; margin-bottom: 12px;">&#9641;</div>
            <h3 class="mktg-h3">Microsoft Teams</h3>
            <p class="mktg-text">Adaptive card notifications in Teams channels. Full incident details in a format your team can act on.</p>
        </div>
    </div>
</div>

<div class="mktg-section mktg-section-alt" style="padding: 80px 24px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 class="mktg-h2" style="text-align: center; margin-bottom: 48px;">Smart Alerting Features</h2>

        <div class="mktg-grid-2">
            <div class="mktg-card">
                <h3 class="mktg-h3">Notification Policies</h3>
                <p class="mktg-text">Create policies that define who gets notified, through which channel, and in what order. Assign policies to monitors so each service has the right escalation path.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Escalation Chains</h3>
                <p class="mktg-text">If the first on-call does not acknowledge within 5 minutes, escalate to the team lead. If they do not respond, page the CTO. Configurable at every step.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Cooldown Periods</h3>
                <p class="mktg-text">Prevent alert fatigue during flapping incidents. Set a cooldown period so you do not get 50 notifications for the same issue. One alert is enough.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Incident Acknowledgement</h3>
                <p class="mktg-text">Acknowledge incidents from the email alert (one-click link) or from the admin panel. Acknowledged incidents stop escalating, so your team knows someone is on it.</p>
            </div>
        </div>
    </div>
</div>

<div class="mktg-section">
    <h2 class="mktg-h2" style="margin-bottom: 32px;">How to Set Up Your First Alert</h2>

    <div style="max-width: 700px; margin: 0 auto;">
        <ol style="padding-left: 20px;">
            <li style="padding: 16px 0; border-bottom: 1px solid var(--color-gray-200); color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Add a notification channel</strong> &mdash; connect your Slack workspace, add a Telegram bot, or configure email recipients.</li>
            <li style="padding: 16px 0; border-bottom: 1px solid var(--color-gray-200); color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Create a notification policy</strong> &mdash; define which channels to use and in what order.</li>
            <li style="padding: 16px 0; border-bottom: 1px solid var(--color-gray-200); color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Assign the policy to a monitor</strong> &mdash; when this monitor goes down, the policy kicks in.</li>
            <li style="padding: 16px 0; color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Done</strong> &mdash; the entire setup takes about 60 seconds. You will get your first test alert immediately.</li>
        </ol>
    </div>
</div>

<div class="mktg-cta-section">
    <h2>Set up your first alert in 60 seconds</h2>
    <p>Free plan includes email alerts. Upgrade for Slack, Discord, PagerDuty, and more.</p>
    <a href="/app/register" class="btn-cta">Start Free</a>
</div>
