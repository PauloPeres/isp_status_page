# Plano de Desenvolvimento Modular

## Visão Geral

Este documento define a estratégia de desenvolvimento do ISP Status Page dividida em módulos independentes que podem ser desenvolvidos em paralelo por múltiplos agentes/desenvolvedores.

## Princípios de Modularização

1. **Baixo Acoplamento**: Módulos devem ter mínima dependência entre si
2. **Alta Coesão**: Cada módulo tem responsabilidade clara e única
3. **Contratos Definidos**: Interfaces bem definidas entre módulos
4. **Testabilidade**: Cada módulo deve ser testável independentemente
5. **Documentação**: Cada módulo deve ter documentação própria

## Fases de Desenvolvimento

### Fase 0: Setup Inicial
**Duração Estimada**: 2 dias
**Dependências**: Nenhuma
**Responsável**: 1 pessoa

- Instalação do CakePHP
- Configuração do ambiente
- Setup do SQLite
- Configuração de CI/CD básico
- Estrutura de testes

### Fase 1: Fundação (Parallel Development)
**Duração Estimada**: 5-7 dias
**Módulos Independentes**: 5

#### Módulo 1.1: Autenticação e Usuários
**Prioridade**: Alta
**Dependências**: Fase 0
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- Migration de `users`
- Model e Entity `User`
- Controller `UsersController` (CRUD)
- Sistema de login/logout
- Middleware de autenticação
- Telas de login/registro
- Seed de usuário admin padrão

**Testes**:
- Unit tests para User model
- Integration tests para autenticação
- Functional tests para login/logout

**Arquivos a criar**:
```
src/Model/Entity/User.php
src/Model/Table/UsersTable.php
src/Controller/UsersController.php
src/Middleware/AuthenticationMiddleware.php
templates/Users/login.php
templates/Users/register.php
tests/TestCase/Model/Table/UsersTableTest.php
tests/TestCase/Controller/UsersControllerTest.php
```

#### Módulo 1.2: Sistema de Configurações
**Prioridade**: Média
**Dependências**: Fase 0
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- Migration de `settings`
- Model `Setting`
- Service `SettingService` com cache
- Controller `SettingsController` (Admin)
- Interface no admin para gerenciar settings
- Seed com configurações padrão
- Helper para acessar settings nas views

**Testes**:
- Unit tests para SettingService
- Tests para cache de settings

**Arquivos a criar**:
```
src/Model/Entity/Setting.php
src/Model/Table/SettingsTable.php
src/Service/SettingService.php
src/Controller/Admin/SettingsController.php
templates/Admin/Settings/index.php
tests/TestCase/Service/SettingServiceTest.php
```

#### Módulo 1.3: Layout e UI Base
**Prioridade**: Alta
**Dependências**: Fase 0
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- Layout base do admin
- Layout base da página pública
- Sistema de assets (CSS/JS)
- Integração com framework CSS (Bootstrap/Tailwind)
- Componentes reutilizáveis (alerts, modals, tables)
- Navegação e menus
- Footer com informações

**Tecnologias**:
- Tailwind CSS (recomendado) ou Bootstrap
- Alpine.js para interatividade leve
- Iconografia (Heroicons ou Font Awesome)

**Arquivos a criar**:
```
templates/layout/default.php
templates/layout/admin.php
templates/element/navbar.php
templates/element/footer.php
templates/element/sidebar.php
webroot/css/app.css
webroot/js/app.js
```

#### Módulo 1.4: Database e Migrations Base
**Prioridade**: Alta
**Dependências**: Fase 0
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- Migration para todas as tabelas base
- Seeds para dados iniciais
- Script de setup do banco
- Documentação de schema

**Migrations a criar**:
```
1_CreateUsers.php
2_CreateSettings.php
3_CreateMonitors.php
4_CreateMonitorChecks.php
5_CreateIncidents.php
6_CreateSubscribers.php
7_CreateSubscriptions.php
8_CreateIntegrations.php
9_CreateIntegrationLogs.php
10_CreateAlertRules.php
11_CreateAlertLogs.php
```

