<?php
/**
 * Portuguese: ISP-focused page
 *
 * @var \App\View\AppView $this
 */
$this->extend('/layout/marketing');

$brandName = \Cake\Core\Configure::read('Brand.name', 'KeepUp');

$this->assign('title', 'Monitoramento para Provedores de Internet - ' . $brandName);
$this->assign('meta_description', 'KeepUp e a plataforma de monitoramento feita para provedores de internet brasileiros. Integracao nativa com IXC e Zabbix, suporte em portugues, conformidade LGPD.');
$this->assign('og_title', 'Monitoramento para Provedores de Internet - KeepUp');
$this->assign('og_url', 'https://usekeeup.com/pt/para-provedores');
$this->assign('html_lang', 'pt-BR');
?>

<div class="mktg-hero">
    <h1>Monitoramento para Provedores de Internet</h1>
    <p>Feito por engenheiros de ISP, para engenheiros de ISP. O <?= h($brandName) ?> integra nativamente com IXC, Zabbix e as ferramentas que voce ja usa.</p>
</div>

<div class="mktg-section" style="padding-top: 0;">
    <div class="mktg-grid-2">
        <div>
            <h2 class="mktg-h2">Por que provedores escolhem o <?= h($brandName) ?>?</h2>
            <p class="mktg-text">Gerenciar a infraestrutura de um provedor de internet e fundamentalmente diferente de monitorar uma aplicacao web. Voce precisa acompanhar sessoes PPPoE, status de equipamentos ONU, utilizacao de banda e saude dos roteadores core &mdash; tudo mantendo milhares de assinantes satisfeitos.</p>
            <p class="mktg-text">O <?= h($brandName) ?> nasceu exatamente desse ambiente. Nossa equipe passou anos operando redes de ISP no Brasil, e construimos a plataforma para resolver os problemas que enfrentavamos todos os dias.</p>
        </div>
        <div>
            <h2 class="mktg-h2">Funcionalidades para ISP</h2>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 12px 0; border-bottom: 1px solid var(--color-gray-200);"><strong style="color: var(--color-brand-500);">Integracao IXC</strong> &mdash; Monitore servicos e equipamentos diretamente do seu sistema de provisionamento IXC. Sem configuracao manual por assinante.</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--color-gray-200);"><strong style="color: var(--color-brand-500);">Integracao Zabbix</strong> &mdash; Traga dados de hosts e triggers do Zabbix para o <?= h($brandName) ?>. Um painel para tudo.</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--color-gray-200);"><strong style="color: var(--color-brand-500);">Monitoramento PPPoE</strong> &mdash; Acompanhe status de sessoes e detecte quedas antes dos assinantes ligarem para o NOC.</li>
                <li style="padding: 12px 0; border-bottom: 1px solid var(--color-gray-200);"><strong style="color: var(--color-brand-500);">Verificacao ONU/CPE</strong> &mdash; Monitore equipamentos por numero de serie ou ID atraves do IXC.</li>
                <li style="padding: 12px 0;"><strong style="color: var(--color-brand-500);">Paginas de Status Publicas</strong> &mdash; Seus assinantes consultam a pagina de status ao inves de lotar a central de atendimento.</li>
            </ul>
        </div>
    </div>
</div>

<div class="mktg-section mktg-section-alt" style="padding: 80px 24px;">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h2 class="mktg-h2" style="text-align: center; margin-bottom: 48px;">Feito para o Mercado Brasileiro</h2>

        <div class="mktg-grid-3">
            <div class="mktg-card" style="text-align: center;">
                <h3 class="mktg-h3">Conformidade LGPD</h3>
                <p class="mktg-text">Os dados dos seus assinantes estao protegidos. O <?= h($brandName) ?> foi projetado com a Lei Geral de Protecao de Dados em mente, com isolamento de dados e politicas de retencao adequadas.</p>
            </div>
            <div class="mktg-card" style="text-align: center;">
                <h3 class="mktg-h3">Suporte em Portugues</h3>
                <p class="mktg-text">Interface nativa em PT-BR, alertas em portugues, paginas de status em portugues e notificacoes por email. Sua equipe de suporte nao precisa traduzir alertas em ingles as 3 da manha.</p>
            </div>
            <div class="mktg-card" style="text-align: center;">
                <h3 class="mktg-h3">Pronto para ABRINT</h3>
                <p class="mktg-text">Projetado para atender aos padroes operacionais esperados pelas associacoes de provedores brasileiras. Paginas de status profissionais que constroem confianca com assinantes.</p>
            </div>
        </div>
    </div>
</div>

<div class="mktg-section">
    <h2 class="mktg-h2" style="margin-bottom: 16px;">Como Provedores Usam o <?= h($brandName) ?></h2>
    <p class="mktg-text" style="margin-bottom: 32px;">Uma configuracao tipica de ISP inclui:</p>

    <div class="mktg-grid-2">
        <div class="mktg-card">
            <h3 class="mktg-h3">Monitoramento de Infraestrutura Core</h3>
            <p class="mktg-text">Verificacoes HTTP no seu portal web, ping nos roteadores e switches core, verificacao de porta em servicos criticos (DNS, RADIUS, DHCP). Seja alertado no momento em que algo cair.</p>
        </div>
        <div class="mktg-card">
            <h3 class="mktg-h3">Monitoramento de Servicos IXC</h3>
            <p class="mktg-text">Conecte sua instancia IXC e monitore servicos provisionados automaticamente. Acompanhe status de servico, saude de equipamentos e detecte falhas antes dos assinantes perceberem.</p>
        </div>
        <div class="mktg-card">
            <h3 class="mktg-h3">Agregacao de Dados Zabbix</h3>
            <p class="mktg-text">Ja usa Zabbix? O <?= h($brandName) ?> puxa dados de hosts e triggers para um painel unificado. Sem necessidade de manter workflows de monitoramento separados.</p>
        </div>
        <div class="mktg-card">
            <h3 class="mktg-h3">Pagina de Status Publica</h3>
            <p class="mktg-text">Ofereca aos seus assinantes uma pagina de status profissional. Quando uma queda acontece, eles consultam a pagina ao inves de lotar sua central. Reduza chamados de suporte em ate 40%.</p>
        </div>
    </div>
</div>

<div class="mktg-section" style="text-align: center;">
    <h2 class="mktg-h2" style="margin-bottom: 16px;">Precos Acessiveis</h2>
    <p class="mktg-text" style="max-width: 600px; margin: 0 auto 32px;">Plano gratuito com 5 monitores. Planos pagos com precos justos para o mercado brasileiro. Sem surpresas na cobranca.</p>
    <a href="/#pricing" class="btn-cta" style="background: var(--color-brand-700);">Ver Precos</a>
</div>

<div class="mktg-cta-section">
    <h2>Junte-se a mais de 50 provedores que ja usam o <?= h($brandName) ?></h2>
    <p>Comece com o plano gratuito. Adicione integracoes IXC e Zabbix quando estiver pronto.</p>
    <a href="/app/register" class="btn-cta">Comece Gratis</a>
    <a href="/use-cases/isp" class="btn-cta-outline">See in English</a>
</div>
