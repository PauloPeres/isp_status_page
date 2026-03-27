<?php
$controller = $this->request->getParam('controller');
$action = $this->request->getParam('action');

if (!function_exists('isActive')) {
function isActive($currentController, $targetController, $currentAction = null, $targetAction = null) {
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

<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <h3><?= __('Admin Panel') ?></h3>
        <button class="sidebar-close" id="sidebarClose">&times;</button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title"><?= __('Main') ?></span>

            <?= $this->Html->link(
                '<span class="nav-icon">📊</span> ' . __('Dashboard'),
                ['controller' => 'Dashboard', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Dashboard', $action, 'index') ? ' active' : '')
                ]
            ) ?>
        </div>

        <div class="nav-section">
            <span class="nav-section-title"><?= __('Monitoring') ?></span>

            <?= $this->Html->link(
                '<span class="nav-icon">🖥️</span> ' . __('Monitors'),
                ['controller' => 'Monitors', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Monitors') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">📈</span> ' . __('Checks'),
                ['controller' => 'Checks', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Checks') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">🚨</span> ' . __('Incidents'),
                ['controller' => 'Incidents', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Incidents') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">🔗</span> ' . __('Integracoes'),
                ['controller' => 'Integrations', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Integrations') ? ' active' : '')
                ]
            ) ?>
        </div>

        <div class="nav-section">
            <span class="nav-section-title"><?= __('Communication') ?></span>

            <?= $this->Html->link(
                '<span class="nav-icon">📧</span> ' . __('Subscribers'),
                ['controller' => 'Subscribers', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Subscribers') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">✉️</span> ' . __('Email Logs'),
                ['controller' => 'EmailLogs', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'EmailLogs') ? ' active' : '')
                ]
            ) ?>
        </div>

        <div class="nav-section">
            <span class="nav-section-title"><?= __('System') ?></span>

            <?php
            // Users (team management): only show for owner and admin
            $role = $currentUserRole ?? null;
            $canManageTeam = $role === null || in_array($role, ['owner', 'admin'], true);
            $canManageSettings = $role === null || in_array($role, ['owner', 'admin'], true);
            ?>

            <?php if ($canManageTeam): ?>
            <?= $this->Html->link(
                '<span class="nav-icon">👥</span> ' . __('Users'),
                ['controller' => 'Users', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Users') ? ' active' : '')
                ]
            ) ?>
            <?php endif; ?>

            <?php if ($canManageSettings): ?>
            <?= $this->Html->link(
                '<span class="nav-icon">🔑</span> ' . __('API Keys'),
                ['controller' => 'ApiKeys', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'ApiKeys') ? ' active' : '')
                ]
            ) ?>
            <?php endif; ?>

            <?php if ($canManageSettings): ?>
            <?= $this->Html->link(
                '<span class="nav-icon">⚙️</span> ' . __('Settings'),
                ['controller' => 'Settings', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Settings') ? ' active' : '')
                ]
            ) ?>
            <?php endif; ?>
        </div>

        <div class="nav-section">
            <span class="nav-section-title"><?= __('View') ?></span>

            <?= $this->Html->link(
                '<span class="nav-icon">🌐</span> ' . __('Public Page'),
                ['controller' => 'Status', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item',
                    'target' => '_blank'
                ]
            ) ?>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="status-indicator">
            <span class="status-dot online"></span>
            <span class="status-text"><?= __('System Online') ?></span>
        </div>
    </div>
</aside>

<script>
(function() {
    var sidebar = document.querySelector('.admin-sidebar');
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

    // Close sidebar on mobile
    document.getElementById('sidebarClose')?.addEventListener('click', closeSidebar);

    // Close sidebar when clicking overlay
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar when clicking on nav item (mobile)
    document.querySelectorAll('.admin-sidebar .nav-item').forEach(function(item) {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });

    // Close sidebar on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && window.innerWidth <= 768) {
            closeSidebar();
        }
    });

    // Expose openSidebar for the navbar toggle button
    window.adminSidebarOpen = openSidebar;
    window.adminSidebarClose = closeSidebar;
})();
</script>
