<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ISP Status - Monitor Your Infrastructure</title>
    <meta name="description" content="Real-time uptime monitoring, beautiful status pages, and instant alerts. Free to start.">
    <link rel="icon" type="image/png" href="/img/icon_isp_status_page.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/icon_isp_status_page.png">
    <meta name="theme-color" content="#1E88E5">
    <link rel="stylesheet" href="/css/landing.css">
</head>
<body>

<!-- Navigation -->
<nav class="landing-nav">
    <div class="nav-container">
        <a href="/" class="nav-brand">
            <img src="/img/icon_isp_status_page.png" alt="ISP Status" class="nav-logo">
            <span>ISP Status</span>
        </a>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#pricing">Pricing</a>
            <a href="/status" class="nav-link-subtle">Status</a>
            <a href="/app/login" class="nav-btn nav-btn-outline">Sign In</a>
            <a href="/app/register" class="nav-btn nav-btn-primary">Start Free</a>
        </div>
        <button class="nav-toggle" aria-label="Toggle navigation" onclick="document.querySelector('.nav-links').classList.toggle('nav-links-open')">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-container">
        <h1>Monitor Your Infrastructure.<br>Know When Things Go Down.</h1>
        <p class="hero-subtitle">Real-time uptime monitoring, beautiful status pages, and instant alerts. Free to start.</p>
        <div class="hero-actions">
            <a href="/app/register" class="btn btn-hero-primary">Start Free</a>
            <a href="/status" class="btn btn-hero-secondary">View Demo</a>
        </div>
        <p class="hero-note">No credit card required. Monitor up to 5 services free.</p>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="features">
    <div class="section-container">
        <h2 class="section-title">Everything You Need to Stay Online</h2>
        <p class="section-subtitle">Comprehensive monitoring tools built for teams that care about uptime.</p>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon feature-icon-blue">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
                <h3>Uptime Monitoring</h3>
                <p>HTTP, Ping, Port, SSL Certificate, and Heartbeat monitoring. Check every 30 seconds from multiple regions around the world.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon feature-icon-orange">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                </div>
                <h3>Instant Alerts</h3>
                <p>Get notified the moment something goes wrong via Email, Slack, Discord, Telegram, or Webhooks. Configurable escalation policies.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon feature-icon-green">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </div>
                <h3>Status Pages</h3>
                <p>Beautiful, customizable public status pages for your customers. Custom domains, branding, and subscriber notifications included.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon feature-icon-purple">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="16 18 22 12 16 6"></polyline>
                        <polyline points="8 6 2 12 8 18"></polyline>
                    </svg>
                </div>
                <h3>REST API</h3>
                <p>Full-featured REST API with JWT authentication. Automate monitor management, query check history, and integrate with your tools.</p>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="pricing">
    <div class="section-container">
        <h2 class="section-title">Simple, Transparent Pricing</h2>
        <p class="section-subtitle">Start free. Upgrade when you need more.</p>

        <div class="pricing-grid">
            <?php if (!empty($plans)): ?>
                <?php foreach ($plans as $i => $plan):
                    $isFeatured = ($plan->slug === 'pro');
                    $isFree = ($plan->price_monthly == 0);
                    $priceFormatted = $isFree ? '$0' : '$' . number_format($plan->price_monthly / 100, 0);

                    // Build feature list from plan limits
                    $features = [];
                    $features[] = ($plan->monitor_limit == -1 ? 'Unlimited' : 'Up to ' . $plan->monitor_limit) . ' monitors';

                    $interval = $plan->check_interval_min;
                    if ($interval < 60) {
                        $features[] = $interval . '-second check interval';
                    } else {
                        $features[] = ($interval / 60) . '-minute check interval';
                    }

                    $features[] = ($plan->status_page_limit == -1 ? 'Unlimited' : $plan->status_page_limit) . ' status page' . ($plan->status_page_limit != 1 ? 's' : '');

                    // Features from JSON
                    $planFeatures = json_decode($plan->features ?? '[]', true) ?: [];
                    if (in_array('email_alerts', $planFeatures)) $features[] = 'Email alerts';
                    if (in_array('slack_alerts', $planFeatures)) $features[] = 'Slack, Discord & Telegram alerts';
                    if (in_array('all_alert_channels', $planFeatures)) $features[] = 'All alert channels';
                    if (in_array('api_access', $planFeatures)) $features[] = 'API access';
                    if (in_array('custom_domains', $planFeatures)) $features[] = 'Custom domains';
                    if (in_array('multi_region', $planFeatures)) $features[] = 'Multi-region checks';
                    if (in_array('custom_branding', $planFeatures)) $features[] = 'Custom branding';
                    if (in_array('sso_saml', $planFeatures)) $features[] = 'SSO / SAML';
                    if (in_array('priority_support', $planFeatures)) $features[] = 'Priority support';
                    if (in_array('dedicated_support', $planFeatures)) $features[] = 'Dedicated support';

                    $features[] = $plan->data_retention_days . '-day data retention';

                    $teamLimit = ($plan->team_member_limit == -1 ? 'Unlimited' : $plan->team_member_limit) . ' team member' . ($plan->team_member_limit != 1 ? 's' : '');
                    $features[] = $teamLimit;
                ?>
                <div class="pricing-card<?= $isFeatured ? ' pricing-card-featured' : '' ?>">
                    <?php if ($isFeatured): ?>
                        <div class="pricing-badge">Most Popular</div>
                    <?php endif; ?>
                    <div class="pricing-header">
                        <h3><?= h($plan->name) ?></h3>
                        <div class="pricing-price">
                            <span class="price-amount"><?= $priceFormatted ?></span>
                            <span class="price-period">/month</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <?php foreach ($features as $feature): ?>
                            <li><?= h($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="/app/register" class="btn <?= $isFeatured ? 'btn-pricing-featured' : 'btn-pricing' ?>"><?= $isFree ? 'Start Free' : 'Start Free Trial' ?></a>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback if plans table not available -->
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3>Free</h3>
                        <div class="pricing-price"><span class="price-amount">$0</span><span class="price-period">/month</span></div>
                    </div>
                    <ul class="pricing-features"><li>5 monitors</li><li>Email alerts</li></ul>
                    <a href="/app/register" class="btn btn-pricing">Start Free</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="landing-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-brand">
                <img src="/img/icon_isp_status_page.png" alt="ISP Status" class="footer-logo">
                <p>Real-time infrastructure monitoring for teams that care about uptime.</p>
            </div>
            <div class="footer-links">
                <h4>Product</h4>
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="/status">Status</a>
                <a href="/api/docs">API Docs</a>
            </div>
            <div class="footer-links">
                <h4>Account</h4>
                <a href="/app/login">Sign In</a>
                <a href="/app/register">Register</a>
            </div>
            <div class="footer-links">
                <h4>Legal</h4>
                <a href="/privacy">Privacy Policy</a>
                <a href="/terms">Terms of Service</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> ISP Status. All rights reserved.</p>
        </div>
    </div>
</footer>

</body>
</html>
