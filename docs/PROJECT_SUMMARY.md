# ISP Status Page - Resumo Executivo do Projeto

## Visão Geral

O **ISP Status Page** é uma solução open source para provedores de internet (ISPs) monitorarem seus serviços e exibirem o status em tempo real para seus clientes.

## Problema que Resolve

Provedores de internet enfrentam desafios como:
- Falta de visibilidade sobre o status dos serviços para clientes
- Chamados repetitivos questionando se "a internet está funcionando"
- Dificuldade em comunicar incidentes de forma proativa
- Necessidade de integrar múltiplos sistemas de monitoramento (IXC, Zabbix, etc)
- Custos elevados de soluções proprietárias

## Solução Proposta

Sistema completo com:
- **Página de Status Pública**: Interface limpa mostrando status de todos os serviços
- **Monitoramento Automatizado**: Verificações a cada 30-60 segundos
- **Integrações**: IXC, Zabbix, APIs REST customizadas
- **Sistema de Alertas**: Email, WhatsApp, SMS, Telegram (roadmap)
- **Gestão de Incidentes**: Criação e resolução automática
- **Painel Administrativo**: Gerenciamento completo

## Diferenciais

### 1. Open Source
- Licença Apache 2.0
- Sem custos de licenciamento
- Comunidade pode contribuir
- Código auditável

### 2. Focado em ISPs Brasileiros
- Integração nativa com IXC Soft
- Interface em português
- Timezone e locale brasileiros
- Entende o contexto de provedores regionais

### 3. Simples e Confiável
- SQLite (sem servidor de banco complexo)
- CakePHP (framework maduro e estável)
- Arquitetura simples e manutenível
- Fácil deploy e backup

### 4. Integrações Prontas
- IXC Soft (gestão de ISP)
- Zabbix (monitoramento enterprise)
- APIs REST genéricas
- Extensível para novos sistemas

## Arquitetura Técnica

### Stack
- **Backend**: PHP 8.1+ / CakePHP 5.x
- **Database**: SQLite (migração para MySQL/PostgreSQL possível)
- **Frontend**: HTML5, Tailwind CSS, Alpine.js
- **Background**: Cron jobs para verificações

### Componentes Principais

```
┌─────────────────────────────────┐
│   Status Page (Pública)         │
│   - Status de serviços          │
│   - Histórico de incidentes     │
│   - Assinatura de alertas       │
└─────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────┐
│   Admin Panel                   │
│   - CRUD de monitores           │
│   - Configurações               │
│   - Gerenciar integrações       │
│   - Dashboard                   │
└─────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────┐
│   Check Engine (Cron)           │
│   - Executa verificações        │
│   - Registra resultados         │
│   - Detecta mudanças de estado  │
│   - Dispara alertas             │
└─────────────────────────────────┘
                │
      ┌─────────┼─────────┐
      ▼         ▼         ▼
   ┌─────┐  ┌─────┐  ┌─────┐
   │ IXC │  │Zabbix│ │ REST│
   └─────┘  └─────┘  └─────┘
```

## Funcionalidades

### MVP (Fase 1)
- ✅ Monitores HTTP/HTTPS, Ping, Port
- ✅ Página de status pública com códigos HTTP inteligentes
- ✅ Painel administrativo completo
- ✅ Sistema de incidentes automático
- ✅ Alertas por email
- ✅ Sistema de assinaturas

### Fase 2 (Integrações)
- ✅ Integração com IXC Soft
- ✅ Integração com Zabbix
- ✅ API REST genérica

### Fase 3 (Futuro)
- 🔄 WhatsApp Business API
- 🔄 Telegram Bot
- 🔄 SMS Gateway
- 🔄 Sistema de telefonia
- 🔄 Dashboard com métricas avançadas
- 🔄 SLA tracking
- 🔄 Multi-idiomas

## Casos de Uso

### Caso 1: ISP Pequeno/Médio
**Perfil**: 500-2000 clientes, usa IXC, infraestrutura básica

**Uso**:
- Monitorar OLTs e roteadores principais
- Integrar com IXC para status de equipamentos
- Exibir status page para clientes
- Alertas por email para equipe técnica

**Benefício**: Redução de 40-60% em chamados de "está funcionando?"

### Caso 2: ISP Grande
**Perfil**: 5000+ clientes, Zabbix enterprise, NOC estruturado

**Uso**:
- Integrar com Zabbix para triggers críticas
- Monitores customizados via API REST
- Página de status com múltiplos serviços
- Alertas integrados com sistema de tickets

**Benefício**: Transparência para clientes, redução de carga no suporte

### Caso 3: ISP Regional
**Perfil**: Múltiplos POPs, cobertura regional

**Uso**:
- Monitorar status de cada POP separadamente
- Status page mostrando disponibilidade por região
- Assinaturas segmentadas por localidade
- Histórico de incidentes por região

**Benefício**: Comunicação proativa, confiança dos clientes

## ROI Esperado

