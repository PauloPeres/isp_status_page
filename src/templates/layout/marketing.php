<?php
/**
 * Marketing layout — shared by all public marketing pages (blog, about, features, etc.)
 * Uses the KeepUp Navy + Electric Blue design system.
 */
$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');
$pageTitle = $this->fetch('title') ?: $brandName;
$metaDescription = $this->fetch('meta_description') ?: 'Real-time uptime monitoring, beautiful status pages, and instant alerts. Free to start.';
$ogTitle = $this->fetch('og_title') ?: $pageTitle;
$ogDescription = $this->fetch('og_description') ?: $metaDescription;
$ogImage = $this->fetch('og_image') ?: 'https://usekeeup.com/img/icon_isp_status_page.png';
$ogUrl = $this->fetch('og_url') ?: ('https://usekeeup.com' . $this->getRequest()->getRequestTarget());
$ogType = $this->fetch('og_type') ?: 'website';
$canonicalUrl = $this->fetch('canonical') ?: $ogUrl;
?>
<!DOCTYPE html>
<html lang="<?= $this->fetch('html_lang') ?: 'en' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?></title>
    <meta name="description" content="<?= h($metaDescription) ?>">
    <link rel="canonical" href="<?= h($canonicalUrl) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="<?= h($ogType) ?>">
    <meta property="og:title" content="<?= h($ogTitle) ?>">
    <meta property="og:description" content="<?= h($ogDescription) ?>">
    <meta property="og:image" content="<?= h($ogImage) ?>">
    <meta property="og:url" content="<?= h($ogUrl) ?>">
    <meta property="og:site_name" content="<?= h($brandName) ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($ogTitle) ?>">
    <meta name="twitter:description" content="<?= h($ogDescription) ?>">
    <meta name="twitter:image" content="<?= h($ogImage) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/img/icon_isp_status_page.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/img/icon_isp_status_page.png">
    <meta name="theme-color" content="#2979FF">

    <!-- Styles -->
    <link rel="stylesheet" href="/css/design-tokens.css">
    <link rel="stylesheet" href="/css/landing.css">
    <style>
        /* Marketing layout additions */
        .mktg-content { padding-top: 80px; min-height: 60vh; }
        .mktg-section { padding: 80px 24px; max-width: 1200px; margin: 0 auto; }
        .mktg-section-alt { background: var(--color-gray-50); }
        .mktg-section-navy { background: var(--color-brand-700); color: #fff; }
        .mktg-hero { padding: 100px 24px 80px; text-align: center; max-width: 900px; margin: 0 auto; }
        .mktg-hero h1 { font-size: clamp(2rem, 5vw, 3.2rem); line-height: 1.15; margin-bottom: 24px; color: var(--color-brand-700); }
        .mktg-hero p { font-size: 1.2rem; color: var(--color-gray-500); max-width: 700px; margin: 0 auto 32px; line-height: 1.7; }
        .mktg-h2 { font-size: clamp(1.5rem, 3vw, 2.2rem); color: var(--color-brand-700); margin-bottom: 16px; }
        .mktg-h3 { font-size: 1.25rem; color: var(--color-brand-700); margin-bottom: 12px; }
        .mktg-text { color: var(--color-gray-500); line-height: 1.7; margin-bottom: 24px; }
        .mktg-grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px; }
        .mktg-grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 32px; }
        .mktg-card { background: #fff; border-radius: 12px; padding: 32px; border: 1px solid var(--color-gray-200); transition: box-shadow 0.2s; }
        .mktg-card:hover { box-shadow: 0 8px 30px rgba(26, 35, 50, 0.08); }
        .mktg-cta-section { text-align: center; padding: 80px 24px; background: linear-gradient(135deg, var(--color-brand-700) 0%, var(--color-brand-800) 100%); color: #fff; }
        .mktg-cta-section h2 { color: #fff; font-size: clamp(1.5rem, 3vw, 2.2rem); margin-bottom: 16px; }
        .mktg-cta-section p { color: rgba(255,255,255,0.8); margin-bottom: 32px; font-size: 1.1rem; }
        .btn-cta { display: inline-block; padding: 14px 36px; background: var(--color-brand-500); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 1.05rem; transition: background 0.2s, transform 0.15s; }
        .btn-cta:hover { background: var(--color-brand-600); transform: translateY(-1px); }
        .btn-cta-outline { display: inline-block; padding: 14px 36px; background: transparent; color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 1.05rem; border: 2px solid rgba(255,255,255,0.4); transition: all 0.2s; margin-left: 16px; }
        .btn-cta-outline:hover { border-color: #fff; background: rgba(255,255,255,0.1); }

        /* Comparison tables */
        .comparison-table { width: 100%; border-collapse: collapse; margin: 32px 0; border-radius: 12px; overflow: hidden; border: 1px solid var(--color-gray-200); }
        .comparison-table thead { background: var(--color-brand-700); color: #fff; }
        .comparison-table th { padding: 16px 20px; text-align: left; font-weight: 600; }
        .comparison-table td { padding: 14px 20px; border-bottom: 1px solid var(--color-gray-200); }
        .comparison-table tbody tr:nth-child(even) { background: var(--color-gray-50); }
        .comparison-table tbody tr:hover { background: var(--color-brand-50); }
        .check-yes { color: var(--color-success-dark); font-weight: 600; }
        .check-no { color: var(--color-error); }

        /* Blog cards */
        .blog-card { background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid var(--color-gray-200); transition: box-shadow 0.2s, transform 0.15s; }
        .blog-card:hover { box-shadow: 0 8px 30px rgba(26, 35, 50, 0.08); transform: translateY(-2px); }
        .blog-card-body { padding: 24px; }
        .blog-card-title { font-size: 1.2rem; color: var(--color-brand-700); margin-bottom: 12px; line-height: 1.4; }
        .blog-card-title a { color: inherit; text-decoration: none; }
        .blog-card-title a:hover { color: var(--color-brand-500); }
        .blog-card-excerpt { color: var(--color-gray-500); line-height: 1.6; margin-bottom: 16px; font-size: 0.95rem; }
        .blog-card-meta { display: flex; align-items: center; gap: 16px; font-size: 0.85rem; color: var(--color-gray-400); }
        .blog-tag { display: inline-block; padding: 4px 10px; background: var(--color-brand-50); color: var(--color-brand-500); border-radius: 20px; font-size: 0.8rem; font-weight: 500; margin-right: 6px; }

        /* Blog single */
        .blog-content { max-width: 760px; margin: 0 auto; padding: 40px 24px 80px; }
        .blog-content h1 { font-size: clamp(1.8rem, 4vw, 2.8rem); color: var(--color-brand-700); line-height: 1.2; margin-bottom: 16px; }
        .blog-meta { display: flex; align-items: center; gap: 16px; color: var(--color-gray-400); font-size: 0.9rem; margin-bottom: 40px; padding-bottom: 24px; border-bottom: 1px solid var(--color-gray-200); }
        .blog-body { font-size: 1.08rem; line-height: 1.8; color: var(--color-gray-600); }
        .blog-body h2 { font-size: 1.6rem; color: var(--color-brand-700); margin: 48px 0 16px; }
        .blog-body h3 { font-size: 1.25rem; color: var(--color-brand-700); margin: 32px 0 12px; }
        .blog-body p { margin-bottom: 20px; }
        .blog-body ul, .blog-body ol { margin-bottom: 20px; padding-left: 28px; }
        .blog-body li { margin-bottom: 8px; }
        .blog-body blockquote { border-left: 4px solid var(--color-brand-500); padding: 16px 24px; margin: 24px 0; background: var(--color-gray-50); border-radius: 0 8px 8px 0; font-style: italic; color: var(--color-gray-600); }
        .blog-body code { background: var(--color-gray-100); padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
        .blog-body pre { background: var(--color-brand-800); color: #e2e8f0; padding: 20px; border-radius: 8px; overflow-x: auto; margin: 24px 0; }
        .blog-body pre code { background: none; padding: 0; color: inherit; }

        /* Changelog */
        .changelog-entry { position: relative; padding-left: 32px; margin-bottom: 48px; }
        .changelog-entry::before { content: ''; position: absolute; left: 8px; top: 8px; bottom: -48px; width: 2px; background: var(--color-gray-200); }
        .changelog-entry:last-child::before { display: none; }
        .changelog-entry::after { content: ''; position: absolute; left: 2px; top: 6px; width: 14px; height: 14px; border-radius: 50%; background: var(--color-brand-500); border: 3px solid #fff; box-shadow: 0 0 0 2px var(--color-brand-500); }
        .changelog-date { font-size: 0.85rem; color: var(--color-brand-500); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
        .changelog-title { font-size: 1.2rem; color: var(--color-brand-700); margin-bottom: 8px; }
        .changelog-items { list-style: none; padding: 0; }
        .changelog-items li { padding: 6px 0; color: var(--color-gray-500); font-size: 0.95rem; }
        .changelog-items li::before { content: '+'; color: var(--color-success-dark); font-weight: 700; margin-right: 8px; }

        /* FAQ */
        .faq-item { border-bottom: 1px solid var(--color-gray-200); padding: 20px 0; }
        .faq-q { font-weight: 600; color: var(--color-brand-700); font-size: 1.05rem; margin-bottom: 8px; }
        .faq-a { color: var(--color-gray-500); line-height: 1.7; }

        /* Values grid */
        .values-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; font-size: 1.5rem; }
        .values-icon-blue { background: var(--color-brand-50); color: var(--color-brand-500); }
        .values-icon-green { background: var(--color-success-light); color: var(--color-success-dark); }
        .values-icon-orange { background: #FFF3E0; color: #E65100; }

        /* Nav dropdown */
        .nav-dropdown { position: relative; }
        .nav-dropdown-menu { display: none; position: absolute; top: 100%; left: 0; background: #fff; border: 1px solid var(--color-gray-200); border-radius: 8px; padding: 8px 0; min-width: 220px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); z-index: 1001; }
        .nav-dropdown:hover .nav-dropdown-menu { display: block; }
        .nav-dropdown-menu a { display: block; padding: 10px 20px; color: var(--color-gray-600); text-decoration: none; font-size: 0.9rem; transition: background 0.15s; }
        .nav-dropdown-menu a:hover { background: var(--color-gray-50); color: var(--color-brand-500); }

        @media (max-width: 768px) {
            .mktg-section { padding: 48px 16px; }
            .mktg-hero { padding: 60px 16px 48px; }
            .mktg-grid-2, .mktg-grid-3 { grid-template-columns: 1fr; }
            .comparison-table { font-size: 0.85rem; }
            .comparison-table th, .comparison-table td { padding: 10px 12px; }
            .btn-cta-outline { margin-left: 0; margin-top: 12px; }
        }
    </style>
    <?= $this->fetch('css') ?>

    <!-- Schema.org Organization -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?= h($brandName) ?>",
        "url": "https://usekeeup.com",
        "logo": "https://usekeeup.com/img/icon_isp_status_page.png",
        "description": "Real-time uptime monitoring, beautiful status pages, and instant alerts.",
        "foundingDate": "2025",
        "founder": {
            "@type": "Organization",
            "name": "IuriLabs"
        },
        "sameAs": []
    }
    </script>
    <?= $this->fetch('schema_json_ld') ?>
</head>
<body>

<!-- Navigation -->
<nav class="landing-nav">
    <div class="nav-container">
        <a href="/" class="nav-brand">
            <img src="/img/icon_isp_status_page.png" alt="<?= h($brandName) ?>" class="nav-logo">
            <span><?= h($brandName) ?></span>
        </a>
        <div class="nav-links">
            <div class="nav-dropdown">
                <a href="/#features">Features</a>
                <div class="nav-dropdown-menu">
                    <a href="/features/status-page">Status Pages</a>
                    <a href="/features/alerting">Alerting</a>
                    <a href="/use-cases/saas">For SaaS</a>
                    <a href="/use-cases/isp">For ISPs</a>
                </div>
            </div>
            <a href="/#pricing">Pricing</a>
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

<!-- Main Content -->
<div class="mktg-content">
    <?= $this->fetch('content') ?>
</div>

<!-- Footer -->
<footer class="landing-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-brand">
                <img src="/img/icon_isp_status_page.png" alt="<?= h($brandName) ?>" class="footer-logo">
                <p>Real-time infrastructure monitoring for teams that care about uptime.</p>
            </div>
            <div class="footer-links">
                <h4>Product</h4>
                <a href="/features/status-page">Status Pages</a>
                <a href="/features/alerting">Alerting</a>
                <a href="/#pricing">Pricing</a>
                <a href="/changelog">Changelog</a>
                <a href="/status">Status</a>
                <a href="/api/docs">API Docs</a>
            </div>
            <div class="footer-links">
                <h4>Use Cases</h4>
                <a href="/use-cases/saas">For SaaS</a>
                <a href="/use-cases/isp">For ISPs</a>
                <a href="/alternatives/uptimerobot">vs UptimeRobot</a>
                <a href="/alternatives/pingdom">vs Pingdom</a>
                <a href="/alternatives/statuspage-io">vs StatusPage.io</a>
            </div>
            <div class="footer-links">
                <h4>Company</h4>
                <a href="/about">About</a>
                <a href="/blog">Blog</a>
                <a href="/pt/monitoramento">Portugu&ecirc;s</a>
                <a href="/privacy">Privacy Policy</a>
                <a href="/terms">Terms of Service</a>
            </div>
            <div class="footer-links">
                <h4>Account</h4>
                <a href="/app/login">Sign In</a>
                <a href="/app/register">Register</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= h($brandName) ?> by IuriLabs. All rights reserved.</p>
        </div>
    </div>
</footer>

<?= $this->fetch('script') ?>
</body>
</html>
