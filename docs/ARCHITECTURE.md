# Arquitetura do Sistema

## Visão Geral

O ISP Status Page é construído com uma arquitetura simples e eficiente, focada em confiabilidade e facilidade de manutenção.

## Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────┐
│                     ISP Status Page                          │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌────────────────┐         ┌──────────────────┐            │
│  │  Public Status │         │  Admin Panel     │            │
│  │  Page          │         │  (CakePHP Admin) │            │
│  └────────┬───────┘         └────────┬─────────┘            │
│           │                          │                       │
│           └──────────┬───────────────┘                       │
│                      │                                       │
│           ┌──────────▼──────────┐                            │
│           │   CakePHP MVC       │                            │
│           │   Controllers       │                            │
│           │   Models            │                            │
│           │   Views             │                            │
│           └──────────┬──────────┘                            │
│                      │                                       │
│           ┌──────────▼──────────┐                            │
│           │   Business Logic    │                            │
│           │   - Monitor Service │                            │
│           │   - Alert Service   │                            │
│           │   - Check Service   │                            │
│           └──────────┬──────────┘                            │
│                      │                                       │
│           ┌──────────▼──────────┐                            │
│           │   SQLite Database   │                            │
│           │   - Monitors        │                            │
│           │   - Checks          │                            │
│           │   - Incidents       │                            │
│           │   - Subscribers     │                            │
│           └─────────────────────┘                            │
└─────────────────────────────────────────────────────────────┘
                      │
                      │  External APIs
         ┌────────────┼────────────┐
         │            │            │
    ┌────▼───┐  ┌────▼───┐  ┌────▼────┐
    │  IXC   │  │ Zabbix │  │ Custom  │
    │  API   │  │  API   │  │ REST    │
    └────────┘  └────────┘  └─────────┘
