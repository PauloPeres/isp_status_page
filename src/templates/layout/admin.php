<!DOCTYPE html>
<html lang="<?= h(str_replace('_', '-', Cake\I18n\I18n::getLocale())) ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $this->fetch('title') ?> - <?= __('ISP Status Admin') ?>
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= $this->Url->build('/img/icon_isp_status_page.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $this->Url->build('/img/icon_isp_status_page.png') ?>">
    <meta name="theme-color" content="#1E88E5">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="ISP Status">

    <?= $this->Html->css(['admin']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body>
    <?php
    $impersonatingOrgName = $this->request->getSession()->read('impersonating_org_name');
    ?>
    <?php if ($impersonatingOrgName): ?>
    <div class="impersonation-banner">
        <?= __('Impersonating: {0}', h($impersonatingOrgName)) ?>
        &mdash;
        <?= $this->Html->link(
            __('Stop Impersonation'),
            ['prefix' => 'SuperAdmin', 'controller' => 'Organizations', 'action' => 'stopImpersonation'],
            ['style' => 'color: white; text-decoration: underline; font-weight: bold;']
        ) ?>
    </div>
    <?php endif; ?>

    <?= $this->element('admin/navbar') ?>

    <div class="admin-container">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?= $this->element('admin/sidebar') ?>

        <main class="admin-content">
            <?= $this->Flash->render() ?>

            <div class="content-wrapper">
                <?= $this->fetch('content') ?>
            </div>

            <?= $this->element('admin/footer') ?>
        </main>
    </div>

    <?= $this->Html->script('datetime-utils') ?>
    <?= $this->fetch('script') ?>
    <script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }
    </script>
</body>
</html>
