<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \Cake\Core\Configure::read('Brand.name', 'ISP Status') ?> - Monitor Your Infrastructure</title>
    <meta name="description" content="Real-time uptime monitoring, beautiful status pages, and instant alerts. Free to start.">
    <link rel="icon" type="image/png" href="/img/icon_isp_status_page.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/icon_isp_status_page.png">
    <meta name="theme-color" content="#2979FF">
    <link rel="stylesheet" href="/css/landing.css">
    <link rel="stylesheet" href="/css/pricing.css">
</head>
<body>

<!-- Navigation -->
<nav class="landing-nav">
    <div class="nav-container">
        <a href="/" class="nav-brand">
            <img src="/img/icon_isp_status_page.png" alt="<?= \Cake\Core\Configure::read('Brand.name', 'ISP Status') ?>" class="nav-logo">
            <span><?= \Cake\Core\Configure::read('Brand.name', 'ISP Status') ?></span>
        </a>
        <div class="nav-links">
            <div class="nav-dropdown">
                <a href="#features" class="nav-dropdown-trigger">Features &#9662;</a>
                <div class="nav-dropdown-menu">
                    <a href="/features/status-page">Status Pages</a>
                    <a href="/features/alerting">Alerting</a>
                    <a href="/use-cases/isp">For ISPs</a>
                    <a href="/use-cases/saas">For SaaS</a>
                </div>
            </div>
            <a href="#pricing">Pricing</a>
            <a href="/blog">Blog</a>
            <a href="/about">About</a>
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
        <p class="hero-note">No credit card required. Start monitoring free.</p>
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
                <p>HTTP, Ping, Port, SSL Certificate, and Heartbeat monitoring. Checks as fast as 30 seconds, from multiple regions around the world.</p>
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

        <div class="plan-cards-grid">
            <?php if (!empty($plans)): ?>
                <?php foreach ($plans as $plan): ?>
                    <?= $this->element('pricing/plan_card', ['plan' => $plan]) ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="plan-card">
                    <div class="plan-card__header">
                        <h3 class="plan-card__name">Free</h3>
                        <div class="plan-card__price">
                            <span class="plan-card__price-amount">$0</span>
                        </div>
                        <p class="plan-card__price-subtitle">Free forever</p>
                    </div>
                    <ul class="plan-card__features">
                        <li class="plan-card__feature">
                            <svg class="plan-card__check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>1 monitor</span>
                        </li>
                        <li class="plan-card__feature">
                            <svg class="plan-card__check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Email alerts</span>
                        </li>
                    </ul>
                    <a href="/app/register" class="plan-card__cta">Start Free</a>
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
                <img src="/img/icon_isp_status_page.png" alt="<?= \Cake\Core\Configure::read('Brand.name', 'KeepUp') ?>" class="footer-logo">
                <p>Real-time infrastructure monitoring for teams that care about uptime.</p>
            </div>
            <div class="footer-links">
                <h4>Product</h4>
                <a href="/features/status-page">Status Pages</a>
                <a href="/features/alerting">Alerting</a>
                <a href="#pricing">Pricing</a>
                <a href="/changelog">Changelog</a>
                <a href="/api/docs">API Docs</a>
            </div>
            <div class="footer-links">
                <h4>Use Cases</h4>
                <a href="/use-cases/isp">For ISPs</a>
                <a href="/use-cases/saas">For SaaS</a>
                <a href="/alternatives/uptimerobot">vs UptimeRobot</a>
                <a href="/alternatives/pingdom">vs Pingdom</a>
                <a href="/alternatives/statuspage-io">vs StatusPage.io</a>
            </div>
            <div class="footer-links">
                <h4>Resources</h4>
                <a href="/blog">Blog</a>
                <a href="/about">About</a>
                <a href="/pt/monitoramento">Portugu&ecirc;s</a>
                <a href="/app/login">Sign In</a>
                <a href="/app/register">Start Free</a>
            </div>
            <div class="footer-links">
                <h4>Legal</h4>
                <a href="/privacy">Privacy Policy</a>
                <a href="/terms">Terms of Service</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= \Cake\Core\Configure::read('Brand.name', 'ISP Status') ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

</body>
</html>
