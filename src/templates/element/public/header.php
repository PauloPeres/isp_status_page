<?php
use Cake\Core\Configure;
use App\Service\SettingService;

// Get settings
$settingService = new SettingService();
$siteName = $settingService->get('site_name', 'ISP Status');

?>

<header class="public-header">
    <div class="container">
        <div class="header-content">
            <div class="header-brand">
                <?= $this->Html->image('icon_isp_status_page.png', [
                    'alt' => 'ISP Status',
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
                <a href="#subscribe-form" class="nav-link nav-link-subscribe">
                    📧 <?= __('Notifications') ?>
                </a>
            </nav>
        </div>
    </div>
</header>
