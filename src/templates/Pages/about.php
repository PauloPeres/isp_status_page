<?php
/**
 * About page
 *
 * @var \App\View\AppView $this
 */
$this->extend('/layout/marketing');

$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');

$this->assign('title', 'About ' . $brandName . ' - Built by Engineers Who Have Been On-Call at 3 AM');
$this->assign('meta_description', 'KeepUp is built by IuriLabs, a team of ISP engineers in Brazil. Our mission: make uptime monitoring accessible to every team. Learn our story.');
$this->assign('og_title', 'About KeepUp - Our Story');
$this->assign('og_url', 'https://usekeeup.com/about');
?>

<div class="mktg-hero">
    <h1>Built by engineers who have been on-call at 3 AM</h1>
    <p>We know what it feels like when your monitoring tools fail you at the worst possible moment. That is why we built <?= h($brandName) ?>.</p>
</div>

<div class="mktg-section">
    <div class="mktg-grid-2">
        <div>
            <h2 class="mktg-h2">Our Story</h2>
            <p class="mktg-text"><?= h($brandName) ?> was born inside <strong>IuriLabs</strong>, a technology company based in Brazil that has been building software for Internet Service Providers since 2020.</p>
            <p class="mktg-text">We spent years managing ISP infrastructure &mdash; configuring Zabbix, integrating with IXC provisioning systems, and trying to piece together monitoring, alerting, and status pages from three different vendors. The experience was frustrating, expensive, and fragile.</p>
            <p class="mktg-text">So we decided to build the tool we wished existed: a single platform that combines uptime monitoring, instant alerts across 9 channels, and beautiful public status pages &mdash; all at a price that makes sense for teams in emerging markets.</p>
        </div>
        <div>
            <h2 class="mktg-h2">The Brazilian Roots</h2>
            <p class="mktg-text">Brazil has over 20,000 registered ISPs, most serving between 500 and 50,000 subscribers. These companies are the backbone of internet access outside major metropolitan areas.</p>
            <p class="mktg-text">We built <?= h($brandName) ?> with this market in mind: native IXC and Zabbix integrations, Portuguese language support, LGPD compliance, and pricing that works in BRL. But the platform is designed for any team that cares about uptime &mdash; whether you are a solo developer or a global SaaS company.</p>
        </div>
    </div>
</div>

<div class="mktg-section mktg-section-alt" style="padding: 80px 24px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 class="mktg-h2" style="text-align: center; margin-bottom: 48px;">Our Mission</h2>
        <p class="mktg-text" style="text-align: center; max-width: 700px; margin: 0 auto 48px; font-size: 1.2rem;">Make uptime monitoring accessible to every team &mdash; regardless of size, budget, or location.</p>

        <div class="mktg-grid-3">
            <div class="mktg-card" style="text-align: center;">
                <div class="values-icon values-icon-blue" style="margin: 0 auto 16px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <h3 class="mktg-h3">Reliability</h3>
                <p class="mktg-text">Your monitoring platform must be more reliable than the systems it watches. We obsess over uptime, redundancy, and data integrity.</p>
            </div>
            <div class="mktg-card" style="text-align: center;">
                <div class="values-icon values-icon-green" style="margin: 0 auto 16px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 12l2 2 4-4"></path></svg>
                </div>
                <h3 class="mktg-h3">Simplicity</h3>
                <p class="mktg-text">Powerful does not have to mean complicated. We design every feature to be intuitive from the first click &mdash; no PhD in DevOps required.</p>
            </div>
            <div class="mktg-card" style="text-align: center;">
                <div class="values-icon values-icon-orange" style="margin: 0 auto 16px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </div>
                <h3 class="mktg-h3">Transparency</h3>
                <p class="mktg-text">We publish our own status page, share our changelog publicly, and price our plans honestly. No hidden fees, no surprise overages.</p>
            </div>
        </div>
    </div>
</div>

<div class="mktg-cta-section">
    <h2>Join us in building a more reliable internet</h2>
    <p>Start monitoring your infrastructure today. Free plan available.</p>
    <a href="/app/register" class="btn-cta">Start Free</a>
    <a href="/blog" class="btn-cta-outline">Read Our Blog</a>
</div>
