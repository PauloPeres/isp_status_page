<?php
/**
 * Organization Switcher Element
 *
 * Dropdown in the admin navbar showing current org name
 * with links to switch between organizations.
 *
 * @var \App\View\AppView $this
 */

// Get current user's organizations
$userOrgs = [];
$identity = $this->Identity ?? null;
if ($identity && $identity->get('id')) {
    $orgUsersTable = \Cake\ORM\TableRegistry::getTableLocator()->get('OrganizationUsers');
    $userOrgs = $orgUsersTable->find()
        ->contain(['Organizations'])
        ->where(['OrganizationUsers.user_id' => $identity->get('id')])
        ->all()
        ->toArray();
}

$currentOrg = $currentOrganization ?? null;
$orgName = $currentOrg['name'] ?? __('No Organization');

// Only show switcher if user has more than one org
$showSwitcher = count($userOrgs) > 1;
?>

<div class="org-switcher" style="position: relative; display: inline-block; margin-right: 1rem;">
    <button class="org-switcher-toggle" id="orgSwitcherToggle" style="
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: inherit;
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    ">
        <span style="font-size: 0.75rem;">🏢</span>
        <span><?= h($orgName) ?></span>
        <?php if ($showSwitcher): ?>
            <span style="font-size: 0.625rem;">▼</span>
        <?php endif; ?>
    </button>

    <?php if ($showSwitcher): ?>
    <div class="org-switcher-menu" id="orgSwitcherMenu" style="
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        margin-top: 0.25rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        min-width: 220px;
        z-index: 1000;
        overflow: hidden;
    ">
        <?php foreach ($userOrgs as $orgUser): ?>
            <?php
            $isCurrent = $currentOrg && ($orgUser->organization->id ?? null) == ($currentOrg['id'] ?? null);
            ?>
            <?php if ($isCurrent): ?>
                <div style="padding: 0.625rem 1rem; color: #1E88E5; font-weight: 600; font-size: 0.875rem; background: #f0f7ff;">
                    <?= h($orgUser->organization->name) ?>
                    <span style="font-size: 0.75rem; color: #999; margin-left: 0.25rem;">(<?= __('current') ?>)</span>
                </div>
            <?php else: ?>
                <?= $this->Form->postLink(
                    h($orgUser->organization->name),
                    ['controller' => 'OrganizationSwitcher', 'action' => 'switch', $orgUser->organization->id],
                    [
                        'style' => 'display: block; padding: 0.625rem 1rem; color: #333; text-decoration: none; font-size: 0.875rem; transition: background 0.15s;',
                        'class' => 'org-switch-link',
                    ]
                ) ?>
            <?php endif; ?>
        <?php endforeach; ?>

        <div style="border-top: 1px solid #eee; padding: 0.5rem 1rem;">
            <?= $this->Html->link(
                __('View all organizations'),
                ['controller' => 'OrganizationSwitcher', 'action' => 'select'],
                ['style' => 'font-size: 0.8125rem; color: #1E88E5; text-decoration: none;']
            ) ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    var toggle = document.getElementById('orgSwitcherToggle');
    var menu = document.getElementById('orgSwitcherMenu');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.org-switcher')) {
            menu.style.display = 'none';
        }
    });

    // Hover effect for switch links
    document.querySelectorAll('.org-switch-link').forEach(function(link) {
        link.addEventListener('mouseenter', function() { this.style.background = '#f5f5f5'; });
        link.addEventListener('mouseleave', function() { this.style.background = ''; });
    });
})();
</script>
