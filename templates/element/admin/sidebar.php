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
        <h3>Painel Admin</h3>
        <button class="sidebar-close" id="sidebarClose">&times;</button>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">Principal</span>

            <?= $this->Html->link(
                '<span class="nav-icon">📊</span> Dashboard',
                ['controller' => 'Admin', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Admin', $action, 'index') ? ' active' : '')
                ]
            ) ?>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Monitoramento</span>

            <?= $this->Html->link(
                '<span class="nav-icon">🖥️</span> Monitores',
                ['controller' => 'Monitors', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Monitors') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">📈</span> Verificações',
                ['controller' => 'Checks', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Checks') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">🚨</span> Incidentes',
                ['controller' => 'Incidents', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Incidents') ? ' active' : '')
                ]
            ) ?>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Comunicação</span>

            <?= $this->Html->link(
                '<span class="nav-icon">📧</span> Inscritos',
                ['controller' => 'Subscribers', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Subscribers') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">✉️</span> Email Logs',
                ['controller' => 'EmailLogs', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'EmailLogs') ? ' active' : '')
                ]
            ) ?>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Sistema</span>

            <?= $this->Html->link(
                '<span class="nav-icon">👥</span> Usuários',
                ['controller' => 'Users', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Users') ? ' active' : '')
                ]
            ) ?>

            <?= $this->Html->link(
                '<span class="nav-icon">⚙️</span> Configurações',
                ['controller' => 'Settings', 'action' => 'index'],
                [
                    'escape' => false,
                    'class' => 'nav-item' . (isActive($controller, 'Settings') ? ' active' : '')
                ]
            ) ?>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Visualizar</span>

            <?= $this->Html->link(
                '<span class="nav-icon">🌐</span> Página Pública',
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
            <span class="status-text">Sistema Online</span>
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
