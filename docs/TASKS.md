# Tarefas para Desenvolvimento Paralelo

Este documento lista tarefas específicas que podem ser executadas por diferentes agentes/desenvolvedores de forma independente.

## 📁 Estrutura do Projeto

**IMPORTANTE**: O projeto CakePHP está na pasta `/src`

```
isp_status_page/
├── src/              # 👈 Projeto CakePHP está aqui
│   ├── bin/          # Scripts CLI (bin/cake)
│   ├── config/       # Configurações
│   ├── src/          # Código da aplicação
│   ├── tests/        # Testes
│   └── database.db   # Banco SQLite
├── docs/             # Documentação
├── docker/           # Configs Docker
├── Dockerfile        # Build Docker
└── Makefile          # Comandos úteis
```

**Todos os comandos devem ser executados de dentro de `/src`** ou usando `make` na raiz.

## Como Usar Este Documento

1. Cada tarefa tem um ID único (ex: TASK-001)
2. Dependências são listadas claramente
3. Status: 🔴 Não iniciado | 🟡 Em progresso | 🟢 Completo
4. Prioridade: 🔥 Crítica | ⭐ Alta | 💡 Média | 📌 Baixa

## ✅ Tarefas Completas

**Fase 0**: TASK-000 ✅, TASK-001 ✅ (2/2 completas)

## Fase 0: Setup Inicial

### TASK-000: Setup do Projeto CakePHP
**Status**: 🟢 **COMPLETO** | **Prioridade**: 🔥 | **Dependências**: Nenhuma
**Estimativa**: 2h | **Tempo Real**: 2h

**Descrição**: Instalar e configurar o projeto CakePHP base.

**Ações Realizadas**:
```bash
# CakePHP instalado em /src via composer
cd src
composer create-project --prefer-dist cakephp/app:~5.0 .

# SQLite configurado em src/config/app_local.php
# Database criado: src/database.db
# Docker configurado para desenvolvimento
# Multi-database support adicionado (SQLite/MySQL/PostgreSQL)
```

**Arquivos modificados**:
- `src/config/app_local.php` - Configurado com SQLite e suporte multi-DB
- `src/database.db` - Criado
- `Dockerfile` - Adicionado
- `docker-compose.yml` - Adicionado
- `Makefile` - Adicionado com comandos úteis

**Estrutura do Projeto**:
- Projeto CakePHP está em `/src`
- Documentação em `/docs`
- Configuração Docker na raiz

**Critérios de Aceite**:
- [x] CakePHP 5.2.9 instalado em `/src`
- [x] SQLite configurado
- [x] Database file criado (`src/database.db`)
- [x] Servidor pode rodar com `cd src && bin/cake server` ou `make dev` (Docker)
- [x] Página inicial do CakePHP acessível em http://localhost:8765
- [x] Docker configurado com `make quick-start`
- [x] Multi-database support (SQLite/MySQL/PostgreSQL)

---

### TASK-001: Configurar Sistema de Testes
**Status**: 🟢 **COMPLETO** | **Prioridade**: ⭐ | **Dependências**: TASK-000
**Estimativa**: 3h | **Tempo Real**: 0h (já incluído no CakePHP)

**Descrição**: Configurar PHPUnit e estrutura de testes.

**Ações Realizadas**:
- CakePHP já vem com PHPUnit configurado
- Estrutura de testes já existe em `src/tests/`
- Fixtures, TestCase e bootstrap já configurados
- Coverage configurado em `phpunit.xml.dist`

**Arquivos existentes**:
- `src/tests/bootstrap.php` - ✅ Já existe
- `src/phpunit.xml.dist` - ✅ Já existe
- `src/tests/TestCase/ApplicationTest.php` - ✅ Já existe
- `src/tests/Fixture/` - ✅ Diretório criado

**Como usar**:
```bash
# Com Docker
make test

# Sem Docker
cd src
vendor/bin/phpunit

# Com coverage
make test-coverage
# ou
vendor/bin/phpunit --coverage-html tmp/coverage
```

**Critérios de Aceite**:
- [x] PHPUnit configurado (vem com CakePHP)
- [x] Testes executam com `vendor/bin/phpunit`
- [x] Coverage funcional
- [x] Makefile com comando `make test`

---

## Fase 1: Fundação

