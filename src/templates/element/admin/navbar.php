<nav class="admin-navbar">
    <div class="navbar-brand">
        <?= $this->Html->image('icon_isp_status_page.png', [
            'alt' => __('ISP Status'),
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
            <button id="themeToggle" class="theme-toggle" title="Toggle dark mode">
                <span class="theme-icon"><i data-lucide="moon"></i></span>
            </button>

            <?= $this->element('admin/org_switcher') ?>

            <div class="user-info">
                <span class="user-name">
                    <?= h($this->Identity->get('username')) ?>
                </span>
                <span class="user-role"><?= __('Administrator') ?></span>
            </div>

            <div class="user-dropdown">
                <button class="user-avatar" id="userMenuToggle">
                    <span class="avatar-circle">
                        <?= strtoupper(substr($this->Identity->get('username'), 0, 1)) ?>
                    </span>
                </button>

                <div class="dropdown-menu" id="userMenu">
                    <?= $this->Html->link(
                        '<i data-lucide="user-circle"></i> ' . __('My Profile'),
                        ['controller' => 'Users', 'action' => 'view', $this->Identity->get('id')],
                        ['class' => 'dropdown-item', 'escape' => false]
                    ) ?>
                    <?= $this->Html->link(
                        '<i data-lucide="settings"></i> ' . __('Settings'),
                        ['controller' => 'Settings', 'action' => 'index'],
                        ['class' => 'dropdown-item', 'escape' => false]
                    ) ?>
                    <div class="dropdown-divider"></div>
                    <?= $this->Html->link(
                        '<i data-lucide="log-out"></i> ' . __('Logout'),
                        ['controller' => 'Users', 'action' => 'logout'],
                        ['class' => 'dropdown-item logout', 'escape' => false]
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
    if (window.adminSidebarOpen) {
        var sidebar = document.querySelector('.admin-sidebar');
        if (sidebar && sidebar.classList.contains('mobile-open')) {
            window.adminSidebarClose();
        } else {
            window.adminSidebarOpen();
        }
    } else {
        document.querySelector('.admin-sidebar')?.classList.toggle('mobile-open');
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const userMenu = document.getElementById('userMenu');
    if (userMenu && !e.target.closest('.user-dropdown')) {
        userMenu.classList.remove('show');
    }
});

// Dark mode toggle (P3-009)
(function() {
    const toggle = document.getElementById('themeToggle');
    const stored = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', stored);
    if (toggle) {
        toggle.querySelector('.theme-icon').innerHTML = stored === 'dark' ? '<i data-lucide="sun"></i>' : '<i data-lucide="moon"></i>';
        lucide.createIcons({nodes: toggle.querySelectorAll('i[data-lucide]')});
    }
    toggle?.addEventListener('click', function() {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        toggle.querySelector('.theme-icon').innerHTML = next === 'dark' ? '<i data-lucide="sun"></i>' : '<i data-lucide="moon"></i>';
        lucide.createIcons({nodes: toggle.querySelectorAll('i[data-lucide]')});
    });
})();
</script>
