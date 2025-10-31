# Getting Started - ISP Status Page

Guia rápido para iniciar o desenvolvimento.

## 🚀 Início Rápido (Docker - Recomendado)

### 1. Um comando para rodar tudo!

```bash
make quick-start
```

Isso vai:
- Build da imagem Docker
- Iniciar containers (app + cron)
- Executar migrations (quando disponíveis)
- Executar seeds (quando disponíveis)

**Pronto!** Acesse: http://localhost:8765

### 2. Comandos úteis

```bash
# Ver logs
make logs

# Acessar shell do container
make shell

# Executar migrations (quando criadas)
make migrate

# Executar seeds (quando criados)
make seed

# Parar ambiente
make dev-down

# Ver todos os comandos
make help
```

## 📝 O que foi feito

### ✅ Documentação Completa
- **README.md**: Overview do projeto
- **docs/ARCHITECTURE.md**: Arquitetura do sistema
- **docs/DATABASE.md**: Schema completo (11 tabelas)
- **docs/API_INTEGRATIONS.md**: IXC, Zabbix, REST API
- **docs/DEVELOPMENT_PLAN.md**: Plano modular (4 fases)
- **docs/TASKS.md**: 40+ tarefas detalhadas
- **docs/DOCKER.md**: Documentação Docker completa
- **docs/DATABASE_MIGRATION.md**: Migração entre bancos
- **docs/INSTALL.md**: Instalação manual
- **docs/QUICKSTART.md**: Guia rápido
- **CONTRIBUTING.md**: Guia de contribuição

### ✅ Ambiente de Desenvolvimento
- **CakePHP 5.2.9** instalado
- **SQLite** configurado
- **Docker** pronto para uso
- **Makefile** com comandos simplificados
- **.gitignore** completo

### ✅ Suporte Multi-Database
- SQLite (padrão)
- MySQL/MariaDB
- PostgreSQL

Configuração via `DATABASE_URL` no `.env`

### ✅ Docker Completo
- Desenvolvimento: `docker-compose up`
- Produção: `docker-compose -f docker-compose.prod.yml up`
- Cron automático para monitoramento
- Health checks configurados
- Logs otimizados

## 🎯 Próximos Passos

### 1. Iniciar o Ambiente

```bash
# Com Docker (recomendado)
make quick-start

# Ou sem Docker
cd src
composer install
cp config/app_local.example.php config/app_local.php
bin/cake server
```

### 2. Começar o Desenvolvimento

Escolha uma tarefa de `docs/TASKS.md`:

#### Prioridade Alta - Começar por aqui:

**TASK-100: Migration de Users**
```bash
make shell
bin/cake bake migration CreateUsers
```

Edite a migration conforme `docs/DATABASE.md` e execute:
```bash
make migrate
```

**TASK-110: Migration de Settings**
```bash
bin/cake bake migration CreateSettings
# Editar conforme docs/DATABASE.md
make migrate
```

**TASK-130 a TASK-170: Migrations Core**
Criar migrations para:
- Monitors
- MonitorChecks
- Incidents
- Subscribers
- Subscriptions
- Integrations
- IntegrationLogs
- AlertRules
- AlertLogs

#### Depois das Migrations:

**TASK-101: User Model**
```bash
bin/cake bake model Users
# Implementar validações
```

**TASK-102: Sistema de Autenticação**
```bash
composer require cakephp/authentication
# Implementar conforme TASK-102
```

**TASK-120: Layout Admin**
```bash
# Criar templates/layout/admin.php
# Usar Tailwind CSS ou Bootstrap
```

### 3. Distribuir Trabalho (Múltiplos Desenvolvedores)

**Dev 1 - Backend**
```
TASK-100 a 103: Auth
TASK-130 a 170: Migrations
TASK-200 a 202: Monitors
TASK-210 a 214: Check Engine
```

**Dev 2 - Frontend**
```
TASK-120 a 121: Layouts
TASK-230 a 231: Status Page
TASK-400: Dashboard
```

**Dev 3 - Serviços**
```
TASK-110 a 112: Settings
TASK-220 a 221: Incidents
TASK-240 a 241: Subscribers
TASK-250 a 251: Alertas
```

**Dev 4 - Integrações**
```
TASK-300: Integration Interface
TASK-301: IXC Adapter
TASK-302: Zabbix Adapter
TASK-303: REST API Adapter
```

## 📚 Documentação Essencial

Leia nesta ordem:

1. **docs/QUICKSTART.md** - Início rápido para devs
2. **docs/ARCHITECTURE.md** - Entenda a arquitetura
3. **docs/DATABASE.md** - Estrutura do banco
4. **docs/TASKS.md** - Escolha sua tarefa
5. **docs/DOCKER.md** - Comandos Docker
6. **CONTRIBUTING.md** - Padrões de código

