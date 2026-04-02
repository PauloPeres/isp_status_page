<?php
use Cake\Core\Configure;

$siteName = Configure::read('Settings.site_name', Configure::read('Brand.name', 'ISP Status'));
$supportEmail = Configure::read('Settings.support_email', \Cake\Core\Configure::read('Brand.supportEmail', 'support@usekeeup.com'));
?>

<footer class="public-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-section">
                <h4><?= __('About') ?></h4>
                <p>
                    <?= __('This page shows the real-time status of all our services. We continuously monitor to ensure the best experience.') ?>
                </p>
            </div>

            <div class="footer-section">
                <h4><?= __('Useful Links') ?></h4>
                <ul class="footer-links">
                    <li>
                        <?= $this->Html->link(
                            __('Home Page'),
                            ['controller' => 'Status', 'action' => 'index']
                        ) ?>
                    </li>
                    <li>
                        <?= $this->Html->link(
                            __('Incident History'),
                            ['controller' => 'Status', 'action' => 'history']
                        ) ?>
                    </li>
                    <li>
                        <?= $this->Html->link(
                            __('Subscribe to Notifications'),
                            ['controller' => 'Subscribers', 'action' => 'subscribe']
                        ) ?>
                    </li>
                </ul>
            </div>

            <div class="footer-section">
                <h4><?= __('Support') ?></h4>
                <p>
                    <strong><?= __('Email') ?>:</strong><br>
                    <a href="mailto:<?= h($supportEmail) ?>"><?= h($supportEmail) ?></a>
                </p>
                <p class="footer-note">
                    <?= __('Automatic updates every 30 seconds') ?>
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= h($siteName) ?>. <?= __('All rights reserved.') ?></p>
            <p class="footer-powered">
                Powered by <a href="https://github.com/PauloPeres/isp_status_page" target="_blank"><?= Configure::read('Brand.fullName', 'ISP Status Page') ?></a>
            </p>
        </div>
    </div>
</footer>
