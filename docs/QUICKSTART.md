# Quick Start Guide

Guia rápido para começar a desenvolver no ISP Status Page.

## Para Desenvolvedores

### 1. Pegue uma Tarefa

```bash
# Veja tarefas disponíveis
cat docs/TASKS.md

# Escolha uma tarefa marcada com 🔴 (não iniciada)
# Exemplo: TASK-100 - Migration de Users
```

### 2. Verifique Dependências

Cada tarefa lista suas dependências. Certifique-se de que estão completas.

**Exemplo**:
- TASK-100 depende de TASK-000 (Setup)
- Se TASK-000 não está feita, faça primeiro ou espere

### 3. Leia a Documentação Relevante

```bash
# Arquitetura geral
cat docs/ARCHITECTURE.md

# Estrutura do banco
cat docs/DATABASE.md

# APIs externas
cat docs/API_INTEGRATIONS.md

# Plano de desenvolvimento
cat docs/DEVELOPMENT_PLAN.md
```

### 4. Configure o Ambiente (se ainda não fez)

```bash
# Instale dependências
composer install

# Configure .env
cp .env.example .env
nano .env

# Execute migrations (se já existirem)
bin/cake migrations migrate

# Execute seeds
bin/cake migrations seed

# Inicie servidor
bin/cake server
```

### 5. Crie sua Branch

```bash
git checkout -b task-100-users-migration
```

### 6. Desenvolva

Siga os critérios de aceite da tarefa.

**Exemplo para TASK-100**:
```bash
# Criar migration
bin/cake bake migration CreateUsers

# Editar migration com campos corretos
# Ver docs/DATABASE.md para estrutura

# Testar migration
bin/cake migrations migrate
bin/cake migrations rollback
bin/cake migrations migrate
```

### 7. Teste

```bash
# Execute testes
vendor/bin/phpunit

# Verifique código
vendor/bin/phpcs src/ --standard=PSR12
```

### 8. Commit e PR

```bash
git add .
git commit -m "TASK-100: Create users migration

- Add users table with all required fields
- Add indexes for username and email
- Add foreign key constraints
- Tested migration and rollback"

git push origin task-100-users-migration
```

Abra PR no GitHub.

## Para Gerentes de Projeto

### Distribuir Tarefas

**Cenário**: 3 desenvolvedores disponíveis

```
Dev 1: Backend/Database
- TASK-000: Setup
- TASK-100-103: Auth system
- TASK-130-170: Migrations

Dev 2: Frontend/UI
- TASK-120-121: Layouts
- TASK-230-231: Status Page

Dev 3: Serviços/Integração
- TASK-110-112: Settings
- TASK-210-214: Check Engine
```

### Acompanhar Progresso

```bash
# Ver status das tarefas
cat docs/TASKS.md | grep "🟡"  # Em progresso
cat docs/TASKS.md | grep "🟢"  # Completas
cat docs/TASKS.md | grep "🔴"  # Não iniciadas
```

### Definir Prioridades

Fases recomendadas (docs/DEVELOPMENT_PLAN.md):
1. **Semana 1-2**: Setup + Auth + Layouts + Migrations
2. **Semana 3-4**: Monitores + Check Engine + Status Page
3. **Semana 5-6**: Incidentes + Alertas + Subscribers
4. **Semana 7-8**: Integrações IXC/Zabbix

## Para Agentes de IA

### Prompt Sugerido

```
Você é um desenvolvedor trabalhando no projeto ISP Status Page.

Contexto do projeto: [paste de README.md]

Sua tarefa: TASK-XXX - [descrição da tarefa]

Documentos relevantes:
- docs/ARCHITECTURE.md
- docs/DATABASE.md
- docs/TASKS.md

Por favor:
1. Leia a tarefa e seus critérios de aceite
2. Verifique dependências
3. Implemente conforme especificação
4. Crie testes
5. Documente o código
6. Siga PSR-12

Comece!
```

### Exemplo Prático

