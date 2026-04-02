<?php
/**
 * Feature page: Status Pages
 *
 * @var \App\View\AppView $this
 */
$this->extend('/layout/marketing');

$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');

$this->assign('title', 'Beautiful Status Pages Your Customers Will Love - ' . $brandName);
$this->assign('meta_description', 'Create branded status pages with custom domains, i18n, subscriber notifications, and password protection. Set up in 2 minutes with KeepUp.');
$this->assign('og_title', 'Beautiful Status Pages - KeepUp');
$this->assign('og_url', 'https://usekeeup.com/features/status-page');
?>

<div class="mktg-hero">
    <h1>Beautiful Status Pages Your Customers Will Love</h1>
    <p>Professional, branded status pages that build trust during incidents and showcase your uptime. Connected to real monitoring data, not manual updates.</p>
</div>

<div class="mktg-section" style="padding-top: 0;">
    <div class="mktg-grid-2">
        <div>
            <h2 class="mktg-h2">Status Pages That Work for You</h2>
            <p class="mktg-text">A status page is the public face of your reliability engineering. It is where your customers go when they suspect something is wrong. It needs to be trustworthy, informative, and on-brand.</p>
            <p class="mktg-text"><?= h($brandName) ?> status pages are connected directly to your monitors. No manual updates during a stressful incident. The page reflects reality in real time.</p>
            <a href="/app/register" class="btn-cta" style="margin-top: 16px;">Create Your Status Page</a>
        </div>
        <div style="background: var(--color-gray-50); border-radius: 12px; padding: 32px; border: 1px solid var(--color-gray-200);">
            <div style="background: var(--color-brand-700); color: #fff; padding: 20px; border-radius: 8px; margin-bottom: 16px;">
                <div style="font-weight: 700; font-size: 1.1rem;">Your Company Status</div>
                <div style="font-size: 0.85rem; opacity: 0.7; margin-top: 4px;">status.yourcompany.com</div>
            </div>
            <div style="display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--color-gray-200);">
                <span style="width: 10px; height: 10px; background: var(--color-success); border-radius: 50%; margin-right: 12px;"></span>
                <span style="flex: 1; font-weight: 500;">Web Application</span>
                <span style="color: var(--color-success-dark); font-size: 0.85rem; font-weight: 500;">Operational</span>
            </div>
            <div style="display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--color-gray-200);">
                <span style="width: 10px; height: 10px; background: var(--color-success); border-radius: 50%; margin-right: 12px;"></span>
                <span style="flex: 1; font-weight: 500;">API</span>
                <span style="color: var(--color-success-dark); font-size: 0.85rem; font-weight: 500;">Operational</span>
            </div>
            <div style="display: flex; align-items: center; padding: 12px 0;">
                <span style="width: 10px; height: 10px; background: var(--color-warning); border-radius: 50%; margin-right: 12px;"></span>
                <span style="flex: 1; font-weight: 500;">Database</span>
                <span style="color: var(--color-warning-dark); font-size: 0.85rem; font-weight: 500;">Degraded</span>
            </div>
        </div>
    </div>
</div>

<div class="mktg-section mktg-section-alt" style="padding: 80px 24px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 class="mktg-h2" style="text-align: center; margin-bottom: 48px;">Everything You Need in a Status Page</h2>

        <div class="mktg-grid-3">
            <div class="mktg-card">
                <h3 class="mktg-h3">Custom Domains</h3>
                <p class="mktg-text">Point status.yourcompany.com to your <?= h($brandName) ?> status page. Your customers see your domain, your brand, your identity.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Full Branding</h3>
                <p class="mktg-text">Upload your logo, set your brand colors, customize the page title and description. The status page looks like part of your product.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Multi-Language (i18n)</h3>
                <p class="mktg-text">Serve status pages in English, Portuguese, or Spanish. Your international customers see the page in their language.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Subscriber Notifications</h3>
                <p class="mktg-text">Customers subscribe to your status page. When an incident starts or resolves, they receive an email notification automatically.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Password Protection</h3>
                <p class="mktg-text">Need a private status page for internal teams or enterprise customers? Add password protection to restrict access.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Maintenance Windows</h3>
                <p class="mktg-text">Schedule maintenance windows with start and end times. Subscribers are notified in advance. The status page shows the scheduled maintenance prominently.</p>
            </div>
        </div>
    </div>
</div>

<div class="mktg-section">
    <h2 class="mktg-h2" style="margin-bottom: 16px;">How It Works</h2>
    <p class="mktg-text" style="margin-bottom: 32px;">Setting up a status page takes about 2 minutes:</p>

    <div class="mktg-grid-2">
        <div>
            <ol style="padding-left: 20px;">
                <li style="padding: 12px 0; color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Create a status page</strong> &mdash; give it a name, description, and slug.</li>
                <li style="padding: 12px 0; color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Add components</strong> &mdash; each component represents a service (Web App, API, Database, etc.).</li>
                <li style="padding: 12px 0; color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Connect monitors</strong> &mdash; link monitors to components for automatic status updates.</li>
                <li style="padding: 12px 0; color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Customize branding</strong> &mdash; upload logo, set colors, configure domain.</li>
                <li style="padding: 12px 0; color: var(--color-gray-500); line-height: 1.7;"><strong style="color: var(--color-brand-700);">Share with customers</strong> &mdash; publish the URL and let subscribers sign up.</li>
            </ol>
        </div>
        <div>
            <div class="mktg-card" style="background: var(--color-brand-50);">
                <h3 class="mktg-h3">Automatic Status Updates</h3>
                <p class="mktg-text">When a monitor detects a problem, the connected status page component automatically changes from "Operational" to "Degraded" or "Down." When the monitor recovers, the component goes back to "Operational." No manual intervention required.</p>
                <p class="mktg-text" style="margin-bottom: 0;">This means your status page is always accurate, even at 3 AM when nobody is at the keyboard.</p>
            </div>
        </div>
    </div>
</div>

<div class="mktg-cta-section">
    <h2>Create your status page in 2 minutes</h2>
    <p>Free plan includes 1 status page. Upgrade for custom domains and branding.</p>
    <a href="/app/register" class="btn-cta">Start Free</a>
</div>
