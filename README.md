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

- **Framework**: CakePHP 5.2.9
- **PHP**: 8.4.14
- **Banco de Dados**: SQLite (com suporte a MySQL/PostgreSQL)
- **Frontend**: HTML5 + CSS Variables (Design System próprio)
- **Autenticação**: cakephp/authentication 3.3.2
- **Testes**: PHPUnit 10.5.58
- **Docker**: Multi-stage builds (dev + production)

## Status do Projeto

🚀 **MVP em Desenvolvimento Ativo** - 65% Completo

### ✅ Funcionalidades Implementadas

#### Autenticação & Segurança
- ✅ Sistema de login/logout completo
- ✅ Proteção de rotas com Authentication
- ✅ Hash de senhas com bcrypt
- ✅ CSRF protection
- ✅ Session management

#### Painel Administrativo
- ✅ Layout responsivo com sidebar
- ✅ Dashboard com estatísticas em tempo real
- ✅ Menu de navegação organizado
- ✅ Design system oficial implementado
- ✅ Mobile-friendly

#### Gerenciamento de Monitores
- ✅ CRUD completo de monitores
- ✅ Suporte a 3 tipos: HTTP, Ping, Port
- ✅ Formulários dinâmicos por tipo
- ✅ Filtros avançados (tipo, status, busca)
- ✅ Ativar/desativar monitores
- ✅ Estatísticas de uptime (24h)
- ✅ Histórico de verificações

#### Página de Status Pública
- ✅ Status em tempo real dos serviços
- ✅ Indicadores visuais (🟢 Online, 🔴 Offline)
- ✅ Auto-refresh a cada 30 segundos
- ✅ Códigos HTTP inteligentes (503/500)
- ✅ Histórico de incidentes
- ✅ Formulário de inscrição

#### Banco de Dados
- ✅ 11 migrations implementadas
- ✅ 11 models com validações
- ✅ Seeds para desenvolvimento
- ✅ Relacionamentos configurados

#### Testes
- ✅ 36+ testes de integração
- ✅ Fixtures para testes
- ✅ Coverage tools configurados
- ✅ CI/CD ready

#### DevOps
- ✅ Docker completo (dev + prod)
- ✅ Makefile com 30+ comandos
- ✅ Multi-database support
- ✅ Hot reload em desenvolvimento

### 🚧 Em Desenvolvimento

- 🚧 Cron job de verificação (30s)
- 🚧 Sistema de incidentes
- 🚧 Alertas por email

### 📊 Progresso por Fase

**Fase 0** (Setup): 2/2 tarefas ✅ (100%)
**Fase 1** (MVP): 6/10 tarefas ✅ (60%)
**Fase 2** (Core): 2/8 tarefas ✅ (25%)

## Documentação

- [Arquitetura do Sistema](docs/ARCHITECTURE.md)
- [Estrutura de Banco de Dados](docs/DATABASE.md)
- [Integrações com APIs](docs/API_INTEGRATIONS.md)
- [Plano de Desenvolvimento](docs/DEVELOPMENT_PLAN.md)
- [Tarefas para Desenvolvimento Paralelo](docs/TASKS.md)
- [Design System e Paleta de Cores](docs/DESIGN.md) ✨
- [Estratégia de Testes](docs/TESTING.md) 🧪

## Instalação

### Opção 1: Docker (Recomendado)

```bash
# Quick start - Um comando para rodar tudo!
make quick-start

# Ou manualmente
docker-compose up -d
```

Acesse: http://localhost:8765

### Opção 2: Manual

```bash
cd src
composer install
cp config/app_local.example.php config/app_local.php
# Editar config/app_local.php
bin/cake migrations migrate
bin/cake server
```

Ver [docs/INSTALL.md](docs/INSTALL.md) e [docs/DOCKER.md](docs/DOCKER.md) para instruções completas.

## 🧪 Testes

```bash
# Executar todos os testes
make test

# Testes de controllers
make test-controllers

# Teste específico
make test-specific FILE=UsersControllerTest

# Coverage HTML
make test-coverage
```

**Coverage Atual**: 36+ testes implementados

Ver [docs/TESTING.md](docs/TESTING.md) para detalhes completos.

## 📸 Screenshots

### Painel Administrativo
- Dashboard com estatísticas em tempo real
- Gestão completa de monitores
- Interface responsiva e moderna

