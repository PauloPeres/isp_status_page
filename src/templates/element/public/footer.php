<?php
use Cake\Core\Configure;

$siteName = Configure::read('Settings.site_name', 'ISP Status');
$supportEmail = Configure::read('Settings.support_email', 'support@example.com');
?>

<footer class="public-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-section">
                <h4>Sobre</h4>
                <p>
                    Esta página mostra o status em tempo real de todos os nossos serviços.
                    Monitoramos continuamente para garantir a melhor experiência.
                </p>
            </div>

            <div class="footer-section">
                <h4>Links Úteis</h4>
                <ul class="footer-links">
                    <li>
                        <?= $this->Html->link(
                            'Página Inicial',
                            ['controller' => 'Status', 'action' => 'index']
                        ) ?>
                    </li>
                    <li>
                        <?= $this->Html->link(
                            'Histórico de Incidentes',
                            ['controller' => 'Status', 'action' => 'history']
                        ) ?>
                    </li>
                    <li>
                        <?= $this->Html->link(
                            'Assinar Notificações',
                            ['controller' => 'Subscribers', 'action' => 'subscribe']
                        ) ?>
                    </li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>Suporte</h4>
                <p>
                    <strong>Email:</strong><br>
                    <a href="mailto:<?= h($supportEmail) ?>"><?= h($supportEmail) ?></a>
                </p>
                <p class="footer-note">
                    Atualizações automáticas a cada 30 segundos
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= h($siteName) ?>. Todos os direitos reservados.</p>
            <p class="footer-powered">
                Powered by <a href="https://github.com/PauloPeres/isp_status_page" target="_blank">ISP Status Page</a>
            </p>
        </div>
    </div>
</footer>
