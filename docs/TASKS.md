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
**Fase 1**: TASK-100 ✅, TASK-101 ✅, TASK-102 ✅, TASK-111 ✅, TASK-120 ✅, TASK-121 ✅ (6/? completas)
**Fase 2**: TASK-200 ✅, TASK-201 ✅, TASK-210 ✅ (3/? completas)

**Modelos Criados**: User, Setting, Monitor, Incident, MonitorCheck, Subscriber, Subscription, AlertRule, AlertLog, Integration, IntegrationLog (11/11)
**Controllers**: UsersController, AdminController, StatusController, MonitorsController ✅
**Migrations**: Todas as 11 migrations criadas e executadas ✅
**Seeds**: UsersSeed, SettingsSeed, MonitorsSeed criados e executados ✅
**Services**: SettingService com cache implementado ✅
**Autenticação**: Sistema completo de login/logout ✅
**Design System**: Paleta de cores oficial documentada (docs/DESIGN.md) ✅

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
**Status**: 🟢 **COMPLETO** | **Prioridade**: 🔥 | **Dependências**: TASK-000
**Estimativa**: 1h | **Tempo Real**: 0.5h

**Descrição**: Criar migration para tabela de usuários.

**Ações Realizadas**:
Todas as 11 migrations criadas manualmente em:
- `src/config/Migrations/20251031090129_CreateUsers.php`
- E executadas com sucesso: `bin/cake migrations migrate`

**Campos implementados**:
- ✅ id (PK, auto-increment)
- ✅ username (unique, maxLength 100)
- ✅ password (hash bcrypt, maxLength 255)
- ✅ email (unique)
- ✅ role (maxLength 20, default 'user')
- ✅ active (boolean, default true)
- ✅ last_login (datetime, nullable)
- ✅ created, modified (timestamps)

**Critérios de Aceite**:
- [x] Migration criada em `src/config/Migrations/`
- [x] `bin/cake migrations migrate` executou sem erros
- [x] Tabela users existe no SQLite (`src/database.db`)
- [x] Seed UsersSeed criado com usuário admin padrão

---

### TASK-101: User Model e Entity
**Status**: 🟢 **COMPLETO** | **Prioridade**: 🔥 | **Dependências**: TASK-100
**Estimativa**: 2h | **Tempo Real**: 1h

**Descrição**: Criar Model e Entity de User com validações.

**Ações Realizadas**:
```bash
cd src
bin/cake bake model Users --no-test --no-fixture
```

**Implementado**:
- ✅ Validações completas (username, email, password)
- ✅ Validação de senha mínima de 8 caracteres
- ✅ Validação de role (admin, user, viewer)
- ✅ Hash automático de senha com DefaultPasswordHasher
- ✅ Métodos auxiliares: isAdmin(), isActive(), getRoleName()

**Arquivos criados**:
- `src/src/Model/Entity/User.php` - ✅ Com métodos auxiliares
- `src/src/Model/Table/UsersTable.php` - ✅ Com validações completas

**Critérios de Aceite**:
- [x] Model criado com validações
- [x] Senha é hash automaticamente
- [x] Métodos auxiliares implementados
- [x] Validações de role e senha

---

### TASK-102: Sistema de Autenticação
**Status**: 🟢 **COMPLETO** | **Prioridade**: 🔥 | **Dependências**: TASK-101
**Estimativa**: 4h | **Tempo Real**: 3h

**Descrição**: Implementar sistema de login/logout usando CakePHP Authentication.

**Ações Realizadas**:
```bash
# Instalado via composer
php composer.phar require cakephp/authentication:^3.0
```

**Implementado**:
- ✅ cakephp/authentication 3.3.2 instalado
- ✅ Application.php configurado com AuthenticationServiceProviderInterface
- ✅ AuthenticationMiddleware adicionado
- ✅ getAuthenticationService() configurado com Session + Form authenticators
- ✅ Password identifier com finder 'auth' (apenas usuários ativos)
- ✅ AppController configurado com Authentication component
- ✅ UsersTable com custom finder findAuth() para filtrar usuários ativos
- ✅ UsersController criado com login/logout actions
- ✅ Login view com design moderno e responsivo
- ✅ Redirect para /admin após login
- ✅ Flash messages para feedback
- ✅ Public access para action 'display' (status page)