```
Agente, implemente TASK-100: Migration de Users

Requisitos:
- Criar migration para tabela users
- Campos conforme docs/DATABASE.md
- Índices necessários
- Testar migration e rollback

Entregue:
- Arquivo de migration
- Comando para executar
- Confirmação de que funciona
```

## Estrutura de Arquivos Importante

```
isp_status_page/
├── docs/                   # 📚 LEIA PRIMEIRO
│   ├── ARCHITECTURE.md     # Arquitetura do sistema
│   ├── DATABASE.md         # Estrutura do banco
│   ├── API_INTEGRATIONS.md # APIs externas
│   ├── DEVELOPMENT_PLAN.md # Plano completo
│   ├── TASKS.md           # Tarefas detalhadas
│   └── INSTALL.md         # Guia de instalação
├── src/                   # Código da aplicação
│   ├── Controller/        # Controllers
│   ├── Model/            # Models/Entities
│   ├── Service/          # Business logic
│   ├── Command/          # CLI commands
│   └── Integration/      # Adapters externos
├── templates/            # Views
├── tests/               # Testes
├── config/              # Configurações
├── .env.example         # Exemplo de configuração
└── README.md           # Visão geral

```

## Comandos Úteis

### Desenvolvimento

```bash
# Servidor dev
bin/cake server

# Console interativo
bin/cake console

# Ver rotas
bin/cake routes

# Limpar cache
bin/cake cache clear_all
```

### Database

```bash
# Status das migrations
bin/cake migrations status

# Executar migrations
bin/cake migrations migrate

# Rollback última migration
bin/cake migrations rollback

# Executar seeds
bin/cake migrations seed

# Criar migration
bin/cake bake migration CreateTableName
```

### Bake (Gerador)

```bash
# Gerar model
bin/cake bake model ModelName

# Gerar controller
bin/cake bake controller ControllerName

# Gerar tudo (model + controller + views)
bin/cake bake all ModelName
```

### Testes

```bash
# Todos os testes
vendor/bin/phpunit

# Teste específico
vendor/bin/phpunit tests/TestCase/Model/Table/UsersTableTest.php

# Com coverage
vendor/bin/phpunit --coverage-html tmp/coverage

# Só um método
vendor/bin/phpunit --filter testMethodName
```

### Code Quality

```bash
# Verificar PSR-12
vendor/bin/phpcs src/

# Corrigir automaticamente
vendor/bin/phpcbf src/

# Static analysis (se configurado)
vendor/bin/phpstan analyse src/
```

## Convenções Importantes

### Naming

```php
// Tabelas: plural, snake_case
monitors, monitor_checks, alert_rules

// Models: Singular, PascalCase
Monitor, MonitorCheck, AlertRule

// Controllers: Plural, PascalCase + Controller
MonitorsController, MonitorChecksController

// Actions: camelCase
index, view, add, edit, delete

// Variables: camelCase
$monitorStatus, $checkResult

// Constants: UPPER_SNAKE_CASE
MAX_RETRY_COUNT, DEFAULT_TIMEOUT
```

### Arquivos

```
# Migration
YYYYMMDDHHMMSS_CreateTableName.php

# Model
src/Model/Entity/EntityName.php
src/Model/Table/EntityNamesTable.php

# Controller
src/Controller/ControllerNameController.php
src/Controller/Admin/ControllerNameController.php

# View
templates/ControllerName/action_name.php
templates/Admin/ControllerName/action_name.php

# Test
tests/TestCase/Model/Table/EntityNameTableTest.php
```

## Próximos Passos

1. **Se você é novo**: Comece com TASK-000 (Setup)
2. **Se quer backend**: Vá para TASK-100+ (Models e Services)
3. **Se quer frontend**: Vá para TASK-120+ (Layouts e Views)
4. **Se quer integrações**: Aguarde TASK-300+ (requer base pronta)

## Dúvidas?

- 📖 Leia [CONTRIBUTING.md](../CONTRIBUTING.md)
- 🏗️ Consulte [ARCHITECTURE.md](ARCHITECTURE.md)
- 📋 Veja [TASKS.md](TASKS.md)
- 💬 Abra uma issue no GitHub

---

**Boa sorte e bom código! 🚀**