#### Módulo 1.5: Infraestrutura de Testes
**Prioridade**: Média
**Dependências**: Fase 0
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- Configuração de PHPUnit
- Fixtures para todas as tabelas
- Factories para criar dados de teste
- Helpers de teste
- CI/CD configuration
- Coverage reports

**Arquivos a criar**:
```
tests/Fixture/*.php
tests/Factory/*.php
tests/bootstrap.php
phpunit.xml.dist
.github/workflows/tests.yml
```

### Fase 2: Core Features (Parallel Development)
**Duração Estimada**: 10-14 dias
**Módulos Independentes**: 6

#### Módulo 2.1: CRUD de Monitores
**Prioridade**: Alta
**Dependências**: 1.1, 1.4
**Pode ser desenvolvido em paralelo**: ✓ (com 2.2, 2.4, 2.5, 2.6)

**Entregas**:
- Model `Monitor` com validações
- Controller `MonitorsController` (CRUD completo)
- Views para listar, criar, editar, deletar
- Suporte para diferentes tipos de monitor
- Validação de configurações por tipo
- Interface para configurar check_interval

**Tipos de Monitor (MVP)**:
- HTTP/HTTPS
- Ping
- Port Check

**Arquivos a criar**:
```
src/Model/Entity/Monitor.php
src/Model/Table/MonitorsTable.php
src/Controller/Admin/MonitorsController.php
templates/Admin/Monitors/index.php
templates/Admin/Monitors/add.php
templates/Admin/Monitors/edit.php
templates/Admin/Monitors/view.php
tests/TestCase/Model/Table/MonitorsTableTest.php
```

#### Módulo 2.2: Motor de Verificação (Check Engine)
**Prioridade**: Alta
**Dependências**: 1.4, 2.1
**Pode ser desenvolvido em paralelo**: ✓ (com 2.1 após models prontos)

**Entregas**:
- Service `CheckService` para executar verificações
- Implementação de checkers por tipo:
  - `HttpChecker`
  - `PingChecker`
  - `PortChecker`
- Registro de resultados em `monitor_checks`
- Cálculo de métricas (response time, uptime)
- Command `MonitorCheckCommand` para cron
- Gestão de janelas de verificação

**Arquivos a criar**:
```
src/Service/Check/CheckService.php
src/Service/Check/CheckerInterface.php
src/Service/Check/HttpChecker.php
src/Service/Check/PingChecker.php
src/Service/Check/PortChecker.php
src/Command/MonitorCheckCommand.php
tests/TestCase/Service/Check/CheckServiceTest.php
tests/TestCase/Command/MonitorCheckCommandTest.php
```

**Cron Configuration**:
```bash
* * * * * cd /path/to/app && bin/cake monitor_check >> /dev/null 2>&1
```

#### Módulo 2.3: Sistema de Incidentes
**Prioridade**: Alta
**Dependências**: 1.4, 2.1, 2.2
**Pode ser desenvolvido em paralelo**: Parcial (após 2.2 ter estrutura)

**Entregas**:
- Model `Incident`
- Service `IncidentService`
- Auto-criação de incidentes quando status muda para DOWN
- Auto-resolução quando status volta para UP
- CRUD de incidentes no admin
- Timeline de incidentes
- Cálculo de duração

**Arquivos a criar**:
```
src/Model/Entity/Incident.php
src/Model/Table/IncidentsTable.php
src/Service/IncidentService.php
src/Controller/Admin/IncidentsController.php
templates/Admin/Incidents/index.php
templates/Admin/Incidents/view.php
```

#### Módulo 2.4: Página de Status Pública
**Prioridade**: Alta
**Dependências**: 1.3, 1.4, 2.1
**Pode ser desenvolvido em paralelo**: ✓ (com todos exceto 2.3)

**Entregas**:
- Controller `StatusController` (público)
- View da página de status
- Lógica de código HTTP baseado em status
- Cache de 30 segundos
- UI mostrando todos os monitores e seus status
- Indicadores visuais (verde/amarelo/vermelho)
- Uptime percentages
- Últimos incidentes
- Subscribe form

