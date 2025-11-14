<?php
/**
 * @var \App\View\AppView $this
 * @var string $siteName Site name from controller
 * @var string $supportEmail Support email from controller
 */

// Fallback values if variables are not set
$siteName = $siteName ?? 'ISP Status';
$supportEmail = $supportEmail ?? 'support@example.com';
?>

<footer class="public-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-section">
                <h4><?= __('Sobre') ?></h4>
                <p>
                    <?= __('Esta página mostra o status em tempo real de todos os nossos serviços. Monitoramos continuamente para garantir a melhor experiência.') ?>
                </p>
            </div>

            <div class="footer-section">
                <h4><?= __('Links Úteis') ?></h4>
                <ul class="footer-links">
                    <li>
                        <?= $this->Html->link(
                            __('Página Inicial'),
                            ['controller' => 'Status', 'action' => 'index']
                        ) ?>
                    </li>
                    <li>
                        <?= $this->Html->link(
                            __('Histórico de Incidentes'),
                            ['controller' => 'Status', 'action' => 'history']
                        ) ?>
                    </li>
                    <li>
                        <?= $this->Html->link(
                            __('Assinar Notificações'),
                            ['controller' => 'Subscribers', 'action' => 'subscribe']
                        ) ?>
                    </li>
                </ul>
            </div>

            <div class="footer-section">
                <h4><?= __('Suporte') ?></h4>
                <p>
                    <strong><?= __('Email:') ?></strong><br>
                    <a href="mailto:<?= h($supportEmail) ?>"><?= h($supportEmail) ?></a>
                </p>
                <p class="footer-note">
                    <?= __('Atualizações automáticas a cada 30 segundos') ?>
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= h($siteName) ?>. <?= __('Todos os direitos reservados.') ?></p>
            <p class="footer-powered">
                <?= __('Powered by') ?> <a href="https://github.com/PauloPeres/isp_status_page" target="_blank">ISP Status Page</a>
            </p>
        </div>
    </div>
</footer>