### TASK-100: Migration de Users
**Status**: 🔴 | **Prioridade**: 🔥 | **Dependências**: TASK-000
**Estimativa**: 1h

**Descrição**: Criar migration para tabela de usuários.

**Ações**:
```bash
# Com Docker
make shell
bin/cake bake migration CreateUsers

# Ou sem Docker
cd src
bin/cake bake migration CreateUsers
```

**Campos da tabela**:
- id (PK)
- username (unique)
- password (hash)
- email (unique)
- role (admin/user/viewer)
- active (boolean)
- last_login (datetime)
- created, modified

**Arquivos a criar**:
- `src/config/Migrations/YYYYMMDDHHMMSS_CreateUsers.php`

**Critérios de Aceite**:
- [ ] Migration criada em `src/config/Migrations/`
- [ ] `bin/cake migrations migrate` executa sem erros
- [ ] Tabela users existe no SQLite (`src/database.db`)

---

### TASK-101: User Model e Entity
**Status**: 🔴 | **Prioridade**: 🔥 | **Dependências**: TASK-100
**Estimativa**: 2h

**Descrição**: Criar Model e Entity de User com validações.

**Ações**:
```bash
bin/cake bake model Users
```

**Implementar**:
- Validações (username, email, password)
- Hash automático de senha
- Métodos auxiliares (isAdmin(), etc)

**Arquivos a criar**:
- `src/Model/Entity/User.php`
- `src/Model/Table/UsersTable.php`
- `tests/TestCase/Model/Table/UsersTableTest.php`
- `tests/Fixture/UsersFixture.php`

**Critérios de Aceite**:
- [ ] Model criado com validações
- [ ] Senha é hash automaticamente
- [ ] Testes unitários passando
- [ ] Fixture funcional

---

### TASK-102: Sistema de Autenticação
**Status**: 🔴 | **Prioridade**: 🔥 | **Dependências**: TASK-101
**Estimativa**: 4h

**Descrição**: Implementar sistema de login/logout usando CakePHP Authentication.

**Ações**:
```bash
composer require cakephp/authentication
```

**Implementar**:
- Configurar Authentication no Application.php
- Controller UsersController (login, logout)
- Views de login
- Middleware de autenticação
- Redirect para login quando não autenticado

**Arquivos a criar/modificar**:
- `src/Application.php`
- `src/Controller/UsersController.php`
- `templates/Users/login.php`
- `tests/TestCase/Controller/UsersControllerTest.php`

**Critérios de Aceite**:
- [ ] Login funcional
- [ ] Logout funcional
- [ ] Redirect automático para login
- [ ] Sessão persistente
- [ ] Testes de integração passando

---

### TASK-103: Seed de Usuário Admin
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-101
**Estimativa**: 1h

**Descrição**: Criar seed para usuário admin padrão.

**Ações**:
```bash
bin/cake bake seed Users
```

**Dados do seed**:
- username: admin
- password: admin123
- email: admin@localhost
- role: admin
- active: true

**Arquivos a criar**:
- `config/Seeds/UsersSeed.php`

**Critérios de Aceite**:
- [ ] Seed criado
- [ ] `bin/cake migrations seed` cria usuário admin
- [ ] Possível fazer login com credenciais padrão

---

### TASK-110: Migration de Settings
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-000
**Estimativa**: 1h

**Descrição**: Criar migration para tabela de configurações.

**Ver**: docs/DATABASE.md - Tabela settings

**Arquivos a criar**:
- `config/Migrations/YYYYMMDDHHMMSS_CreateSettings.php`

**Critérios de Aceite**:
- [ ] Migration criada e executada
- [ ] Tabela com índice em `key`

---

### TASK-111: Setting Model e Service
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-110
**Estimativa**: 3h

**Descrição**: Criar Model Setting e SettingService com cache.

**Implementar**:
- Model e Entity Setting
- SettingService com métodos:
  - `get(string $key, $default = null)`
  - `set(string $key, $value)`
  - `has(string $key)`
  - `all()`
- Cache de settings (cache engine)

**Arquivos a criar**:
- `src/Model/Entity/Setting.php`
- `src/Model/Table/SettingsTable.php`
- `src/Service/SettingService.php`
- `tests/TestCase/Service/SettingServiceTest.php`