### Página de Status Pública
- Status em tempo real dos serviços
- Histórico de incidentes
- Inscrição para notificações

### Sistema de Monitores
- CRUD completo com filtros avançados
- Formulários dinâmicos por tipo (HTTP/Ping/Port)
- Estatísticas de uptime e performance

## 🎨 Design System

O projeto utiliza um design system próprio com paleta de cores profissional:

- **Azul Principal**: `#1E88E5` - Botões, links, headers
- **Verde Sucesso**: `#43A047` - Status online, ações positivas
- **Vermelho Erro**: `#E53935` - Status offline, erros críticos
- **Amarelo Alerta**: `#FDD835` - Avisos, degradação parcial

Ver [docs/DESIGN.md](docs/DESIGN.md) para guidelines completos.

## 📝 Comandos Úteis

```bash
# Desenvolvimento
make dev                # Inicia ambiente de desenvolvimento
make logs               # Ver logs em tempo real
make shell              # Acessa shell do container

# Banco de Dados
make migrate            # Executa migrations
make seed               # Popula banco com dados de teste
make db-reset           # Reset completo (⚠️ apaga tudo)

# Testes
make test               # Todos os testes
make test-coverage      # Coverage HTML

# Qualidade de Código
make cs-check           # Verifica padrões
make cs-fix             # Corrige automaticamente
```

## 🏗️ Arquitetura

```
isp_status_page/
├── src/                    # CakePHP application
│   ├── src/
│   │   ├── Controller/     # Controllers (Users, Admin, Monitors, Status)
│   │   ├── Model/          # Models & Tables (11 models)
│   │   ├── Service/        # Services (Settings, etc)
│   │   └── View/           # AppView
│   ├── templates/          # Views
│   │   ├── layout/         # Layouts (admin, public)
│   │   ├── element/        # Componentes reutilizáveis
│   │   ├── Monitors/       # CRUD de monitores
│   │   └── Status/         # Página pública
│   ├── config/
│   │   └── Migrations/     # 11 migrations
│   ├── tests/              # 36+ testes
│   └── webroot/
│       └── css/            # admin.css, public.css
├── docs/                   # Documentação completa
├── docker/                 # Configurações Docker
└── Makefile               # 30+ comandos úteis
```

## 🤝 Contribuindo

Este é um projeto open source sob licença Apache 2.0. Contribuições são bem-vindas!

### Como Contribuir

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

### Guidelines

- Siga o [Design System](docs/DESIGN.md)
- Adicione testes para novas features
- Mantenha coverage acima de 75%
- Use mensagens de commit descritivas
- Documente alterações no TASKS.md

## Roadmap

### Fase 1 - MVP ✅ 60% Completo
- [x] Estrutura básica do CakePHP
- [x] Banco de dados SQLite
- [x] CRUD de monitores
- [x] Página de status pública
- [x] Sistema de autenticação
- [x] Painel administrativo
- [ ] Sistema de verificação via cron (🚧 próximo)
- [ ] Sistema de emails
- [ ] CRUD de incidentes
- [ ] Sistema de assinantes

### Fase 2 - Core Features ⏳ 25% Completo
- [x] Layout admin responsivo
- [x] Layout público responsivo
- [ ] Cron job de monitoramento
- [ ] Detecção automática de incidentes
- [ ] Sistema de alertas (Email)
- [ ] Dashboard com métricas
- [ ] Histórico de incidentes
- [ ] API REST básica

### Fase 3 - Integrações 🔜
- [ ] Integração com IXC Soft
- [ ] Integração com Zabbix
- [ ] API REST genérica
- [ ] Webhooks para sistemas externos

### Fase 4 - Alertas Avançados 🔜
- [ ] Integração com WhatsApp Business API
- [ ] Integração com Telegram Bot
- [ ] SMS via gateway (Twilio, Nexmo)
- [ ] Notificações push

### Fase 5 - Analytics & Melhorias 🔜
- [ ] Dashboard com gráficos (Chart.js)
- [ ] SLA tracking e relatórios
- [ ] Exportação de dados (CSV, PDF)
- [ ] Multi-idiomas (i18n)
- [ ] Temas personalizáveis
- [ ] API completa (REST + GraphQL)

## Licença

Apache License 2.0 - Veja [LICENSE](LICENSE) para mais detalhes.

## Autores

Paulo e comunidade open source

## Suporte

Para questões e suporte, abra uma issue no GitHub.
