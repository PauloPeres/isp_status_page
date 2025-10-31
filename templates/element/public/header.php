<?php
use Cake\Core\Configure;

// Get site name from settings (with fallback)
$siteName = Configure::read('Settings.site_name', 'ISP Status');
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
                    <p class="header-subtitle">Status dos Serviços</p>
                </div>
            </div>

            <nav class="header-nav">
                <?= $this->Html->link(
                    '🏠 Início',
                    ['controller' => 'Status', 'action' => 'index'],
                    ['class' => 'nav-link']
                ) ?>
                <?= $this->Html->link(
                    '📜 Histórico',
                    ['controller' => 'Status', 'action' => 'history'],
                    ['class' => 'nav-link']
                ) ?>
                <?= $this->Html->link(
                    '📧 Notificações',
                    ['controller' => 'Subscribers', 'action' => 'subscribe'],
                    ['class' => 'nav-link']
                ) ?>
            </nav>
        </div>
    </div>
</header>