**Critérios de Aceite**:
- [ ] CRUD de settings funcional
- [ ] Cache funcionando
- [ ] Testes unitários passando

---

### TASK-112: Settings Seed
**Status**: 🔴 | **Prioridade**: 💡 | **Dependências**: TASK-111
**Estimativa**: 1h

**Descrição**: Seed com configurações padrão do sistema.

**Settings padrão** (ver docs/DATABASE.md):
- site_name
- site_url
- email_from
- smtp_*
- default_check_interval
- etc.

**Arquivos a criar**:
- `config/Seeds/SettingsSeed.php`

**Critérios de Aceite**:
- [ ] Seed cria todas as configurações padrão
- [ ] Valores apropriados para desenvolvimento

---

### TASK-120: Layout Admin Base
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-000
**Estimativa**: 6h

**Descrição**: Criar layout base para painel administrativo.

**Implementar**:
- Layout `admin.php`
- Navbar com menu
- Sidebar (opcional)
- Footer
- Integração com Tailwind CSS ou Bootstrap
- JavaScript base (Alpine.js)

**Arquivos a criar**:
- `templates/layout/admin.php`
- `templates/element/admin/navbar.php`
- `templates/element/admin/sidebar.php`
- `templates/element/admin/footer.php`
- `webroot/css/admin.css`
- `webroot/js/admin.js`

**Critérios de Aceite**:
- [ ] Layout responsivo
- [ ] Navegação funcional
- [ ] Estilo consistente
- [ ] Mobile-friendly

---

### TASK-121: Layout Público Base
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-000
**Estimativa**: 4h

**Descrição**: Criar layout base para páginas públicas (status page).

**Implementar**:
- Layout `default.php`
- Header simples
- Footer
- Estilo focado em clareza e legibilidade

**Arquivos a criar**:
- `templates/layout/default.php`
- `templates/element/public/header.php`
- `templates/element/public/footer.php`
- `webroot/css/public.css`

**Critérios de Aceite**:
- [ ] Layout clean e profissional
- [ ] Responsivo
- [ ] Rápido carregamento

---

### TASK-130: Migrations de Monitors
**Status**: 🔴 | **Prioridade**: 🔥 | **Dependências**: TASK-000
**Estimativa**: 2h

**Descrição**: Criar migrations para tabelas monitors e monitor_checks.

**Ver**: docs/DATABASE.md

**Arquivos a criar**:
- `config/Migrations/YYYYMMDDHHMMSS_CreateMonitors.php`
- `config/Migrations/YYYYMMDDHHMMSS_CreateMonitorChecks.php`

**Critérios de Aceite**:
- [ ] Migrations executam sem erro
- [ ] Índices criados corretamente
- [ ] Foreign keys configuradas

---

### TASK-140: Migrations de Incidents
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-130
**Estimativa**: 1h

**Descrição**: Criar migration para tabela incidents.

**Ver**: docs/DATABASE.md

**Arquivos a criar**:
- `config/Migrations/YYYYMMDDHHMMSS_CreateIncidents.php`

**Critérios de Aceite**:
- [ ] Migration executa
- [ ] Foreign key para monitors

---

### TASK-150: Migrations de Subscribers
**Status**: 🔴 | **Prioridade**: 💡 | **Dependências**: TASK-130
**Estimativa**: 1h

**Descrição**: Criar migrations para subscribers e subscriptions.

**Ver**: docs/DATABASE.md

**Arquivos a criar**:
- `config/Migrations/YYYYMMDDHHMMSS_CreateSubscribers.php`
- `config/Migrations/YYYYMMDDHHMMSS_CreateSubscriptions.php`

**Critérios de Aceite**:
- [ ] Migrations executam
- [ ] Relacionamentos corretos

---

### TASK-160: Migrations de Integrações
**Status**: 🔴 | **Prioridade**: 💡 | **Dependências**: TASK-130
**Estimativa**: 1h

**Descrição**: Criar migrations para integrations e integration_logs.

**Ver**: docs/DATABASE.md

**Arquivos a criar**:
- `config/Migrations/YYYYMMDDHHMMSS_CreateIntegrations.php`
- `config/Migrations/YYYYMMDDHHMMSS_CreateIntegrationLogs.php`

**Critérios de Aceite**:
- [ ] Migrations executam
- [ ] Relacionamento correto

