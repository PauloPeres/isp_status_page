<!DOCTYPE html>
<html lang="<?= h(str_replace('_', '-', Cake\I18n\I18n::getLocale())) ?>">
<head>
    <?= $this->Html->charset() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        <?= $this->fetch('title') ?> - <?= __('Super Admin') ?>
    </title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= $this->Url->build('/img/icon_isp_status_page.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $this->Url->build('/img/icon_isp_status_page.png') ?>">
    <meta name="theme-color" content="#1a1a2e">

    <?= $this->Html->css(['admin']) ?>
    <?= $this->fetch('meta') ?>
    <?= $this->fetch('css') ?>
</head>
<body class="super-admin-body">
    <?php if (!empty($impersonating)): ?>
    <div class="impersonation-banner">
        <?= __('You are impersonating organization: {0}', h($impersonatingOrgName ?? 'Unknown')) ?>
        <a href="<?= $this->Url->build(['prefix' => 'SuperAdmin', 'controller' => 'Organizations', 'action' => 'stopImpersonation']) ?>"><?= __('Stop Impersonation') ?></a>
    </div>
    <?php endif; ?>

    <?= $this->element('super_admin/navbar') ?>

    <div class="admin-container">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <?= $this->element('super_admin/sidebar') ?>

        <main class="admin-content">
            <?= $this->Flash->render() ?>

            <div class="content-wrapper">
                <?= $this->fetch('content') ?>
            </div>

            <footer class="admin-footer">
                <p><?= __('Super Admin Panel') ?> &mdash; <?= __('ISP Status Page') ?></p>
            </footer>
        </main>
    </div>

    <?= $this->Html->script('datetime-utils') ?>
    <?= $this->fetch('script') ?>
</body>
</html>
