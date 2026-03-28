<nav class="admin-navbar super-admin-navbar">
    <div class="navbar-brand">
        <?= $this->Html->image('icon_isp_status_page.png', [
            'alt' => __('ISP Status'),
            'class' => 'navbar-logo'
        ]) ?>
        <span class="navbar-title"><?= __('Super Admin') ?></span>
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
                <span class="user-role"><?= __('Super Administrator') ?></span>
            </div>

            <div class="user-dropdown">
                <button class="user-avatar" id="userMenuToggle">
                    <span class="avatar-circle" style="background: #e94560;">
                        <?= strtoupper(substr($this->Identity->get('username'), 0, 1)) ?>
                    </span>
                </button>

                <div class="dropdown-menu" id="userMenu">
                    <?= $this->Html->link(
                        '&#x1F464; ' . __('My Profile'),
                        ['prefix' => false, 'controller' => 'Users', 'action' => 'view', $this->Identity->get('id')],
                        ['class' => 'dropdown-item', 'escape' => false]
                    ) ?>
                    <?= $this->Html->link(
                        '&#x2699;&#xFE0F; ' . __('Settings'),
                        ['prefix' => false, 'controller' => 'Settings', 'action' => 'index'],
                        ['class' => 'dropdown-item', 'escape' => false]
                    ) ?>
                    <div class="dropdown-divider"></div>
                    <?= $this->Html->link(
                        '&#x1F6AA; ' . __('Logout'),
                        ['prefix' => false, 'controller' => 'Users', 'action' => 'logout'],
                        ['class' => 'dropdown-item logout', 'escape' => false]
                    ) ?>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
document.getElementById('userMenuToggle')?.addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('userMenu')?.classList.toggle('show');
});

document.getElementById('mobileMenuToggle')?.addEventListener('click', function() {
    if (window.adminSidebarOpen) {
        var sidebar = document.querySelector('.super-admin-sidebar');
        if (sidebar && sidebar.classList.contains('mobile-open')) {
            window.adminSidebarClose();
        } else {
            window.adminSidebarOpen();
        }
    } else {
        document.querySelector('.super-admin-sidebar')?.classList.toggle('mobile-open');
    }
});

document.addEventListener('click', function(e) {
    const userMenu = document.getElementById('userMenu');
    if (userMenu && !e.target.closest('.user-dropdown')) {
        userMenu.classList.remove('show');
    }
});
</script>