---

### TASK-170: Migrations de Alertas
**Status**: 🔴 | **Prioridade**: 💡 | **Dependências**: TASK-130, TASK-140
**Estimativa**: 1h

**Descrição**: Criar migrations para alert_rules e alert_logs.

**Ver**: docs/DATABASE.md

**Arquivos a criar**:
- `config/Migrations/YYYYMMDDHHMMSS_CreateAlertRules.php`
- `config/Migrations/YYYYMMDDHHMMSS_CreateAlertLogs.php`

**Critérios de Aceite**:
- [ ] Migrations executam
- [ ] Foreign keys corretas

---

## Fase 2: Core Features

### TASK-200: Monitor Model e Entity
**Status**: 🔴 | **Prioridade**: 🔥 | **Dependências**: TASK-130
**Estimativa**: 3h

**Descrição**: Criar Model Monitor com validações e lógica.

**Implementar**:
- Validações de campos
- Validação de configuration JSON por tipo
- Associações com MonitorChecks, Incidents
- Métodos auxiliares (isUp(), getUptimePercentage())

**Arquivos a criar**:
- `src/Model/Entity/Monitor.php`
- `src/Model/Table/MonitorsTable.php`
- `tests/TestCase/Model/Table/MonitorsTableTest.php`
- `tests/Fixture/MonitorsFixture.php`

**Critérios de Aceite**:
- [ ] Validações funcionando
- [ ] JSON configuration validado por tipo
- [ ] Associações corretas
- [ ] Testes passando

---

### TASK-201: MonitorsController - CRUD
**Status**: 🔴 | **Prioridade**: 🔥 | **Dependências**: TASK-200, TASK-120
**Estimativa**: 5h

**Descrição**: Implementar CRUD completo de monitores no admin.

**Ações**:
```bash
bin/cake bake controller Monitors --prefix Admin
```

**Implementar**:
- index: Listar todos os monitores
- view: Ver detalhes de um monitor
- add: Criar novo monitor (form com tipos diferentes)
- edit: Editar monitor
- delete: Deletar monitor
- toggle: Ativar/desativar

**Arquivos a criar**:
- `src/Controller/Admin/MonitorsController.php`
- `templates/Admin/Monitors/index.php`
- `templates/Admin/Monitors/view.php`
- `templates/Admin/Monitors/add.php`
- `templates/Admin/Monitors/edit.php`
- `tests/TestCase/Controller/Admin/MonitorsControllerTest.php`

**Critérios de Aceite**:
- [ ] CRUD completo funcional
- [ ] Form adapta-se ao tipo de monitor
- [ ] Validações no frontend e backend
- [ ] Mensagens de feedback apropriadas
- [ ] Testes de integração passando

---

### TASK-202: Forms Dinâmicos por Tipo de Monitor
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-201
**Estimativa**: 4h

**Descrição**: Criar forms que mudam baseado no tipo de monitor selecionado.

**Tipos**:
- HTTP: URL, method, headers, expected_status
- Ping: Host, packet_count, max_latency
- Port: Host, port, protocol

**Implementar**:
- JavaScript para mostrar/ocultar campos
- Validação frontend
- Componentes reutilizáveis

**Arquivos a criar/modificar**:
- `templates/Admin/Monitors/add.php`
- `templates/Admin/Monitors/edit.php`
- `webroot/js/monitor-form.js`
- `templates/element/monitor/form_http.php`
- `templates/element/monitor/form_ping.php`
- `templates/element/monitor/form_port.php`

**Critérios de Aceite**:
- [ ] Form muda dinamicamente
- [ ] Validações adequadas por tipo
- [ ] UX intuitiva

---

### TASK-210: Check Service - Interface e Abstract
**Status**: 🔴 | **Prioridade**: 🔥 | **Dependências**: TASK-200
**Estimativa**: 2h

**Descrição**: Criar interface e classe abstrata para checkers.

**Ver**: docs/ARCHITECTURE.md - Check Service

**Implementar**:
- `CheckerInterface` com métodos obrigatórios
- `AbstractChecker` com lógica comum
- `CheckService` coordenador

**Arquivos a criar**:
- `src/Service/Check/CheckerInterface.php`
- `src/Service/Check/AbstractChecker.php`
- `src/Service/Check/CheckService.php`
- `tests/TestCase/Service/Check/CheckServiceTest.php`

