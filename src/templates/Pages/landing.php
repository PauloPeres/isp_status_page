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
            <a href="/users/login" class="nav-btn nav-btn-outline">Sign In</a>
            <a href="/register" class="nav-btn nav-btn-primary">Start Free</a>
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
            <a href="/register" class="btn btn-hero-primary">Start Free</a>
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
            <!-- Free Plan -->
            <div class="pricing-card">
                <div class="pricing-header">
                    <h3>Free</h3>
                    <div class="pricing-price">
                        <span class="price-amount">$0</span>
                        <span class="price-period">/month</span>
                    </div>
                    <p class="pricing-desc">For personal projects and small sites.</p>
                </div>
                <ul class="pricing-features">
                    <li>Up to 5 monitors</li>
                    <li>5-minute check interval</li>
                    <li>1 status page</li>
                    <li>Email alerts</li>
                    <li>24-hour data retention</li>
                    <li>Community support</li>
                </ul>
                <a href="/register" class="btn btn-pricing">Start Free</a>
            </div>

            <!-- Pro Plan -->
            <div class="pricing-card pricing-card-featured">
                <div class="pricing-badge">Most Popular</div>
                <div class="pricing-header">
                    <h3>Pro</h3>
                    <div class="pricing-price">
                        <span class="price-amount">$19</span>
                        <span class="price-period">/month</span>
                    </div>
                    <p class="pricing-desc">For growing teams and businesses.</p>
                </div>
                <ul class="pricing-features">
                    <li>Up to 50 monitors</li>
                    <li>1-minute check interval</li>
                    <li>5 status pages</li>
                    <li>Email, Slack, Discord alerts</li>
                    <li>90-day data retention</li>
                    <li>Custom domains</li>
                    <li>API access</li>
                    <li>Priority support</li>
                </ul>
                <a href="/register" class="btn btn-pricing-featured">Start Free Trial</a>
            </div>

            <!-- Business Plan -->
            <div class="pricing-card">
                <div class="pricing-header">
                    <h3>Business</h3>
                    <div class="pricing-price">
                        <span class="price-amount">$49</span>
                        <span class="price-period">/month</span>
                    </div>
                    <p class="pricing-desc">For organizations that need full control.</p>
                </div>
                <ul class="pricing-features">
                    <li>Unlimited monitors</li>
                    <li>30-second check interval</li>
                    <li>Unlimited status pages</li>
                    <li>All alert channels</li>
                    <li>1-year data retention</li>
                    <li>Custom domains + branding</li>
                    <li>Full API access</li>
                    <li>Multi-region checks</li>
                    <li>SSO / SAML</li>
                    <li>Dedicated support</li>
                </ul>
                <a href="/register" class="btn btn-pricing">Start Free Trial</a>
            </div>
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
                <a href="/users/login">Sign In</a>
                <a href="/register">Register</a>
            </div>
            <div class="footer-links">
                <h4>Legal</h4>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> ISP Status. All rights reserved.</p>
        </div>
    </div>
</footer>

</body>
</html>
