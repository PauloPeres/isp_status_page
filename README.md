# ISP Status Page

Sistema de monitoramento e página de status para provedores de internet (ISPs).

## Visão Geral

Este projeto é uma solução completa para provedores de internet monitorarem seus serviços e exibirem o status em tempo real para seus clientes. O sistema integra-se com plataformas de gestão como IXC, Zabbix e outras APIs REST para criar fluxos de monitoramento automatizados.

## Características Principais

- **Monitoramento Automatizado**: Cron job executando a cada 30 segundos
- **Integração com Sistemas de Gestão**: IXC, Zabbix, APIs REST
- **Página de Status Pública**: Interface visual mostrando status de cada serviço
- **Sistema de Alertas**: Notificações por email (WhatsApp, SMS, Telegram em roadmap)
- **Resposta HTTP Inteligente**: Retorna códigos de erro quando serviços estão fora
- **Painel Administrativo**: Gerenciamento completo via interface web
- **Sistema de Assinaturas**: Permite usuários se inscreverem para receber notificações

## Stack Tecnológica

- **Framework**: CakePHP (última versão estável)
- **Banco de Dados**: SQLite (simplicidade e portabilidade)
- **Frontend**: CakePHP Views + Bootstrap/Tailwind
- **Background Jobs**: Cron + CakePHP Shell/Command

## Status do Projeto

🚧 **Em Desenvolvimento Inicial**

## Documentação

- [Arquitetura do Sistema](docs/ARCHITECTURE.md)
- [Estrutura de Banco de Dados](docs/DATABASE.md)
- [Integrações com APIs](docs/API_INTEGRATIONS.md)
- [Plano de Desenvolvimento](docs/DEVELOPMENT_PLAN.md)
- [Tarefas para Desenvolvimento Paralelo](docs/TASKS.md)

## Instalação

```bash
# Instruções serão adicionadas durante o desenvolvimento
```

## Contribuindo

Este é um projeto open source sob licença Apache 2.0. Contribuições são bem-vindas!

## Roadmap

### Fase 1 - MVP (Em Desenvolvimento)
- [ ] Estrutura básica do CakePHP
- [ ] Banco de dados SQLite
- [ ] CRUD de monitores
- [ ] Sistema de verificação via cron
- [ ] Página de status pública
- [ ] Sistema de emails

### Fase 2 - Integrações
- [ ] Integração com IXC
- [ ] Integração com Zabbix
- [ ] API REST genérica para outros sistemas

### Fase 3 - Alertas Avançados
- [ ] Integração com WhatsApp
- [ ] Integração com Telegram
- [ ] SMS via gateway
- [ ] Sistema de telefonia

### Fase 4 - Melhorias
- [ ] Dashboard com gráficos
- [ ] Histórico de incidentes
- [ ] SLA tracking
- [ ] Multi-idiomas

## Licença

Apache License 2.0 - Veja [LICENSE](LICENSE) para mais detalhes.

## Autores

Paulo e comunidade open source

## Suporte

Para questões e suporte, abra uma issue no GitHub.