**Critérios de Aceite**:
- [ ] Interface bem definida
- [ ] Abstract class com métodos comuns
- [ ] CheckService coordena checkers

---

### TASK-211: HTTP Checker
**Status**: 🔴 | **Prioridade**: 🔥 | **Dependências**: TASK-210
**Estimativa**: 3h

**Descrição**: Implementar checker para monitores HTTP/HTTPS.

**Implementar**:
- Request HTTP
- Validação de status code
- Medição de response time
- Validação de conteúdo (opcional)
- Timeout handling

**Arquivos a criar**:
- `src/Service/Check/HttpChecker.php`
- `tests/TestCase/Service/Check/HttpCheckerTest.php`

**Critérios de Aceite**:
- [ ] Faz request HTTP corretamente
- [ ] Valida status code
- [ ] Mede response time
- [ ] Trata erros e timeouts
- [ ] Testes com mocks passando

---

### TASK-212: Ping Checker
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-210
**Estimativa**: 3h

**Descrição**: Implementar checker para ping ICMP.

**Implementar**:
- Execução de ping via shell
- Parse de resultado
- Medição de latência
- Detecção de packet loss

**Arquivos a criar**:
- `src/Service/Check/PingChecker.php`
- `tests/TestCase/Service/Check/PingCheckerTest.php`

**Critérios de Aceite**:
- [ ] Ping funciona em Linux/Mac/Windows
- [ ] Extrai latência corretamente
- [ ] Detecta packet loss
- [ ] Testes passando

---

### TASK-213: Port Checker
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-210
**Estimativa**: 2h

**Descrição**: Implementar checker para verificação de portas TCP/UDP.

**Implementar**:
- Conexão TCP socket
- Timeout configurável
- Medição de tempo de conexão

**Arquivos a criar**:
- `src/Service/Check/PortChecker.php`
- `tests/TestCase/Service/Check/PortCheckerTest.php`

**Critérios de Aceite**:
- [ ] Verifica porta TCP
- [ ] Timeout funcional
- [ ] Mede tempo de conexão
- [ ] Testes passando

---

### TASK-214: Monitor Check Command
**Status**: 🔴 | **Prioridade**: 🔥 | **Dependências**: TASK-211, TASK-212, TASK-213
**Estimativa**: 4h

**Descrição**: Criar Command para executar verificações via cron.

**Implementar**:
- Buscar monitores que devem ser verificados (next_check_at <= now)
- Executar checker apropriado para cada tipo
- Registrar resultado em monitor_checks
- Atualizar status do monitor
- Atualizar next_check_at
- Log de execução

**Arquivos a criar**:
- `src/Command/MonitorCheckCommand.php`
- `tests/TestCase/Command/MonitorCheckCommandTest.php`

**Critérios de Aceite**:
- [ ] Command executa com `bin/cake monitor_check`
- [ ] Verifica apenas monitores na janela
- [ ] Registra checks corretamente
- [ ] Atualiza status do monitor
- [ ] Performance adequada (< 30s para 100 monitores)

---

### TASK-220: Incident Model e Service
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-140, TASK-214
**Estimativa**: 4h

**Descrição**: Criar Model Incident e IncidentService para gestão de incidentes.

**Implementar**:
- Model e Entity Incident
- IncidentService com métodos:
  - `createIncident(Monitor $monitor)`
  - `updateIncident(Incident $incident, string $status)`
  - `resolveIncident(Incident $incident)`
  - `getActiveIncidents()`
- Auto-criação quando monitor fica DOWN
- Auto-resolução quando monitor fica UP
- Cálculo de duração

**Arquivos a criar**:
- `src/Model/Entity/Incident.php`
- `src/Model/Table/IncidentsTable.php`
- `src/Service/IncidentService.php`
- `tests/TestCase/Service/IncidentServiceTest.php`

**Critérios de Aceite**:
- [ ] Incidentes criados automaticamente
- [ ] Resolvidos automaticamente
- [ ] Duração calculada corretamente
- [ ] Testes passando

---

### TASK-221: Incidents Controller
**Status**: 🔴 | **Prioridade**: 💡 | **Dependências**: TASK-220
**Estimativa**: 3h