## 🔧 Comandos Principais

### Docker

```bash
make quick-start     # Setup completo
make dev             # Iniciar desenvolvimento
make dev-down        # Parar
make logs            # Ver logs
make shell           # Acessar container
make clean           # Limpar cache
```

### Banco de Dados

```bash
make migrate         # Executar migrations
make migrate-status  # Ver status
make seed            # Executar seeds
make db-reset        # Reset (CUIDADO!)
make backup          # Backup
```

### Desenvolvimento

```bash
make test            # Rodar testes
make cs-check        # Verificar código
make cs-fix          # Corrigir código
make console         # Console CakePHP
```

### Bake (Geradores)

```bash
make bake ARGS="model User"
make bake ARGS="controller Users"
make bake ARGS="migration CreateUsers"
```

## 🗂️ Estrutura do Projeto

```
isp_status_page/
├── docs/              # 📚 Toda a documentação
├── docker/            # 🐳 Configs Docker
├── src/               # 💻 Código CakePHP
│   ├── bin/           # Scripts CLI
│   ├── config/        # Configurações
│   ├── database.db    # SQLite (não vai pro git)
│   ├── src/           # Código da aplicação
│   │   ├── Controller/
│   │   ├── Model/
│   │   ├── Service/   # A criar
│   │   ├── Command/   # A criar
│   │   └── Integration/ # A criar
│   ├── templates/     # Views
│   └── tests/         # Testes
├── Dockerfile         # Build da imagem
├── docker-compose.yml # Ambiente dev
├── Makefile           # Comandos simplificados
└── README.md          # Overview
```

## 🎨 Padrões de Código

### PHP
- PSR-12 compliance
- CakePHP conventions
- PHPDoc em todos os métodos públicos
- Testes para lógica de negócio

### JavaScript
- ES6+ syntax
- Comentários claros
- Alpine.js para interatividade

### CSS
- Tailwind CSS (recomendado) ou Bootstrap
- BEM naming se CSS customizado

## 🧪 Testes

```bash
# Todos os testes
make test

# Teste específico
make shell
vendor/bin/phpunit tests/TestCase/Model/Table/UsersTableTest.php

# Com coverage
make test-coverage
```

## 🔐 Segurança

- **Nunca** commite `.env` ou `app_local.php`
- **Nunca** commite `database.db`
- **Nunca** commite credenciais
- Gere security salt com:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

## 🐛 Troubleshooting

### Porta 8765 em uso
```yaml
# docker-compose.yml
ports:
  - "8080:80"  # Mudar para outra porta
```

### Permissões
```bash
make shell-root
chmod -R 777 tmp logs
chmod 666 database.db
```

### Container não inicia
```bash
make logs
make dev-down
make dev-build
```

## 📞 Ajuda

- **Documentação**: `docs/`
- **Comandos**: `make help`
- **Issues**: GitHub Issues
- **Contribuir**: `CONTRIBUTING.md`

## 🎯 Timeline Sugerido

### Semana 1-2: Fundação
- Setup (TASK-000) ✅ **FEITO**
- Migrations (TASK-100, 110, 130-170)
- Auth (TASK-101, 102, 103)
- Layouts (TASK-120, 121)

### Semana 3-4: Core
- Monitores (TASK-200, 201, 202)
- Check Engine (TASK-210-214)
- Status Page (TASK-230, 231)

### Semana 5-6: Features
- Incidentes (TASK-220, 221)
- Alertas (TASK-250, 251)
- Subscribers (TASK-240, 241)

### Semana 7-8: Integrações
- IXC (TASK-301)
- Zabbix (TASK-302)
- REST API (TASK-303)

## ✅ Checklist Inicial

- [x] CakePHP instalado
- [x] Docker configurado
- [x] Documentação completa
- [x] .gitignore configurado
- [x] Makefile com comandos
- [ ] Migrations criadas
- [ ] Seeds criados
- [ ] Auth implementado
- [ ] Layouts prontos
- [ ] Primeiro monitor funcional

## 🎉 Começar Agora!

```bash
# 1. Clone (se ainda não fez)
git clone https://github.com/seu-usuario/isp_status_page.git
cd isp_status_page

# 2. Inicie o ambiente
make quick-start

# 3. Acesse
open http://localhost:8765

# 4. Comece a desenvolver!
# Escolha uma tarefa em docs/TASKS.md
```

---

**Ambiente pronto! Bom desenvolvimento! 🚀**

**Dúvidas?** Consulte `make help` ou leia `docs/QUICKSTART.md`