**Comportamento HTTP**:
- 200: Todos os serviços UP
- 503: Algum serviço DOWN

**Arquivos a criar**:
```
src/Controller/StatusController.php
templates/Status/index.php
templates/Status/components/monitor-item.php
webroot/css/status-page.css
```

#### Módulo 2.5: Sistema de Subscribers
**Prioridade**: Média
**Dependências**: 1.4
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- Models `Subscriber` e `Subscription`
- Controllers para inscrição/cancelamento
- Sistema de verificação de email
- Token de unsubscribe
- Interface no admin para ver subscribers
- Página pública de subscribe

**Arquivos a criar**:
```
src/Model/Entity/Subscriber.php
src/Model/Table/SubscribersTable.php
src/Model/Entity/Subscription.php
src/Model/Table/SubscriptionsTable.php
src/Controller/SubscribersController.php
src/Controller/Admin/SubscribersController.php
templates/Subscribers/subscribe.php
templates/Subscribers/verify.php
templates/Subscribers/unsubscribe.php
```

#### Módulo 2.6: Sistema de Alertas (Email)
**Prioridade**: Alta
**Dependências**: 1.2, 1.4, 2.3, 2.5
**Pode ser desenvolvido em paralelo**: Parcial (após models prontos)

**Entregas**:
- Model `AlertRule` e `AlertLog`
- Service `AlertService`
- Implementação de `EmailAlertChannel`
- Templates de email
- Configuração SMTP
- Throttling de alertas
- Integração com IncidentService

**Arquivos a criar**:
```
src/Model/Entity/AlertRule.php
src/Model/Table/AlertRulesTable.php
src/Model/Entity/AlertLog.php
src/Model/Table/AlertLogsTable.php
src/Service/Alert/AlertService.php
src/Service/Alert/ChannelInterface.php
src/Service/Alert/EmailAlertChannel.php
templates/email/html/incident_down.php
templates/email/html/incident_up.php
templates/email/text/incident_down.php
templates/email/text/incident_up.php
```

### Fase 3: Integrações (Parallel Development)
**Duração Estimada**: 7-10 dias
**Módulos Independentes**: 3

#### Módulo 3.1: Integração IXC
**Prioridade**: Alta
**Dependências**: 1.4, 2.1, 2.2
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- `IxcAdapter` e `IxcClient`
- CRUD de integração IXC no admin
- Teste de conexão
- Implementação de checkers IXC:
  - Service Status
  - Equipment Status
  - Critical Tickets
- Documentação específica

**Arquivos a criar**:
```
src/Integration/IntegrationInterface.php
src/Integration/AbstractIntegration.php
src/Integration/Ixc/IxcAdapter.php
src/Integration/Ixc/IxcClient.php
src/Integration/Ixc/IxcMapper.php
src/Service/Check/IxcServiceChecker.php
src/Service/Check/IxcEquipmentChecker.php
tests/TestCase/Integration/Ixc/IxcAdapterTest.php
```

#### Módulo 3.2: Integração Zabbix
**Prioridade**: Alta
**Dependências**: 1.4, 2.1, 2.2
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- `ZabbixAdapter` e `ZabbixClient`
- CRUD de integração Zabbix no admin
- Teste de conexão
- Implementação de checkers Zabbix:
  - Host Status
  - Active Triggers
  - Metric Value
- Documentação específica

**Arquivos a criar**:
```
src/Integration/Zabbix/ZabbixAdapter.php
src/Integration/Zabbix/ZabbixClient.php
src/Integration/Zabbix/ZabbixMapper.php
src/Service/Check/ZabbixHostChecker.php
src/Service/Check/ZabbixTriggerChecker.php
src/Service/Check/ZabbixMetricChecker.php
tests/TestCase/Integration/Zabbix/ZabbixAdapterTest.php
```

#### Módulo 3.3: Integração REST API Genérica
**Prioridade**: Média
**Dependências**: 1.4, 2.1, 2.2
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- `RestApiAdapter`
- UI para configurar APIs customizadas
- Validadores:
  - Status Code
  - Content
  - JSON Path
  - Response Time