**Arquivos criados/modificados**:
- `src/Application.php` - ✅ AuthenticationServiceProvider configurado
- `src/Controller/AppController.php` - ✅ Component carregado
- `src/Controller/UsersController.php` - ✅ Criado com CRUD completo
- `src/Model/Table/UsersTable.php` - ✅ Finder 'auth' adicionado
- `templates/Users/login.php` - ✅ View moderna com CSS

**Credenciais padrão**:
- Username: admin
- Password: admin123

**Critérios de Aceite**:
- [x] Login funcional
- [x] Logout funcional
- [x] Redirect automático para /users/login
- [x] Sessão persistente
- [x] Apenas usuários ativos podem fazer login
- [x] View com design moderno

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
**Status**: 🟢 **COMPLETO** | **Prioridade**: ⭐ | **Dependências**: TASK-110
**Estimativa**: 3h | **Tempo Real**: 2h

**Descrição**: Criar Model Setting e SettingService com cache.

**Ações Realizadas**:
```bash
cd src
bin/cake bake model Settings --no-test --no-fixture
```

**Implementado**:
- ✅ Model e Entity Setting
- ✅ Validação de type (string, integer, boolean, json)
- ✅ Métodos getTypedValue() e _setValue() na Entity
- ✅ Auto-detecção de tipo na Entity
- ✅ SettingService com cache (1 hora)
- ✅ Métodos: get(), set(), getString(), getInt(), getBool(), getArray()
- ✅ Métodos: has(), delete(), clearCache(), reload(), getAll()

**Arquivos criados**:
- `src/src/Model/Entity/Setting.php` - ✅ Com type casting
- `src/src/Model/Table/SettingsTable.php` - ✅ Com validações
- `src/src/Service/SettingService.php` - ✅ Com cache completo

**Critérios de Aceite**:
- [x] CRUD de settings funcional
- [x] Cache funcionando (1 hora)
- [x] Type casting automático
- [x] Múltiplos getters tipados

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
**Status**: 🟢 **COMPLETO** | **Prioridade**: ⭐ | **Dependências**: TASK-000
**Estimativa**: 6h | **Tempo Real**: 4h

**Descrição**: Criar layout base para painel administrativo.

**Implementar**:
- Layout `admin.php`
- Navbar com menu
- Sidebar (opcional)
- Footer
- Integração com Tailwind CSS ou Bootstrap
- JavaScript base (Alpine.js)

**Arquivos criados**:
- `templates/layout/admin.php` ✅
- `templates/element/admin/navbar.php` ✅
- `templates/element/admin/sidebar.php` ✅
- `templates/element/admin/footer.php` ✅
- `webroot/css/admin.css` ✅
- `src/Controller/AdminController.php` ✅
- `templates/Admin/index.php` ✅ (Dashboard)

**Critérios de Aceite**:
- [x] Layout responsivo
- [x] Navegação funcional
- [x] Estilo consistente (usando design system oficial)
- [x] Mobile-friendly (sidebar responsivo)
- [x] Dashboard com estatísticas
- [x] Integração com Authentication (menu de usuário)
- [x] CSS Variables do design system aplicado

---

### TASK-121: Layout Público Base
**Status**: 🟢 **COMPLETO** | **Prioridade**: ⭐ | **Dependências**: TASK-000
**Estimativa**: 4h | **Tempo Real**: 3h

**Descrição**: Criar layout base para páginas públicas (status page).

**Implementar**:
- Layout `public.php`
- Header simples
- Footer
- Estilo focado em clareza e legibilidade

**Arquivos criados**:
- `templates/layout/public.php` ✅
- `templates/element/public/header.php` ✅
- `templates/element/public/footer.php` ✅
- `webroot/css/public.css` ✅
- `src/Controller/StatusController.php` ✅
- `templates/Status/index.php` ✅ (Página de status)

**Critérios de Aceite**:
- [x] Layout clean e profissional
- [x] Responsivo para mobile
- [x] Design system aplicado (cores oficiais)
- [x] Indicadores de status visuais
- [x] Sistema de atualização automática (30s)
- [x] Códigos HTTP inteligentes (503 para major outage, 500 para partial)
- [x] Seção de inscrição para notificações
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
**Status**: 🟢 **COMPLETO** | **Prioridade**: 🔥 | **Dependências**: TASK-130
**Estimativa**: 3h | **Tempo Real**: 2h

**Descrição**: Criar Model Monitor com validações e lógica.

