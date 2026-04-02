<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($this->fetch('title', 'Status Page')) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=DM+Sans:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/design-tokens.css">
    <link rel="stylesheet" href="/css/status-page.css">
    <?php if ($this->fetch('primaryColor')): ?>
    <style>
        .sp-header { background: <?= h($this->fetch('primaryColor')) ?>; }
        .sp-powered a { color: <?= h($this->fetch('primaryColor')) ?>; }
    </style>
    <?php endif; ?>
    <?php if ($this->fetch('customCss')): ?>
    <?php
    $css = $this->fetch('customCss');
    // Prevent style tag breakout and script injection
    $css = str_ireplace('</style', '', $css);
    $css = str_ireplace('<script', '', $css);
    $css = str_ireplace('javascript:', '', $css);
    $css = str_ireplace('expression(', '', $css);
    $css = preg_replace('/@import\s/i', '/* blocked-import */', $css);
    ?>
    <style><?= $css ?></style>
    <?php endif; ?>
</head>
<body class="status-page-body" data-slug="<?= h($this->fetch('slug', '')) ?>">
    <header class="sp-header">
        <div class="sp-container sp-header-inner">
            <?php
            $logoUrl = $this->fetch('logoUrl');
            if ($logoUrl && preg_match('#^https?://#i', $logoUrl)):
            ?>
                <img src="<?= h($logoUrl) ?>" alt="" class="sp-logo">
            <?php endif; ?>
            <h1 class="sp-title"><?= h($this->fetch('pageTitle', 'System Status')) ?></h1>
        </div>
    </header>
    <main class="sp-container">
        <?= $this->fetch('content') ?>
    </main>
    <footer class="sp-footer">
        <div class="sp-container">
            <?php if ($this->fetch('footerText')): ?>
                <p><?= h($this->fetch('footerText')) ?></p>
            <?php endif; ?>
            <p class="sp-powered"><?= h($this->fetch('poweredBy', 'Powered by')) ?> <a href="/"><?= \Cake\Core\Configure::read('Brand.fullName', 'ISP Status Page') ?></a></p>
        </div>
    </footer>
    <script src="/js/status-page-live.js"></script>
</body>
</html>