- Documentação de uso

**Arquivos a criar**:
```
src/Integration/RestApi/RestApiAdapter.php
src/Integration/RestApi/RestApiClient.php
src/Service/Check/RestApiChecker.php
templates/Admin/Integrations/rest_api_form.php
```

### Fase 4: Melhorias e Polimento (Parallel Development)
**Duração Estimada**: 5-7 dias
**Módulos Independentes**: 5

#### Módulo 4.1: Dashboard Admin
**Prioridade**: Média
**Dependências**: 2.1, 2.2, 2.3
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- Controller `DashboardController`
- View com estatísticas
- Gráficos:
  - Uptime por monitor
  - Incidentes no último mês
  - Response times
- Últimas verificações
- Incidentes ativos

**Arquivos a criar**:
```
src/Controller/Admin/DashboardController.php
templates/Admin/Dashboard/index.php
webroot/js/charts.js
```

#### Módulo 4.2: Histórico e Relatórios
**Prioridade**: Baixa
**Dependências**: 2.2, 2.3
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- View de histórico de checks
- Filtros por período, monitor, status
- Export para CSV
- Relatório de uptime mensal
- Gráfico de response times

**Arquivos a criar**:
```
src/Controller/Admin/ReportsController.php
templates/Admin/Reports/checks.php
templates/Admin/Reports/uptime.php
```

#### Módulo 4.3: Manutenção e Limpeza
**Prioridade**: Média
**Dependências**: 1.4
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- Command `CleanupCommand`
- Limpeza de checks antigos
- Limpeza de logs
- Vacuum SQLite
- Backup automático
- Cron configuration

**Arquivos a criar**:
```
src/Command/CleanupCommand.php
src/Command/BackupCommand.php
bin/backup.sh
```

#### Módulo 4.4: API Pública
**Prioridade**: Baixa
**Dependências**: 2.1, 2.3
**Pode ser desenvolvido em paralelo**: ✓

**Entregas**:
- Controller `ApiController`
- Endpoints:
  - GET /api/status
  - GET /api/monitors
  - GET /api/incidents
- Autenticação por API key
- Rate limiting
- Documentação da API

**Arquivos a criar**:
```
src/Controller/Api/StatusController.php
src/Controller/Api/MonitorsController.php
src/Controller/Api/IncidentsController.php
src/Middleware/ApiAuthMiddleware.php
docs/API.md
```

#### Módulo 4.5: Testes End-to-End
**Prioridade**: Média
**Dependências**: Todas as fases anteriores
**Pode ser desenvolvido em paralelo**: Parcial

**Entregas**:
- Testes E2E com Playwright/Cypress
- Cenários principais:
  - Login e navegação admin
  - Criar monitor e verificar status
  - Simular incidente
  - Subscribe e verificar email
- CI/CD com testes E2E

**Arquivos a criar**:
```
tests/e2e/admin-login.spec.js
tests/e2e/create-monitor.spec.js
tests/e2e/status-page.spec.js
tests/e2e/subscribe.spec.js
```

## Estratégia de Branches

### Branch Principal
- `main`: Código estável, sempre deployável

### Branches de Feature
- `feature/module-1.1-auth`: Módulo 1.1
- `feature/module-1.2-settings`: Módulo 1.2
- etc.

### Workflow
1. Criar branch a partir de `main`
2. Desenvolver módulo
3. Testes locais passando
4. Abrir Pull Request
5. Code review
6. Merge para `main`

## Dependências entre Módulos

### Grafo de Dependências