**Descrição**: Controller para visualizar e gerenciar incidentes no admin.

**Implementar**:
- index: Listar incidentes (filtros por status)
- view: Ver detalhes e timeline
- edit: Atualizar status manualmente
- resolve: Resolver incidente

**Arquivos a criar**:
- `src/Controller/Admin/IncidentsController.php`
- `templates/Admin/Incidents/index.php`
- `templates/Admin/Incidents/view.php`

**Critérios de Aceite**:
- [ ] Lista incidentes com filtros
- [ ] Exibe timeline
- [ ] Permite atualização manual

---

### TASK-230: Status Page Controller
**Status**: 🔴 | **Prioridade**: 🔥 | **Dependências**: TASK-200, TASK-220, TASK-121
**Estimativa**: 4h

**Descrição**: Criar página pública de status.

**Implementar**:
- Controller StatusController
- Lógica de código HTTP baseado em status geral
- Cache de 30 segundos
- View com todos os monitores
- Indicadores visuais por status
- Últimos incidentes

**Arquivos a criar**:
- `src/Controller/StatusController.php`
- `templates/Status/index.php`
- `webroot/css/status-page.css`
- `tests/TestCase/Controller/StatusControllerTest.php`

**Critérios de Aceite**:
- [ ] Retorna 200 quando tudo OK
- [ ] Retorna 503 quando algo DOWN
- [ ] Cache funcional
- [ ] UI clara e informativa
- [ ] Responsiva

---

### TASK-231: Status Page - Componentes Visuais
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-230
**Estimativa**: 3h

**Descrição**: Criar componentes visuais para a status page.

**Implementar**:
- Monitor status card (verde/amarelo/vermelho)
- Uptime percentage badge
- Response time indicator
- Timeline de incidentes
- Subscribe form

**Arquivos a criar**:
- `templates/element/status/monitor_card.php`
- `templates/element/status/incident_timeline.php`
- `templates/element/status/subscribe_form.php`

**Critérios de Aceite**:
- [ ] Componentes reutilizáveis
- [ ] Visual atraente
- [ ] Informação clara

---

### TASK-240: Subscriber Model
**Status**: 🔴 | **Prioridade**: 💡 | **Dependências**: TASK-150
**Estimativa**: 2h

**Descrição**: Criar Models Subscriber e Subscription.

**Implementar**:
- Models e Entities
- Validações
- Geração de tokens (verification, unsubscribe)
- Associações

**Arquivos a criar**:
- `src/Model/Entity/Subscriber.php`
- `src/Model/Table/SubscribersTable.php`
- `src/Model/Entity/Subscription.php`
- `src/Model/Table/SubscriptionsTable.php`
- `tests/Fixture/SubscribersFixture.php`

**Critérios de Aceite**:
- [ ] Models com validações
- [ ] Tokens gerados automaticamente
- [ ] Associações corretas

---

### TASK-241: Subscribers Controller - Subscribe Flow
**Status**: 🔴 | **Prioridade**: 💡 | **Dependências**: TASK-240
**Estimativa**: 4h

**Descrição**: Implementar fluxo de inscrição pública.

**Implementar**:
- Form de subscribe na status page
- Envio de email de verificação
- Página de verificação (click no link)
- Página de sucesso
- Unsubscribe com token

**Arquivos a criar**:
- `src/Controller/SubscribersController.php`
- `templates/Subscribers/subscribe.php`
- `templates/Subscribers/verify.php`
- `templates/Subscribers/unsubscribe.php`
- `templates/email/html/verify_subscription.php`

**Critérios de Aceite**:
- [ ] Form funcional
- [ ] Email enviado
- [ ] Verificação funciona
- [ ] Unsubscribe funciona

---

### TASK-250: Alert Rule Model
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-170
**Estimativa**: 2h

**Descrição**: Criar Models AlertRule e AlertLog.

**Ver**: docs/DATABASE.md

**Arquivos a criar**:
- `src/Model/Entity/AlertRule.php`
- `src/Model/Table/AlertRulesTable.php`
- `src/Model/Entity/AlertLog.php`
- `src/Model/Table/AlertLogsTable.php`

**Critérios de Aceite**:
- [ ] Models criados
- [ ] Validações
- [ ] Associações

---

