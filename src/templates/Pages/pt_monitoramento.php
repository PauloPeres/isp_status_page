<?php
/**
 * Portuguese: Monitoring landing page
 *
 * @var \App\View\AppView $this
 */
$this->extend('/layout/marketing');

$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');

$this->assign('title', 'Monitoramento de Uptime e Paginas de Status - ' . $brandName);
$this->assign('meta_description', 'Monitoramento de uptime em tempo real, paginas de status profissionais e alertas instantaneos. Comece gratis com o KeepUp. Monitorar site fora do ar nunca foi tao facil.');
$this->assign('og_title', 'Monitoramento de Uptime e Paginas de Status - KeepUp');
$this->assign('og_url', 'https://usekeeup.com/pt/monitoramento');
$this->assign('html_lang', 'pt-BR');
?>

<div class="mktg-hero">
    <h1>Monitoramento de Uptime e Paginas de Status</h1>
    <p>Saiba quando seu site esta fora do ar antes dos seus clientes. Monitoramento em tempo real, alertas instantaneos e paginas de status profissionais.</p>
</div>

<div class="mktg-section" style="padding-top: 0;">
    <div class="mktg-grid-3">
        <div class="mktg-card" style="text-align: center;">
            <div class="values-icon values-icon-blue" style="margin: 0 auto 16px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            </div>
            <h3 class="mktg-h3">Monitoramento de Uptime</h3>
            <p class="mktg-text">HTTP, Ping, Porta, Certificado SSL e Heartbeat. Verificacoes a cada 30 segundos de multiplas regioes do mundo.</p>
        </div>
        <div class="mktg-card" style="text-align: center;">
            <div class="values-icon values-icon-orange" style="margin: 0 auto 16px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
            </div>
            <h3 class="mktg-h3">Alertas Instantaneos</h3>
            <p class="mktg-text">Receba notificacoes por Email, Slack, Discord, Telegram, PagerDuty, OpsGenie, Webhooks, SMS e Microsoft Teams.</p>
        </div>
        <div class="mktg-card" style="text-align: center;">
            <div class="values-icon values-icon-green" style="margin: 0 auto 16px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
            </div>
            <h3 class="mktg-h3">Paginas de Status</h3>
            <p class="mktg-text">Paginas de status profissionais com dominio proprio, sua marca, notificacoes para assinantes e protecao por senha.</p>
        </div>
    </div>
</div>

<div class="mktg-section mktg-section-alt" style="padding: 80px 24px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 class="mktg-h2" style="text-align: center; margin-bottom: 48px;">Por que escolher o <?= h($brandName) ?>?</h2>

        <div class="mktg-grid-2">
            <div class="mktg-card">
                <h3 class="mktg-h3">Feito para o Brasil</h3>
                <p class="mktg-text">Interface em portugues, alertas em portugues, paginas de status em portugues. Integracoes nativas com IXC e Zabbix para provedores de internet. Conformidade com a LGPD.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Tudo em Um</h3>
                <p class="mktg-text">Monitoramento, alertas e paginas de status em uma unica plataforma. Sem precisar integrar tres ferramentas diferentes. Sem custos extras.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Comece Gratis</h3>
                <p class="mktg-text">Plano gratuito com 5 monitores, alertas por email e uma pagina de status. Sem cartao de credito. Sem limite de tempo. Use enquanto precisar.</p>
            </div>
            <div class="mktg-card">
                <h3 class="mktg-h3">Para Equipes</h3>
                <p class="mktg-text">Convide sua equipe com controle de acesso por funcao: proprietario, administrador, membro e visualizador. Cada pessoa ve o que precisa.</p>
            </div>
        </div>
    </div>
</div>

<div class="mktg-section">
    <h2 class="mktg-h2" style="text-align: center; margin-bottom: 16px;">Monitoramento Completo</h2>
    <p class="mktg-text" style="text-align: center; max-width: 700px; margin: 0 auto 32px;">Tudo que voce precisa para manter sua infraestrutura online:</p>

    <table class="comparison-table" style="max-width: 700px; margin: 0 auto;">
        <thead>
            <tr>
                <th>Funcionalidade</th>
                <th>Incluido</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Monitoramento HTTP/HTTPS</td><td class="check-yes">Sim</td></tr>
            <tr><td>Monitoramento Ping</td><td class="check-yes">Sim</td></tr>
            <tr><td>Monitoramento de Porta</td><td class="check-yes">Sim</td></tr>
            <tr><td>Monitoramento SSL</td><td class="check-yes">Sim</td></tr>
            <tr><td>Heartbeat (cron jobs)</td><td class="check-yes">Sim</td></tr>
            <tr><td>API REST completa</td><td class="check-yes">Sim</td></tr>
            <tr><td>Integracao IXC</td><td class="check-yes">Sim</td></tr>
            <tr><td>Integracao Zabbix</td><td class="check-yes">Sim</td></tr>
            <tr><td>Paginas de Status</td><td class="check-yes">Sim</td></tr>
            <tr><td>9 canais de alerta</td><td class="check-yes">Sim</td></tr>
            <tr><td>Multi-tenancy</td><td class="check-yes">Sim</td></tr>
        </tbody>
    </table>
</div>

<div class="mktg-cta-section">
    <h2>Comece a monitorar agora</h2>
    <p>Plano gratuito disponivel. Sem cartao de credito.</p>
    <a href="/app/register" class="btn-cta">Comece Gratis</a>
    <a href="/pt/para-provedores" class="btn-cta-outline">Para Provedores de Internet</a>
</div>