**Ações Realizadas**:
```bash
cd src
bin/cake bake model Monitors --no-test --no-fixture
```

**Implementado**:
- ✅ Constantes de tipo (TYPE_HTTP, TYPE_PING, TYPE_PORT, TYPE_API, TYPE_IXC, TYPE_ZABBIX)
- ✅ Constantes de status (STATUS_UP, STATUS_DOWN, STATUS_DEGRADED, STATUS_UNKNOWN)
- ✅ Validação de type com inList
- ✅ Validação de status com inList
- ✅ Validação de JSON configuration
- ✅ Validação de valores mínimos (check_interval > 0, timeout > 0, retry_count >= 0)
- ✅ Validação de uptime_percentage (0-100)
- ✅ Validação de display_order >= 0
- ✅ Métodos auxiliares: isUp(), isDown(), isDegraded(), isUnknown()
- ✅ Métodos auxiliares: isActive(), isVisibleOnStatusPage()
- ✅ Métodos: getConfiguration(), getStatusBadgeClass(), getTypeName()
- ✅ Setter _setConfiguration() para auto-encode JSON
- ✅ Associações: hasMany AlertLogs, AlertRules, Incidents, MonitorChecks, Subscriptions

**Arquivos criados**:
- `src/src/Model/Entity/Monitor.php` - ✅ Com constantes e métodos
- `src/src/Model/Table/MonitorsTable.php` - ✅ Com validações completas

**Critérios de Aceite**:
- [x] Validações funcionando
- [x] JSON configuration validado
- [x] Associações corretas (5 hasMany)
- [x] Métodos auxiliares implementados
- [x] Constantes de tipo e status

---

### TASK-201: MonitorsController - CRUD
**Status**: 🟢 **COMPLETO** | **Prioridade**: 🔥 | **Dependências**: TASK-200, TASK-120
**Estimativa**: 5h | **Tempo Real**: 3h

**Descrição**: Implementar CRUD completo de monitores no admin.

**Arquivos criados**:
- `src/Controller/MonitorsController.php` ✅
- `templates/Monitors/index.php` ✅
- `templates/Monitors/view.php` ✅
- `templates/Monitors/add.php` ✅
- `templates/Monitors/edit.php` ✅
- `tests/TestCase/Controller/MonitorsControllerTest.php` ✅

**Funcionalidades implementadas**:
- ✅ index: Listagem com filtros (tipo, status, busca)
- ✅ view: Detalhes completos + estatísticas (uptime, tempo médio)
- ✅ add: Criar novo monitor com campos dinâmicos por tipo
- ✅ edit: Editar monitor existente
- ✅ delete: Excluir monitor
- ✅ toggle: Ativar/desativar monitor
- ✅ Estatísticas no topo (total, ativos, online, offline)
- ✅ Tabela responsiva com ações inline
- ✅ Paginação
- ✅ Design system aplicado

**Critérios de Aceite**:
- [x] CRUD completo funcional
- [x] Form adapta-se ao tipo de monitor (JavaScript)
- [x] Validações no backend (MonitorsTable)
- [x] Mensagens de feedback apropriadas (Flash)
- [x] Testes de integração criados (20 testes)

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
**Status**: 🟢 **COMPLETO** | **Prioridade**: 🔥 | **Dependências**: TASK-200
**Estimativa**: 2h | **Tempo Real**: 1.5h

**Descrição**: Criar interface e classe abstrata para checkers.

**Arquivos criados**:
- `src/Service/Check/CheckerInterface.php` ✅
- `src/Service/Check/AbstractChecker.php` ✅
- `src/Service/Check/CheckService.php` ✅
- `tests/TestCase/Service/Check/CheckServiceTest.php` ✅ (11 testes)
- `tests/TestCase/Service/Check/AbstractCheckerTest.php` ✅ (11 testes)

**Funcionalidades implementadas**:
- ✅ Interface CheckerInterface com 4 métodos obrigatórios
- ✅ AbstractChecker com lógica comum (error handling, logging, timing)
- ✅ Métodos auxiliares: buildSuccessResult(), buildErrorResult(), buildDegradedResult()
- ✅ CheckService como registry e factory de checkers
- ✅ Suporte para múltiplos checkers simultaneamente
- ✅ Validação de configuração de monitores
- ✅ Logging completo de todas as operações
- ✅ Tratamento robusto de erros e exceções
- ✅ 22 testes passando (100% coverage dos métodos críticos)