### TASK-251: Alert Service - Interface e Email Channel
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-250, TASK-220
**Estimativa**: 5h

**Descrição**: Implementar AlertService e canal de email.

**Implementar**:
- `ChannelInterface`
- `AlertService` com lógica de disparo
- `EmailAlertChannel`
- Integração com IncidentService
- Throttling de alertas
- Templates de email

**Arquivos a criar**:
- `src/Service/Alert/ChannelInterface.php`
- `src/Service/Alert/AlertService.php`
- `src/Service/Alert/EmailAlertChannel.php`
- `templates/email/html/incident_down.php`
- `templates/email/html/incident_up.php`
- `tests/TestCase/Service/Alert/AlertServiceTest.php`

**Critérios de Aceite**:
- [ ] Alertas disparados corretamente
- [ ] Emails enviados
- [ ] Throttling funciona
- [ ] Registra em alert_logs

---

## Fase 3: Integrações

### TASK-300: Integration Interface
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-160
**Estimativa**: 2h

**Descrição**: Criar interface e estrutura base para integrações.

**Ver**: docs/API_INTEGRATIONS.md

**Arquivos a criar**:
- `src/Integration/IntegrationInterface.php`
- `src/Integration/AbstractIntegration.php`

**Critérios de Aceite**:
- [ ] Interface bem definida
- [ ] Abstract com métodos comuns

---

### TASK-301: IXC Adapter e Client
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-300
**Estimativa**: 6h

**Descrição**: Implementar integração com IXC.

**Ver**: docs/API_INTEGRATIONS.md - Integração IXC

**Implementar**:
- IxcClient para chamadas HTTP
- IxcAdapter implementando interface
- IxcMapper para transformar dados
- Checkers específicos do IXC

**Arquivos a criar**:
- `src/Integration/Ixc/IxcClient.php`
- `src/Integration/Ixc/IxcAdapter.php`
- `src/Integration/Ixc/IxcMapper.php`
- `src/Service/Check/IxcServiceChecker.php`
- `src/Service/Check/IxcEquipmentChecker.php`
- `tests/TestCase/Integration/Ixc/IxcAdapterTest.php`

**Critérios de Aceite**:
- [ ] Autenticação funcional
- [ ] Métodos principais implementados
- [ ] Checkers funcionando
- [ ] Testes com mocks

---

### TASK-302: Zabbix Adapter e Client
**Status**: 🔴 | **Prioridade**: ⭐ | **Dependências**: TASK-300
**Estimativa**: 6h

**Descrição**: Implementar integração com Zabbix.

**Ver**: docs/API_INTEGRATIONS.md - Integração Zabbix

**Implementar**:
- ZabbixClient para JSON-RPC
- ZabbixAdapter implementando interface
- ZabbixMapper
- Checkers específicos

**Arquivos a criar**:
- `src/Integration/Zabbix/ZabbixClient.php`
- `src/Integration/Zabbix/ZabbixAdapter.php`
- `src/Integration/Zabbix/ZabbixMapper.php`
- `src/Service/Check/ZabbixHostChecker.php`
- `src/Service/Check/ZabbixTriggerChecker.php`
- `tests/TestCase/Integration/Zabbix/ZabbixAdapterTest.php`

**Critérios de Aceite**:
- [ ] Autenticação funcional
- [ ] Métodos principais implementados
- [ ] Checkers funcionando
- [ ] Testes com mocks

---

### TASK-303: REST API Generic Adapter
**Status**: 🔴 | **Prioridade**: 💡 | **Dependências**: TASK-300
**Estimativa**: 4h

**Descrição**: Implementar adapter genérico para APIs REST.

**Ver**: docs/API_INTEGRATIONS.md - REST API Genérica

**Implementar**:
- RestApiAdapter configurável
- Validadores (status, content, json_path)
- RestApiChecker

**Arquivos a criar**:
- `src/Integration/RestApi/RestApiAdapter.php`
- `src/Integration/RestApi/RestApiClient.php`
- `src/Service/Check/RestApiChecker.php`
- `tests/TestCase/Integration/RestApi/RestApiAdapterTest.php`

**Critérios de Aceite**:
- [ ] Configuração flexível
- [ ] Validações funcionam
- [ ] Testes passando

---