```

## Camadas da Aplicação

### 1. Camada de Apresentação

#### Public Status Page
- **Rota**: `/` ou `/status`
- **Função**: Exibir status atual de todos os serviços monitorados
- **Comportamento**:
  - Status OK: HTTP 200, UI verde mostrando todos serviços operacionais
  - Status com problemas: HTTP 500/503, UI vermelha mostrando serviços afetados
- **Atualização**: Dados em tempo real do banco de dados

#### Admin Panel
- **Rota**: `/admin`
- **Função**: Gerenciamento completo do sistema
- **Recursos**:
  - CRUD de monitores
  - Configuração de integrações
  - Gerenciamento de subscribers
  - Visualização de histórico
  - Configurações gerais

### 2. Camada de Negócio

#### Monitor Service
- Gerencia a configuração de monitores
- Define tipos de monitoramento (ping, http, api, etc)
- Configura intervalos de verificação

#### Check Service
- Executa verificações conforme agendamento
- Processa resultados
- Determina status (UP/DOWN/DEGRADED)
- Calcula métricas (latência, uptime, etc)

#### Alert Service
- Detecta mudanças de estado
- Gerencia regras de notificação
- Envia alertas via diferentes canais
- Controla throttling de alertas

#### Integration Service
- Abstração para APIs externas
- IXC Adapter
- Zabbix Adapter
- Generic REST Adapter

### 3. Camada de Dados

#### SQLite Database
- Arquivo único `database.db`
- Transações ACID
- Backup simples (cópia de arquivo)
- Sem necessidade de servidor de BD

## Fluxo de Monitoramento

```
┌─────────────────┐
│  Cron (30s)     │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────┐
│  MonitorCheckCommand        │
│  (CakePHP Shell/Command)    │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│  Buscar monitores ativos    │
│  (que estão na janela)      │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│  Para cada monitor:         │
│  1. Executar verificação    │
│  2. Registrar resultado     │
│  3. Atualizar status        │
│  4. Verificar alertas       │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│  Se mudança de estado:      │
│  1. Criar/Atualizar incident│
│  2. Disparar notificações   │
└─────────────────────────────┘
```

## Janelas de Monitoramento

Cada monitor tem configuração de janela:
- **check_interval**: Intervalo entre verificações (ex: 60 segundos)
- **last_check_at**: Timestamp da última verificação
- **next_check_at**: Timestamp calculado para próxima verificação

O cron a cada 30s verifica quais monitores têm `next_check_at <= NOW()`.

## Sistema de Status HTTP

A página de status retorna códigos HTTP baseados no estado geral:

- **200 OK**: Todos os serviços operacionais
- **207 Multi-Status**: Alguns serviços degradados (opcional)
- **503 Service Unavailable**: Serviços críticos fora do ar
- **500 Internal Server Error**: Erro no próprio sistema de monitoramento

## Tipos de Monitores

### 1. HTTP/HTTPS Monitor
- Verificação de URL
- Validação de código de status
- Timeout configurável
- Verificação de conteúdo (opcional)

### 2. Ping Monitor
- ICMP ping
- Verificação de latência
- Packet loss

### 3. Port Monitor
- Verificação de porta TCP/UDP
- Timeout configurável

### 4. API Monitor
- Chamada a API REST
- Validação de resposta JSON
- Headers customizados

### 5. IXC Integration
- Consulta status de serviços no IXC
- Verificação de tickets
- Status de equipamentos

### 6. Zabbix Integration
- Consulta triggers do Zabbix
- Status de hosts
- Métricas específicas

## Sistema de Alertas

### Canais de Notificação

#### Fase 1 (MVP)
- **Email**: Via CakePHP Email component
  - SMTP configurável
  - Templates de email
  - Suporte a múltiplos destinatários

#### Fase 2+
- **WhatsApp**: Via API do WhatsApp Business
- **Telegram**: Via Telegram Bot API
- **SMS**: Via gateway de SMS
- **Telefone**: Via sistema de telefonia/IVR

### Regras de Notificação

- **on_down**: Quando serviço cai
- **on_up**: Quando serviço volta
- **on_degraded**: Quando serviço fica degradado
- **throttle**: Intervalo mínimo entre alertas (ex: não enviar mais que 1 por 5 min)

## Segurança

### Admin Panel
- Autenticação obrigatória
- Controle de acesso baseado em roles
- Proteção CSRF (CakePHP built-in)
- SQL Injection protection (ORM)

### Public Status Page
- Acesso público (sem autenticação)
- Rate limiting (prevenir abuse)
- Cache de resultados

### API Integrations
- Credenciais criptografadas no banco
- Tokens com expiração
- Logs de acesso

## Performance

### Otimizações
- Cache de status (30s - 1min)
- Índices no banco de dados
- Lazy loading de relações
- Query optimization

### Escalabilidade
- SQLite suporta até ~1000 writes/s
- Para mais, migração para PostgreSQL/MySQL é direta no CakePHP
- Monitores podem ser distribuídos (futura implementação)

## Backup e Recuperação

### Backup
- Cópia automática diária do `database.db`
- Rotação de backups (manter últimos 30 dias)
- Export de configurações em JSON/YAML

### Recuperação
- Restauração do arquivo SQLite
- Import de configurações
- Logs para auditoria

## Ambiente de Desenvolvimento

### Requisitos
- PHP 8.1+
- Composer
- SQLite3
- Cron ou scheduler equivalente

### Estrutura de Diretórios (CakePHP)
```
isp_status_page/
├── bin/               # Scripts e comandos
├── config/            # Configurações
├── logs/              # Logs da aplicação
├── plugins/           # Plugins customizados
├── src/               # Código da aplicação
│   ├── Command/       # CLI commands (cron jobs)
│   ├── Controller/    # Controllers
│   ├── Model/         # Models e Entities
│   ├── Service/       # Business logic
│   └── View/          # Templates
├── templates/         # Views
├── tests/             # Testes
├── tmp/               # Cache e arquivos temporários
├── vendor/            # Dependências
├── webroot/           # Arquivos públicos
└── database.db        # SQLite database
```

## Próximos Passos

1. Implementar estrutura básica do CakePHP
2. Criar migrations para tabelas do banco
3. Desenvolver Models e Entities
4. Implementar CRUD básico no admin
5. Criar comando de monitoramento
6. Desenvolver página de status pública
7. Implementar sistema de alertas por email