**Critérios de Aceite**:
- [x] Interface bem definida
- [x] Abstract class com métodos comuns
- [x] CheckService coordena checkers
- [x] Testes passando com 100% dos assertions

---

### TASK-211: HTTP Checker
**Status**: 🟢 **COMPLETO** | **Prioridade**: 🔥 | **Dependências**: TASK-210
**Estimativa**: 3h | **Tempo Real**: 2h

**Descrição**: Implementar checker para monitores HTTP/HTTPS.

**Implementado**:
- ✅ Request HTTP/HTTPS usando Cake\Http\Client
- ✅ Validação de status code (expected_status_code)
- ✅ Medição de response time (milliseconds)
- ✅ Detecção de degraded performance (>80% timeout)
- ✅ Timeout handling configurável
- ✅ Headers customizáveis via configuration
- ✅ SSL verification toggle
- ✅ Auto-adiciona https:// se URL sem scheme
- ✅ Validação de URL (rejeita ftp://, javascript:, etc)
- ✅ Error messages user-friendly
- ✅ Seguir redirects automático

**Arquivos criados**:
- `src/Service/Check/HttpChecker.php` - ✅ 320 linhas
- `tests/TestCase/Service/Check/HttpCheckerTest.php` - ✅ 14 testes

**Critérios de Aceite**:
- [x] Faz request HTTP corretamente
- [x] Valida status code
- [x] Mede response time
- [x] Trata erros e timeouts
- [x] Testes com mocks passando (14/14 testes, 28 assertions)

---

### TASK-212: Ping Checker
**Status**: 🟢 **COMPLETO** | **Prioridade**: ⭐ | **Dependências**: TASK-210
**Estimativa**: 3h | **Tempo Real**: 2.5h

**Descrição**: Implementar checker para ping ICMP.

**Implementado**:
- ✅ Execução de ping via shell (shell_exec)
- ✅ Comandos específicos por OS (Linux, macOS, Windows)
- ✅ Parse de resultado com regex
- ✅ Medição de latência (min/avg/max)
- ✅ Detecção de packet loss
- ✅ Suporte IPv4 e IPv6
- ✅ Remoção inteligente de scheme/path/port
- ✅ Detecção de degraded com packet loss
- ✅ Error messages user-friendly
- ✅ Configuração de timeout por OS

**Arquivos criados**:
- `src/Service/Check/PingChecker.php` - ✅ 395 linhas
- `tests/TestCase/Service/Check/PingCheckerTest.php` - ✅ 21 testes

**Critérios de Aceite**:
- [x] Ping funciona em Linux/Mac/Windows
- [x] Extrai latência corretamente
- [x] Detecta packet loss
- [x] Testes passando (21/21 testes, 56 assertions)

---

### TASK-213: Port Checker
**Status**: 🟢 **COMPLETO** | **Prioridade**: ⭐ | **Dependências**: TASK-210
**Estimativa**: 2h | **Tempo Real**: 1.5h

**Descrição**: Implementar checker para verificação de portas TCP/UDP.

**Implementado**:
- ✅ Conexão TCP socket com stream_socket_client
- ✅ Timeout configurável por monitor
- ✅ Medição de tempo de conexão (milliseconds)
- ✅ Detecção de degraded performance (>80% timeout)
- ✅ Suporte IPv4 e IPv6
- ✅ Parse de target host:port e [ipv6]:port
- ✅ Validação de range de porta (1-65535)
- ✅ Error messages user-friendly
- ✅ Testes com mocks e integração

**Arquivos criados**:
- `src/Service/Check/PortChecker.php` - ✅ 260 linhas
- `tests/TestCase/Service/Check/PortCheckerTest.php` - ✅ 20 testes

**Critérios de Aceite**:
- [x] Verifica porta TCP
- [x] Timeout funcional
- [x] Mede tempo de conexão
- [x] Testes passando (20/20 testes, 50 assertions)

---

### TASK-214: Monitor Check Command
**Status**: 🟢 **COMPLETO** | **Prioridade**: 🔥 | **Dependências**: TASK-211, TASK-212, TASK-213
**Estimativa**: 4h | **Tempo Real**: 3h

**Descrição**: Criar Command para executar verificações via cron.

**Implementado**:
- ✅ Command `bin/cake monitor_check` funcional
- ✅ Busca monitores ativos do banco
- ✅ Integração com CheckService
- ✅ Registra todos os 3 checkers (HTTP, Ping, Port)
- ✅ Salva resultados em monitor_checks table
- ✅ Atualiza status do monitor (up/down/degraded)
- ✅ Atualiza last_check_at timestamp
- ✅ Calcula uptime_percentage (últimas 24h)
- ✅ Suporte para --monitor-id (check específico)
- ✅ Modo verbose (-v) para debug
- ✅ Logging completo (info, debug, error)
- ✅ Summary com estatísticas
- ✅ Error handling robusto
- ✅ Status mapping (up→success, down→failure, degraded→success)
- ✅ Virtual field 'target' na Monitor Entity

**Arquivos criados**:
- `src/Command/MonitorCheckCommand.php` - ✅ 380 linhas
- `src/Model/Entity/Monitor.php` - ✅ Adicionado virtual field 'target'

**Arquivos modificados**:
- `src/Model/Entity/Monitor.php` - ✅ Virtual field 'target' extrai URL/host da configuration

**Critérios de Aceite**:
- [x] Command executa com `bin/cake monitor_check`
- [x] Busca e verifica monitores ativos
- [x] Registra checks corretamente em monitor_checks
- [x] Atualiza status do monitor
- [x] Calcula uptime percentage
- [x] Performance adequada
- [x] Testado com monitores reais (HTTP, Ping, Port)

---

### TASK-220: Incident Model e Service
**Status**: 🟢 | **Prioridade**: ⭐ | **Dependências**: TASK-140, TASK-214
**Estimativa**: 4h | **Realizado**: 4h

**Descrição**: Criar Model Incident e IncidentService para gestão de incidentes.

**Implementar**:
- Model e Entity Incident
- IncidentService com métodos:
  - `createIncident(Monitor $monitor)`
  - `updateIncident(Incident $incident, string $status)`
  - `resolveIncident(Incident $incident)`
  - `getActiveIncidents()`
  - `autoResolveIncidents(Monitor $monitor)`
  - `getActiveIncidentForMonitor(int $monitorId)`
- Auto-criação quando monitor fica DOWN
- Auto-resolução quando monitor fica UP
- Cálculo de duração

**Arquivos criados**:
- `src/Model/Entity/Incident.php` ✅
- `src/Model/Table/IncidentsTable.php` ✅ (já existia, adicionados custom finders)
- `src/Service/IncidentService.php` ✅
- `tests/TestCase/Service/IncidentServiceTest.php` ✅

**Arquivos modificados**:
- `src/Command/MonitorCheckCommand.php` - Integração com IncidentService
- `src/Model/Table/IncidentsTable.php` - Adicionados finders: `findActive()`, `findByMonitor()`, `findActiveByMonitor()`

**Critérios de Aceite**:
- [x] Incidentes criados automaticamente quando monitor fica DOWN
- [x] Resolvidos automaticamente quando monitor volta UP
- [x] Duração calculada corretamente em segundos
- [x] Testes passando (12/12 testes, 100% sucesso)

**Notas de Implementação**:
- Entity Incident possui constantes para status e severidade
- Helper methods: `isResolved()`, `isOngoing()`, `getSeverityBadgeClass()`, `getStatusName()`
- Verificação de incidentes duplicados (não cria se já existe ativo)
- Logging completo de todas as operações
- Timestamps: started_at, identified_at, resolved_at
- Severidade atual: todos como "major" (pronto para expansão futura)

---

### TASK-221: Incidents Controller
**Status**: 🟢 | **Prioridade**: 💡 | **Dependências**: TASK-220
**Estimativa**: 3h | **Realizado**: 3h

**Descrição**: Controller para visualizar e gerenciar incidentes no admin.

**Implementar**:
- index: Listar incidentes (filtros por status, severidade, monitor, busca)
- view: Ver detalhes, timeline de eventos e verificações recentes
- edit: Atualizar status e descrição manualmente
- resolve: Resolver incidente rapidamente

**Arquivos criados**:
- `src/Controller/IncidentsController.php` ✅
- `templates/Incidents/index.php` ✅
- `templates/Incidents/view.php` ✅

**Funcionalidades Implementadas**:

**Index (Listagem)**:
- Filtros: status (com "ativos"), severidade, monitor, auto-criado, busca por título/descrição
- Cards de estatísticas: Total, Ativos, Resolvidos, Críticos
- Tabela com badges coloridos por status e severidade
- Indicador de incidentes auto-criados (🤖)
- Links para monitores relacionados
- Duração formatada (segundos, minutos, horas, dias)
- Paginação integrada
- Ações: Ver, Editar, Resolver

**View (Detalhes)**:
- Timeline visual com eventos cronológicos (criação, identificação, resolução)
- Ícones e cores por tipo de evento (🚨, 🔍, ✅)
- Informações detalhadas: status, severidade, monitor afetado, timestamps
- Duração formatada com múltiplas unidades (s, m, h, d)
- Descrição completa do incidente
- Grid de verificações recentes do monitor (últimas 20)
- Status visual de cada check (✅/❌)
- Ações: Voltar, Editar, Resolver

**Edit e Resolve**:
- Integração com IncidentService para atualização
- Confirmação antes de resolver
- Mensagens de sucesso/erro via Flash
- Validação de incidentes já resolvidos

**Design e UX**:
- Layout responsivo (adapta para mobile)
- Badges coloridos seguindo status e severidade
- Timeline com marcadores visuais
- Hover effects e transições suaves
- Tipografia clara e hierarquia visual
- Estilos CSS inline para fácil manutenção

**Critérios de Aceite**:
- [x] Lista incidentes com filtros funcionais (status, severidade, monitor, busca)
- [x] Exibe timeline de eventos com timestamps e descrições
- [x] Permite atualização manual de status e descrição
- [x] Resolve incidentes com um clique
- [x] Interface responsiva e intuitiva
- [x] Integração completa com IncidentService

**Notas de Implementação**:
- Controller criado inicialmente em `Admin/` mas movido para raiz para consistência
- Templates movidos de `Admin/Incidents/` para `Incidents/` (padrão do projeto)
- URL final: `/incidents` (acessível via menu lateral)
- 3 incidentes de teste criados para validação da interface
- Método `buildTimeline()` gera eventos cronológicos automaticamente
- Método `formatDuration()` formata duração em formato legível

---

### TASK-222: Checks Controller
**Status**: 🟢 | **Prioridade**: 💡 | **Dependências**: TASK-214
**Estimativa**: 3h | **Realizado**: 3h

**Descrição**: Controller para visualizar histórico de verificações de monitores no admin.

**Implementar**:
- index: Listar todas as verificações com filtros (monitor, status, período)
- view: Ver detalhes de uma verificação específica
- Estatísticas de uptime e response time
- Timeline de checks anteriores e posteriores

**Arquivos criados**:
- `src/Controller/ChecksController.php` ✅
- `templates/Checks/index.php` ✅
- `templates/Checks/view.php` ✅

**Funcionalidades Implementadas**:

**Index (Listagem)**:
- Filtros: monitor (dropdown com todos os monitores ativos), status (success/failed), período (24h/7d/30d/all)
- Cards de estatísticas: Total checks, Success count, Failed count, Success rate (%), Avg response time (ms)
- Tabela com: data/hora, monitor (com tipo), status (badges coloridos), response time, mensagem
- Badges coloridos por status: ✅ Sucesso (verde), ❌ Falha (vermelho)
- Links para monitores relacionados
- Paginação integrada (50 checks por página)
- Botão "Ver" para acessar detalhes de cada check
- Busca por monitor, status e período com botão "Filtrar" e "Limpar"

**View (Detalhes)**:
- Banner de status no topo (verde para success, vermelho para failed)
- Informações completas: monitor, tipo, data/hora, status, response time, status code
- Message box com mensagem de erro (se houver), destacada em vermelho
- Response details em JSON formatado (se disponível)
- Estatísticas do monitor: Total checks, Success checks, Success rate, Avg response time
- Timeline de contexto: 5 checks anteriores + check atual + 5 checks posteriores
- Timeline visual com ícones (✅/❌), timestamp e response time
- Check atual destacado com borda azul e fundo azul claro
- Links: Voltar para Verificações, Ver Monitor
- Interface totalmente responsiva

**Design e UX**:
- Layout responsivo (adapta para mobile)
- Cards de estatísticas com cores semânticas (success: verde, error: vermelho, info: azul)
- Badges coloridos seguindo status
- Timeline com marcadores visuais e hover effects
- Tipografia clara com hierarquia visual
- Estilos CSS inline para fácil manutenção
- Hover effects em tabelas e timeline items

**Critérios de Aceite**:
- [x] Lista checks com filtros funcionais (monitor, status, período)
- [x] Exibe estatísticas de uptime e performance
- [x] Interface responsiva e clara
- [x] Paginação eficiente para grandes volumes (50 por página)
- [x] Integração com MonitorChecks model via fetchTable()

**Notas de Implementação**:
- Controller usa `$this->fetchTable('MonitorChecks')` para acessar o model (não há ChecksTable)
- URL final: `/checks` (acessível via menu lateral)
- Método `getPeriodStartDate()` converte string de período em DateTime
- Cálculos de estatísticas usando aggregation functions do CakePHP
- Timeline mostra contexto temporal (checks antes e depois)
- Verificação protegida por autenticação (redirect para /users/login se não logado)

---

### TASK-223: Subscribers Admin Controller
**Status**: 🟢 | **Prioridade**: 💡 | **Dependências**: TASK-240
**Estimativa**: 3h | **Realizado**: 3h

**Descrição**: Controller admin para gerenciar inscritos de notificações por email.

**Implementado**:
- index: Listar inscritos com filtros (status, data)
- view: Ver detalhes de um inscrito
- delete: Remover inscrito manualmente
- toggle: Ativar/desativar inscrito individualmente
- resendVerification: Reenviar email de verificação

**Arquivos criados**:
- `src/src/Controller/SubscribersController.php` ✅
- `src/templates/Subscribers/index.php` ✅
- `src/templates/Subscribers/view.php` ✅

**Funcionalidades Implementadas**:

**Index (Listagem)**:
- Filtros: status (verified/unverified), active (active/inactive), período (7d/30d/90d/all), busca por email/nome
- Cards de estatísticas: Total (azul), Verified (verde), Unverified (laranja), Active (verde), Recently Added (azul)
- Tabela com: email/nome, verificação (badge + timestamp), status ativo (badge), data de inscrição, número de assinaturas
- Badges coloridos: Verified (verde), Pending (laranja), Active (verde), Inactive (vermelho)
- Busca por email ou nome
- Paginação integrada (50 por página)
- Ações: Ver, Ativar/Desativar, Excluir (confirmação obrigatória)

**View (Detalhes)**:
- Status overview: indicador visual de status (ativo e verificado / inativo ou não verificado)
- Cards de estatísticas: Emails recebidos, Monitores inscritos, Status de verificação, Status ativo
- Informações completas: email, nome, status de verificação, status ativo, timestamps
- Tokens exibidos (verification_token, unsubscribe_token) para administração
- Lista de monitores inscritos com detalhes (nome, tipo, descrição, data de inscrição)
- Ações: Reenviar verificação (se não verificado), Ativar/Desativar, Excluir (confirmação obrigatória)

**Design e UX**:
- Layout responsivo seguindo DESIGN.md
- Botões com texto apenas (sem ícones): "Ver", "Ativar", "Desativar", "Excluir", "Reenviar Verificação"
- Cores consistentes: View (#3b82f6), Toggle (#8b5cf6), Delete (#ef4444)
- Cards de estatísticas com ícones e cores semânticas
- Filtros organizados em grid responsivo
- Paginação com contador de registros
- Empty states informativos

**Critérios de Aceite**:
- [x] Lista inscritos com filtros funcionais (verificação, ativo, período, busca)
- [x] Exibe estatísticas completas de inscrições
- [x] Permite deletar inscritos com confirmação
- [x] Permite ativar/desativar inscritos individualmente
- [x] Interface clara e intuitiva seguindo design system
- [x] Integração com Subscribers model
- [x] URL: /subscribers (acessível via menu lateral)

**Notas de Implementação**:
- Controller criado em `src/src/Controller/` (estrutura correta do projeto)
- Templates criados em `src/templates/Subscribers/` (estrutura correta do projeto)
- Método `toggle()` para ativar/desativar inscritos
- Método `resendVerification()` preparado para integração futura com EmailService
- EmailLogs count incluído no view (preparado para TASK-224)
- Subscriptions relationship carregada com eager loading (contain)

---

### TASK-224: EmailLogs Controller
**Status**: 🟢 | **Prioridade**: 💡 | **Dependências**: TASK-300
**Estimativa**: 3h | **Realizado**: 3h

**Descrição**: Controller para visualizar logs de emails enviados pelo sistema.

**Implementado**:
- index: Listar emails enviados com filtros
- view: Ver detalhes de um email (destinatário, status, timestamps)
- resend: Reenviar email (preparado para EmailService)

**Arquivos criados**:
- `src/src/Controller/EmailLogsController.php` ✅
- `src/templates/EmailLogs/index.php` ✅
- `src/templates/EmailLogs/view.php` ✅

**Funcionalidades Implementadas**:

**Index (Listagem)**:
- Usa tabela `alert_logs` filtrada por `channel='email'`
- Filtros: status (sent/failed/queued), período (24h/7d/30d/all), busca por email/assunto
- Cards de estatísticas: Total enviados (azul), Sucesso (verde), Falhas (vermelho), Taxa de sucesso (%), Hoje (azul)
- Tabela com: data/hora, destinatário, assunto (nome do monitor), status (badge colorido), monitor relacionado
- Badges: Sent (verde), Failed (vermelho), Queued (laranja)
- Link para monitor relacionado
- Paginação integrada (50 por página)
- Ações: Ver detalhes

**View (Detalhes)**:
- Status overview: indicador visual (✅ Enviado / ❌ Falha / ⏳ Na fila)
- Informações completas: destinatário, canal, status, data criação, data envio, tempo de processamento
- Monitor relacionado: nome, tipo, descrição, link para ver monitor
- Incidente relacionado (se houver): título, status, descrição, timestamps, link para ver incidente
- Regra de alerta relacionada
- Mensagem de erro (se falhou): exibida em card vermelho destacado
- Ação: Reenviar email (se falhou) - preparado para integração com EmailService

**Design e UX**:
- Layout responsivo seguindo DESIGN.md
- Botões com texto apenas (sem ícones): "Ver", "Voltar", "Reenviar Email"
- Cores consistentes: View (#3b82f6), Resend (#3b82f6)
- Status cards com gradientes e bordas coloridas
- Filtros organizados em grid responsivo
- Paginação com contador de registros
- Empty states informativos
- Error messages destacados em vermelho

**Critérios de Aceite**:
- [x] Lista emails com filtros funcionais (status, período, busca)
- [x] Exibe estatísticas completas de envio
- [x] Mostra informações completas do email e relacionamentos
- [x] Interface responsiva seguindo design system
- [x] Integração com AlertLogs model (channel='email')
- [x] URL: /email-logs (acessível via menu lateral)

**Notas de Implementação**:
- Controller usa AlertLogsTable filtrado por channel='email'
- EmailLogs é um alias para AlertLogs com filtro de canal
- Relacionamentos: Monitor (INNER JOIN), Incident (LEFT JOIN), AlertRule (LEFT JOIN)
- Método `resend()` preparado para integração futura com EmailService
- Dados de teste criados: 3 emails (2 sent, 1 failed) para validação

---

### TASK-225: Settings Controller
**Status**: 🔴 | **Prioridade**: 💡 | **Dependências**: TASK-150
**Estimativa**: 4h

**Descrição**: Controller para gerenciar configurações do sistema no admin.

**Implementar**:
- index: Página de configurações agrupadas por categoria
- save: Salvar configurações (validação incluída)
- Categorias: General, Email, Monitoring, Notifications

**Arquivos a criar**:
- `src/Controller/SettingsController.php`
- `templates/Settings/index.php`
- `src/Model/Entity/Setting.php` (se não existir)
- `src/Model/Table/SettingsTable.php` (se não existir)

**Funcionalidades**:

**Categorias de Configurações**:

1. **General**:
   - Site name
   - Site URL
   - Timezone
   - Language

2. **Email**:
   - SMTP host, port, username, password
   - From email/name
   - Test email button

3. **Monitoring**:
   - Check interval (minutos)
   - Timeout (segundos)
   - Max retries
   - Auto-resolve incidents

4. **Notifications**:
   - Email on incident created
   - Email on incident resolved
   - Email template customization

**Interface**:
- Abas ou accordion para cada categoria
- Forms com validação inline
- Test buttons (ex: test email, test monitor)
- Save button por categoria
- Success/error messages via Flash

**Critérios de Aceite**:
- [ ] Exibe configurações organizadas por categoria
- [ ] Salva configurações com validação
- [ ] Test email funcional
- [ ] Interface intuitiva e clara
- [ ] Valores carregados do banco ou .env
- [ ] Integração com Settings model

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
