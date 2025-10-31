<nav class="admin-navbar">
    <div class="navbar-brand">
        <?= $this->Html->image('icon_isp_status_page.png', [
            'alt' => 'ISP Status',
            'class' => 'navbar-logo'
        ]) ?>
        <span class="navbar-title">ISP Status</span>
    </div>

    <div class="navbar-menu">
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="navbar-user">
            <div class="user-info">
                <span class="user-name">
                    <?= h($this->Identity->get('username')) ?>
                </span>
                <span class="user-role">Administrador</span>
            </div>

            <div class="user-dropdown">
                <button class="user-avatar" id="userMenuToggle">
                    <span class="avatar-circle">
                        <?= strtoupper(substr($this->Identity->get('username'), 0, 1)) ?>
                    </span>
                </button>

                <div class="dropdown-menu" id="userMenu">
                    <?= $this->Html->link(
                        '👤 Meu Perfil',
                        ['controller' => 'Users', 'action' => 'view', $this->Identity->get('id')],
                        ['class' => 'dropdown-item']
                    ) ?>
                    <?= $this->Html->link(
                        '⚙️ Configurações',
                        ['controller' => 'Settings', 'action' => 'index'],
                        ['class' => 'dropdown-item']
                    ) ?>
                    <div class="dropdown-divider"></div>
                    <?= $this->Html->link(
                        '🚪 Sair',
                        ['controller' => 'Users', 'action' => 'logout'],
                        ['class' => 'dropdown-item logout']
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
// User menu toggle
document.getElementById('userMenuToggle')?.addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('userMenu')?.classList.toggle('show');
});

// Mobile menu toggle
document.getElementById('mobileMenuToggle')?.addEventListener('click', function() {
    document.querySelector('.admin-sidebar')?.classList.toggle('mobile-open');
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const userMenu = document.getElementById('userMenu');
    if (userMenu && !e.target.closest('.user-dropdown')) {
        userMenu.classList.remove('show');
    }
});
</script>