```
Fase 0 (Setup)
    │
    ├──► 1.1 (Auth) ──────────────────┐
    ├──► 1.2 (Settings) ──────────────┤
    ├──► 1.3 (UI) ────────────────────┤
    ├──► 1.4 (Database) ──────────────┼──► 2.1 (Monitors) ──┐
    └──► 1.5 (Tests) ─────────────────┘                     │
                                                             │
                  ┌──────────────────────────────────────────┤
                  │                                          │
    2.2 (Check Engine) ◄───────┘                            │
                  │                                          │
                  ├──► 2.3 (Incidents) ──────────────────────┤
                  │                                          │
    2.4 (Status Page) ◄─────────────────────────────────────┤
                  │                                          │
    2.5 (Subscribers) ◄─────────────────────────────────────┤
                  │                                          │
    2.6 (Alerts) ◄──────────────────────────────────────────┘
                  │
                  ├──► 3.1 (IXC)
                  ├──► 3.2 (Zabbix)
                  └──► 3.3 (REST API)
                       │
                       ├──► 4.1 (Dashboard)
                       ├──► 4.2 (Reports)
                       ├──► 4.3 (Maintenance)
                       ├──► 4.4 (API)
                       └──► 4.5 (E2E Tests)
```

## Checklist por Módulo

Cada módulo deve ser considerado completo quando:

- [ ] Código implementado conforme especificação
- [ ] Testes unitários escritos e passando
- [ ] Testes de integração escritos e passando (se aplicável)
- [ ] Documentação inline (PHPDoc)
- [ ] README específico do módulo (se necessário)
- [ ] Code review aprovado
- [ ] CI/CD passando
- [ ] Sem warnings de linter/static analysis

## Ferramentas e Padrões

### Ferramentas de Desenvolvimento
- **IDE**: VSCode, PHPStorm
- **Linter**: PHP_CodeSniffer (PSR-12)
- **Static Analysis**: PHPStan (level 8)
- **Testes**: PHPUnit 9+
- **Coverage**: 80%+ desejável
- **Git Hooks**: Pre-commit para linting

### Padrões de Código
- PSR-12 para código PHP
- CakePHP conventions para naming
- RESTful para APIs
- Semantic versioning

### Comunicação
- Issues no GitHub para cada módulo
- Pull Requests com descrição detalhada
- Code reviews obrigatórios
- Daily standups (opcional)

## Timeline Geral

### Semana 1
- Setup inicial (Fase 0)
- Início da Fase 1

### Semana 2
- Conclusão da Fase 1
- Início da Fase 2

### Semana 3-4
- Desenvolvimento da Fase 2
- Início da Fase 3 (integrações)

### Semana 5
- Conclusão da Fase 3
- Início da Fase 4

### Semana 6
- Conclusão da Fase 4
- Testes finais
- Documentação

### Semana 7
- Buffer para ajustes
- Preparação para release

**Total: ~7 semanas para MVP completo**

## Distribuição de Trabalho Sugerida

### Para 3 Desenvolvedores

**Dev 1 - Backend Core**:
- Módulos: 1.1, 1.2, 1.4, 2.1, 2.2, 2.3

**Dev 2 - Frontend & UI**:
- Módulos: 1.3, 2.4, 2.5, 4.1, 4.2

**Dev 3 - Integrações & Alertas**:
- Módulos: 2.6, 3.1, 3.2, 3.3, 4.3

### Para 5 Desenvolvedores

**Dev 1 - Auth & Settings**:
- Módulos: 1.1, 1.2, 1.5

**Dev 2 - Monitors & Checks**:
- Módulos: 1.4, 2.1, 2.2

**Dev 3 - Frontend**:
- Módulos: 1.3, 2.4, 4.1

**Dev 4 - Incidents & Alerts**:
- Módulos: 2.3, 2.5, 2.6

**Dev 5 - Integrations**:
- Módulos: 3.1, 3.2, 3.3, 4.3

## Próximos Passos Imediatos

1. Criar issues no GitHub para cada módulo
2. Atribuir responsáveis
3. Executar Fase 0 (Setup)
4. Kickoff da Fase 1
5. Daily syncs para alinhamento

## Recursos Adicionais

- [CakePHP Documentation](https://book.cakephp.org)
- [SQLite Documentation](https://www.sqlite.org/docs.html)
- [PHPUnit Documentation](https://phpunit.de)
- [PSR-12 Coding Standard](https://www.php-fig.org/psr/psr-12/)
