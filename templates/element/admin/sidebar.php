<?php
$controller = $this->request->getParam('controller');
$action = $this->request->getParam('action');

function isActive($currentController, $targetController, $currentAction = null, $targetAction = null) {
    if ($currentController !== $targetController) {
        return false;
    }
    if ($targetAction && $currentAction !== $targetAction) {
        return false;
    }
    return true;
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
                ['controller' => 'Admin', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Admin', $action, 'index') ? ' active' : '')
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

            <?= $this->Html->link(
                '<span class="nav-icon">👥</span> ' . __('Users'),
                ['controller' => 'Users', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Users') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">⚙️</span> ' . __('Settings'),
                ['controller' => 'Settings', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Settings') ? ' active' : '')
                ]
            ) ?>
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
// Close sidebar on mobile
document.getElementById('sidebarClose')?.addEventListener('click', function() {
    document.querySelector('.admin-sidebar')?.classList.remove('mobile-open');
});

// Close sidebar when clicking on nav item (mobile)
document.querySelectorAll('.admin-sidebar .nav-item').forEach(item => {
    item.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            document.querySelector('.admin-sidebar')?.classList.remove('mobile-open');
        }
    });
});
</script>