### Redução de Custos
- **Suporte**: -40% em chamados relacionados a status
- **Ferramentas**: Substituir StatusPage.io ($99-399/mês) por solução própria
- **Tempo**: -50% em comunicação manual de incidentes

### Exemplo Financeiro (ISP com 1000 clientes)
- **Economia em ferramenta paga**: R$ 1.200/mês
- **Redução de 20 horas/mês de suporte**: R$ 2.000/mês (a R$ 100/h)
- **Total anual**: ~R$ 38.400

**Investimento**:
- Desenvolvimento: ~140h (R$ 14.000 se terceirizado)
- Servidor: R$ 100/mês
- **Payback**: 4-6 meses

## Timeline de Desenvolvimento

### Setup e Fundação (2 semanas)
- Instalação CakePHP
- Autenticação e usuários
- Layouts e UI base
- Database migrations

### Core Features (3 semanas)
- CRUD de monitores
- Motor de verificação
- Sistema de incidentes
- Página de status
- Alertas por email

### Integrações (2 semanas)
- Integração IXC
- Integração Zabbix
- API REST genérica

### Polimento (1 semana)
- Dashboard admin
- Testes finais
- Documentação
- Deploy

**Total: ~8 semanas** (pode ser reduzido com equipe maior)

## Requisitos de Infraestrutura

### Mínimo (até 50 monitores)
- **VPS**: 1 vCPU, 1GB RAM, 20GB SSD
- **Custo**: ~R$ 30-50/mês
- **Exemplo**: Contabo, DigitalOcean Droplet básico

### Recomendado (50-200 monitores)
- **VPS**: 2 vCPU, 2GB RAM, 40GB SSD
- **Custo**: ~R$ 60-100/mês
- **Exemplo**: DigitalOcean, Linode, Vultr

### Escalado (200+ monitores)
- **VPS**: 4 vCPU, 4GB RAM, 80GB SSD
- **Custo**: ~R$ 150-200/mês
- **Migrar**: SQLite → PostgreSQL/MySQL

## Riscos e Mitigações

### Risco 1: Performance com muitos monitores
**Mitigação**:
- Otimizações no código
- Cache agressivo
- Migração para PostgreSQL se necessário
- Distribuição de verificações

### Risco 2: Dependência de APIs externas
**Mitigação**:
- Tratamento de erros robusto
- Timeouts configuráveis
- Fallback graceful
- Logs detalhados

### Risco 3: Manutenção de integrações
**Mitigação**:
- Arquitetura de adapters
- Testes automatizados
- Documentação clara
- Versionamento de APIs

## Comparação com Alternativas

| Característica | ISP Status Page | StatusPage.io | Cachet | Statusfy |
|---------------|-----------------|---------------|---------|-----------|
| **Custo** | Grátis | $99-399/mês | Grátis | Grátis |
| **Integração IXC** | ✅ Nativa | ❌ | ❌ | ❌ |
| **Integração Zabbix** | ✅ Nativa | ⚠️ Webhook | ⚠️ Manual | ❌ |
| **Self-hosted** | ✅ | ⚠️ Opcional | ✅ | ✅ |
| **Simplicidade** | ✅✅ | ✅✅✅ | ✅ | ✅ |
| **Customização** | ✅✅ | ⚠️ Limitada | ✅✅ | ✅ |
| **Suporte** | Comunidade | Oficial | Comunidade | Comunidade |
| **Manutenção** | Ativa | Ativa | Média | Baixa |

## Roadmap Público

### Q1 2025
- [ ] MVP completo
- [ ] Integração IXC
- [ ] Integração Zabbix
- [ ] Release 1.0

### Q2 2025
- [ ] WhatsApp integration
- [ ] Telegram Bot
- [ ] Dashboard avançado
- [ ] Release 1.1

### Q3 2025
- [ ] SMS Gateway
- [ ] Multi-idiomas
- [ ] SLA tracking
- [ ] Release 2.0

### Q4 2025
- [ ] API pública completa
- [ ] Mobile app (opcional)
- [ ] Integrações adicionais

## Conclusão

O ISP Status Page é uma solução completa, focada e acessível para provedores de internet brasileiros. Com arquitetura simples, integrações nativas e código aberto, oferece alternativa viável a soluções pagas mantendo qualidade e confiabilidade.

## Próximos Passos

1. **Para Desenvolvedores**: Ver [DEVELOPMENT_PLAN.md](DEVELOPMENT_PLAN.md) e [TASKS.md](TASKS.md)
2. **Para Contribuidores**: Ver [CONTRIBUTING.md](../CONTRIBUTING.md)
3. **Para Usuários**: Aguardar release 1.0 ou testar versão dev

## Contato

- **GitHub**: https://github.com/seu-usuario/isp_status_page
- **Issues**: GitHub Issues
- **Email**: (a definir)
- **Licença**: Apache 2.0

---

**Versão do documento**: 1.0
**Última atualização**: Outubro 2024
**Status do projeto**: 🚧 Em desenvolvimento inicial
