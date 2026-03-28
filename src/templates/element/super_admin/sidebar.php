<?php
$controller = $this->request->getParam('controller');
$action = $this->request->getParam('action');

if (!function_exists('isSuperAdminActive')) {
function isSuperAdminActive($currentController, $targetController, $currentAction = null, $targetAction = null) {
    if ($currentController !== $targetController) {
        return false;
    }
    if ($targetAction && $currentAction !== $targetAction) {
        return false;
    }
    return true;
}
}
?>

<aside class="admin-sidebar super-admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <h3><?= __('Super Admin') ?></h3>
        <button class="sidebar-close" id="sidebarClose">&times;</button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title"><?= __('Overview') ?></span>

            <?= $this->Html->link(
                '<span class="nav-icon">&#x1F4CA;</span> ' . __('Dashboard'),
                ['prefix' => 'SuperAdmin', 'controller' => 'Dashboard', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isSuperAdminActive($controller, 'Dashboard', $action, 'index') ? ' active' : '')
                ]
            ) ?>
        </div>

        <div class="nav-section">
            <span class="nav-section-title"><?= __('Management') ?></span>

            <?= $this->Html->link(
                '<span class="nav-icon">&#x1F3E2;</span> ' . __('Organizations'),
                ['prefix' => 'SuperAdmin', 'controller' => 'Organizations', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isSuperAdminActive($controller, 'Organizations') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">&#x1F465;</span> ' . __('Users'),
                ['prefix' => 'SuperAdmin', 'controller' => 'Users', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isSuperAdminActive($controller, 'Users') ? ' active' : '')
                ]
            ) ?>
        </div>

        <div class="nav-section">
            <span class="nav-section-title"><?= __('Analytics') ?></span>

            <?= $this->Html->link(
                '<span class="nav-icon">&#x1F4B0;</span> ' . __('Revenue'),
                ['prefix' => 'SuperAdmin', 'controller' => 'Revenue', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isSuperAdminActive($controller, 'Revenue') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">&#x1F3E5;</span> ' . __('Platform Health'),
                ['prefix' => 'SuperAdmin', 'controller' => 'Health', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isSuperAdminActive($controller, 'Health') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">&#x1F6E1;</span> ' . __('Security Logs'),
                ['prefix' => 'SuperAdmin', 'controller' => 'SecurityLogs', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isSuperAdminActive($controller, 'SecurityLogs') ? ' active' : '')
                ]
            ) ?>
        </div>

        <div class="nav-section">
            <span class="nav-section-title"><?= __('Navigation') ?></span>

            <?= $this->Html->link(
                '<span class="nav-icon">&#x2B05;</span> ' . __('Back to Admin'),
                ['prefix' => false, 'controller' => 'Dashboard', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item'
                ]
            ) ?>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="status-indicator">
            <span class="status-dot online"></span>
            <span class="status-text"><?= __('Platform Online') ?></span>
        </div>
    </div>
</aside>

<script>
(function() {
    var sidebar = document.querySelector('.super-admin-sidebar');
    var overlay = document.getElementById('sidebarOverlay');

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('mobile-open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    function openSidebar() {
        if (sidebar) sidebar.classList.add('mobile-open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    document.getElementById('sidebarClose')?.addEventListener('click', closeSidebar);

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    document.querySelectorAll('.super-admin-sidebar .nav-item').forEach(function(item) {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && window.innerWidth <= 768) {
            closeSidebar();
        }
    });

    window.adminSidebarOpen = openSidebar;
    window.adminSidebarClose = closeSidebar;
})();
</script>
