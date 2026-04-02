<?php
use Cake\Core\Configure;

// Get site name from settings (with fallback)
$siteName = Configure::read('Settings.site_name', Configure::read('Brand.name', 'ISP Status'));
?>

<header class="public-header">
    <div class="container">
        <div class="header-content">
            <div class="header-brand">
                <?= $this->Html->image('icon_isp_status_page.png', [
                    'alt' => $siteName,
                    'class' => 'header-logo'
                ]) ?>
                <div class="header-title">
                    <h1><?= h($siteName) ?></h1>
                    <p class="header-subtitle"><?= __('Service Status') ?></p>
                </div>
            </div>

            <nav class="header-nav">
                <?= $this->Html->link(
                    '🏠 ' . __('Home'),
                    ['controller' => 'Status', 'action' => 'index'],
                    ['class' => 'nav-link']
                ) ?>
                <?= $this->Html->link(
                    '📜 ' . __('History'),
                    ['controller' => 'Status', 'action' => 'history'],
                    ['class' => 'nav-link']
                ) ?>
                <?= $this->Html->link(
                    '📧 ' . __('Notifications'),
                    ['controller' => 'Subscribers', 'action' => 'subscribe'],
                    ['class' => 'nav-link']
                ) ?>
            </nav>
        </div>
    </div>
</header>