### TASK-310: Integrations Controller
**Status**: 🔴 | **Prioridade**: 💡 | **Dependências**: TASK-301, TASK-302
**Estimativa**: 4h

**Descrição**: CRUD de integrações no admin.

**Implementar**:
- Listagem de integrações
- Adicionar nova integração
- Editar integração
- Testar conexão
- Ver logs

**Arquivos a criar**:
- `src/Controller/Admin/IntegrationsController.php`
- `templates/Admin/Integrations/index.php`
- `templates/Admin/Integrations/add.php`
- `templates/Admin/Integrations/test.php`

**Critérios de Aceite**:
- [ ] CRUD completo
- [ ] Teste de conexão funciona
- [ ] Credenciais seguras

---

## Fase 4: Melhorias

### TASK-400: Dashboard Admin
**Status**: 🔴 | **Prioridade**: 💡 | **Dependências**: TASK-200, TASK-220
**Estimativa**: 5h

**Descrição**: Dashboard com estatísticas no admin.

**Implementar**:
- Resumo de monitores (total, up, down)
- Incidentes ativos
- Gráfico de uptime
- Últimas verificações
- Alertas recentes

**Arquivos a criar**:
- `src/Controller/Admin/DashboardController.php`
- `templates/Admin/Dashboard/index.php`
- `webroot/js/charts.js`

**Critérios de Aceite**:
- [ ] Estatísticas precisas
- [ ] Gráficos funcionais
- [ ] Performance boa

---

### TASK-410: Cleanup Command
**Status**: 🔴 | **Prioridade**: 📌 | **Dependências**: TASK-000
**Estimativa**: 2h

**Descrição**: Command para limpeza de dados antigos.

**Implementar**:
- Deletar monitor_checks > 30 dias
- Deletar integration_logs > 7 dias
- Deletar alert_logs > 30 dias
- VACUUM SQLite

**Arquivos a criar**:
- `src/Command/CleanupCommand.php`

**Critérios de Aceite**:
- [ ] Limpeza funcional
- [ ] Logs informativos
- [ ] Configurável

---

### TASK-420: Backup Command
**Status**: 🔴 | **Prioridade**: 📌 | **Dependências**: TASK-000
**Estimativa**: 2h

**Descrição**: Command para backup automático.

**Implementar**:
- Copiar database.db para pasta de backups
- Nome com timestamp
- Rotação (manter últimos 30)
- Compressão opcional

**Arquivos a criar**:
- `src/Command/BackupCommand.php`
- `bin/backup.sh`

**Critérios de Aceite**:
- [ ] Backup funciona
- [ ] Rotação automática
- [ ] Restore documentado

---

## Como Pegar uma Tarefa

1. Verifique as dependências
2. Certifique-se que tem o contexto necessário (leia os docs referenciados)
3. Atualize o status para 🟡
4. Crie uma branch: `git checkout -b task-XXX-description`
5. Desenvolva seguindo os critérios de aceite
6. Execute os testes
7. Faça commit e PR
8. Atualize status para 🟢 após merge

## Ordem Recomendada de Execução

**Sprint 1** (Semana 1-2):
- TASK-000, 001 (Setup)
- TASK-100, 101, 102, 103 (Auth)
- TASK-120, 121 (Layouts)
- TASK-130, 140, 150, 160, 170 (Migrations)

**Sprint 2** (Semana 3-4):
- TASK-110, 111, 112 (Settings)
- TASK-200, 201, 202 (Monitors)
- TASK-210, 211, 212, 213, 214 (Check Engine)

**Sprint 3** (Semana 5-6):
- TASK-220, 221 (Incidents)
- TASK-230, 231 (Status Page)
- TASK-240, 241 (Subscribers)
- TASK-250, 251 (Alerts)

**Sprint 4** (Semana 7-8):
- TASK-300, 301, 302, 303 (Integrações)
- TASK-310 (Integrations UI)

**Sprint 5** (Semana 9):
- TASK-400 (Dashboard)
- TASK-410, 420 (Maintenance)

## Estimativas Totais

- Fase 0: ~5h
- Fase 1: ~40h
- Fase 2: ~60h
- Fase 3: ~25h
- Fase 4: ~10h

**Total: ~140 horas** (~4-5 semanas com 1 dev, ~2-3 semanas com 2-3 devs)
