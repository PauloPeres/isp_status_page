# Estrutura de Banco de Dados

## Visão Geral

O sistema utiliza PostgreSQL 16 como banco de dados principal, oferecendo robustez, concorrencia e recursos avancados como JSONB. O SQLite ainda e utilizado na suite de testes (PHPUnit) para manter os testes rapidos e isolados. As tabelas seguem as convencoes do CakePHP para aproveitar ao maximo o ORM.

## Diagrama de Relacionamentos (ERD)

```
┌─────────────────┐         ┌──────────────────┐
│     monitors    │◄───────┤   monitor_checks │
│─────────────────│   1:N   │──────────────────│
│ id (PK)         │         │ id (PK)          │
│ name            │         │ monitor_id (FK)  │
│ type            │         │ status           │
│ configuration   │         │ response_time    │
│ check_interval  │         │ checked_at       │
│ status          │         │ error_message    │
│ ...             │         │ created          │
└────────┬────────┘         └──────────────────┘
         │
         │ 1:N
         │
┌────────▼────────┐
│   incidents     │
│─────────────────│
│ id (PK)         │
│ monitor_id (FK) │
│ title           │
│ description     │
│ status          │
│ started_at      │
│ resolved_at     │
│ severity        │
└─────────────────┘

┌─────────────────┐         ┌──────────────────┐
│  subscribers    │         │  subscriptions   │
│─────────────────│         │──────────────────│
│ id (PK)         │◄───────┤ id (PK)          │
│ email           │   1:N   │ subscriber_id(FK)│
│ name            │         │ monitor_id (FK)  │◄────┐
│ verified        │         │ created          │     │
│ verification_tk │         │ modified         │     │
│ active          │         └──────────────────┘     │
│ created         │                                  │
└─────────────────┘                                  │
                                                     │ N:1
                            ┌────────────────────────┘
                            │
┌─────────────────┐         │
│   integrations  │         │
│─────────────────│         │
│ id (PK)         │         │
│ name            │         │
│ type            │         │
│ configuration   │         │
│ active          │         │
│ last_sync       │         │
└─────────────────┘         │
         │                  │
         │ 1:N              │
         │                  │
┌────────▼────────┐         │
│integration_logs │         │
│─────────────────│         │
│ id (PK)         │         │
│ integration_id  │         │
│ action          │         │
│ status          │         │
│ message         │         │
│ created         │         │
└─────────────────┘         │
                            │
┌───────────────────────────┘
│
│     ┌─────────────────┐
└────►│   monitors      │
      └─────────────────┘

┌─────────────────┐
│  alert_rules    │
│─────────────────│
│ id (PK)         │
│ monitor_id (FK) │◄────┐
│ channel         │     │
│ trigger_on      │     │
│ throttle_mins   │     │
│ recipients      │     │
│ active          │     │
└─────────────────┘     │
                        │ N:1
                        │
         ┌──────────────┘
         │
┌────────┴────────┐
│   monitors      │
└─────────────────┘

┌─────────────────┐
│  alert_logs     │
│─────────────────│
│ id (PK)         │
│ alert_rule_id   │
│ incident_id     │
│ channel         │
│ recipient       │
│ status          │
│ sent_at         │
│ error_message   │
└─────────────────┘

┌─────────────────┐
│  settings       │
│─────────────────│
│ id (PK)         │
│ key             │
│ value           │
│ type            │
│ modified        │
└─────────────────┘

┌─────────────────┐
│  users          │
│─────────────────│
│ id (PK)         │
│ username        │
│ password        │
│ email           │
│ role            │
│ active          │
│ created         │
│ modified        │
└─────────────────┘
```

## Tabelas Detalhadas

### 1. monitors

Armazena a configuração de todos os monitores do sistema.

```sql
CREATE TABLE monitors (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    type VARCHAR(50) NOT NULL,  -- 'http', 'ping', 'port', 'api', 'ixc', 'zabbix'
    configuration TEXT,  -- JSON com configurações específicas do tipo
    check_interval INTEGER NOT NULL DEFAULT 60,  -- segundos
    timeout INTEGER DEFAULT 30,  -- segundos
    retry_count INTEGER DEFAULT 3,
    status VARCHAR(20) DEFAULT 'unknown',  -- 'up', 'down', 'degraded', 'unknown'
    last_check_at DATETIME,
    next_check_at DATETIME,
    uptime_percentage DECIMAL(5,2),
    active BOOLEAN DEFAULT 1,
    visible_on_status_page BOOLEAN DEFAULT 1,
    display_order INTEGER DEFAULT 0,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL
);

CREATE INDEX idx_monitors_status ON monitors(status);
CREATE INDEX idx_monitors_next_check ON monitors(next_check_at);
CREATE INDEX idx_monitors_active ON monitors(active);
```

**Campos configuration (JSON) por tipo:**

#### HTTP Monitor
```json
{
    "url": "https://example.com/api/health",
    "method": "GET",
    "headers": {
        "Authorization": "Bearer token"
    },
    "expected_status": [200, 201],
    "expected_content": "ok",
    "ssl_verify": true
}
```

#### Ping Monitor
```json
{
    "host": "8.8.8.8",
    "packet_count": 4,
    "max_latency": 100
}
```

#### Port Monitor
```json
{
    "host": "example.com",
    "port": 3306,
    "protocol": "tcp"
}
```

#### IXC Integration
```json
{
    "integration_id": 1,
    "endpoint": "service_status",
    "service_id": "12345"
}
```

### 2. monitor_checks

Histórico de todas as verificações executadas.

```sql
CREATE TABLE monitor_checks (
    id SERIAL PRIMARY KEY,
    monitor_id INTEGER NOT NULL,
    status VARCHAR(20) NOT NULL,  -- 'success', 'failure', 'timeout', 'error'
    response_time INTEGER,  -- milisegundos
    status_code INTEGER,  -- HTTP status ou similar
    error_message TEXT,
    details TEXT,  -- JSON com detalhes da verificação
    checked_at DATETIME NOT NULL,
    created DATETIME NOT NULL,
    FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE
);

CREATE INDEX idx_monitor_checks_monitor ON monitor_checks(monitor_id);
CREATE INDEX idx_monitor_checks_date ON monitor_checks(checked_at);
```

**Retenção de dados**: Manter últimos 30 dias (configurável)

### 3. incidents

Registra incidentes quando serviços ficam indisponíveis.

```sql
CREATE TABLE incidents (
    id SERIAL PRIMARY KEY,
    monitor_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status VARCHAR(20) NOT NULL,  -- 'investigating', 'identified', 'monitoring', 'resolved'
    severity VARCHAR(20) NOT NULL,  -- 'critical', 'major', 'minor', 'maintenance'
    started_at DATETIME NOT NULL,
    identified_at DATETIME,
    resolved_at DATETIME,
    duration INTEGER,  -- segundos (calculado)
    auto_created BOOLEAN DEFAULT 1,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL,
    FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE
);

CREATE INDEX idx_incidents_monitor ON incidents(monitor_id);
CREATE INDEX idx_incidents_status ON incidents(status);
CREATE INDEX idx_incidents_started ON incidents(started_at);
```

### 4. subscribers

Usuários que se inscrevem para receber notificações.

```sql
CREATE TABLE subscribers (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255),
    verification_token VARCHAR(255),
    verified BOOLEAN DEFAULT 0,
    verified_at DATETIME,
    active BOOLEAN DEFAULT 1,
    unsubscribe_token VARCHAR(255),
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL
);

CREATE INDEX idx_subscribers_email ON subscribers(email);
CREATE INDEX idx_subscribers_active ON subscribers(active, verified);
```

### 5. subscriptions

Relaciona subscribers com monitors específicos.

```sql
CREATE TABLE subscriptions (
    id SERIAL PRIMARY KEY,
    subscriber_id INTEGER NOT NULL,
    monitor_id INTEGER,  -- NULL = todos os monitores
    notify_on_down BOOLEAN DEFAULT 1,
    notify_on_up BOOLEAN DEFAULT 1,
    notify_on_degraded BOOLEAN DEFAULT 0,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL,
    FOREIGN KEY (subscriber_id) REFERENCES subscribers(id) ON DELETE CASCADE,
    FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE
);

CREATE INDEX idx_subscriptions_subscriber ON subscriptions(subscriber_id);
CREATE INDEX idx_subscriptions_monitor ON subscriptions(monitor_id);
```

### 6. integrations

Configuração de integrações com sistemas externos.

```sql
CREATE TABLE integrations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,  -- 'ixc', 'zabbix', 'rest_api'
    configuration TEXT NOT NULL,  -- JSON encriptado
    active BOOLEAN DEFAULT 1,
    last_sync_at DATETIME,
    last_sync_status VARCHAR(20),
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL
);

CREATE INDEX idx_integrations_type ON integrations(type);
CREATE INDEX idx_integrations_active ON integrations(active);
```

**Campos configuration (JSON encriptado):**

#### IXC
```json
{
    "base_url": "https://ixc.example.com/api",
    "api_token": "encrypted_token",
    "api_version": "v1"
}
```

#### Zabbix
```json
{
    "base_url": "https://zabbix.example.com/api_jsonrpc.php",
    "username": "api_user",
    "password": "encrypted_password"
}
```

### 7. integration_logs

Log de sincronizações com sistemas externos.

```sql
CREATE TABLE integration_logs (
    id SERIAL PRIMARY KEY,
    integration_id INTEGER NOT NULL,
    action VARCHAR(100) NOT NULL,
    status VARCHAR(20) NOT NULL,  -- 'success', 'error', 'warning'
    message TEXT,
    details TEXT,  -- JSON
    created DATETIME NOT NULL,
    FOREIGN KEY (integration_id) REFERENCES integrations(id) ON DELETE CASCADE
);

CREATE INDEX idx_integration_logs_integration ON integration_logs(integration_id);
CREATE INDEX idx_integration_logs_created ON integration_logs(created);
```

**Retenção**: Últimos 7 dias

### 8. alert_rules

Regras de notificação para cada monitor.

```sql
CREATE TABLE alert_rules (
    id SERIAL PRIMARY KEY,
    monitor_id INTEGER NOT NULL,
    channel VARCHAR(50) NOT NULL,  -- 'email', 'whatsapp', 'telegram', 'sms', 'phone'
    trigger_on VARCHAR(50) NOT NULL,  -- 'on_down', 'on_up', 'on_degraded', 'on_change'
    throttle_minutes INTEGER DEFAULT 5,
    recipients TEXT NOT NULL,  -- JSON array de destinatários
    template TEXT,  -- Template customizado (opcional)
    active BOOLEAN DEFAULT 1,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL,
    FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE
);

CREATE INDEX idx_alert_rules_monitor ON alert_rules(monitor_id);
CREATE INDEX idx_alert_rules_active ON alert_rules(active);
```

### 9. alert_logs

Log de alertas enviados.

```sql
CREATE TABLE alert_logs (
    id SERIAL PRIMARY KEY,
    alert_rule_id INTEGER NOT NULL,
    incident_id INTEGER,
    monitor_id INTEGER NOT NULL,
    channel VARCHAR(50) NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL,  -- 'sent', 'failed', 'queued'
    sent_at DATETIME,
    error_message TEXT,
    created DATETIME NOT NULL,
    FOREIGN KEY (alert_rule_id) REFERENCES alert_rules(id) ON DELETE CASCADE,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE SET NULL,
    FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE
);

CREATE INDEX idx_alert_logs_rule ON alert_logs(alert_rule_id);
CREATE INDEX idx_alert_logs_incident ON alert_logs(incident_id);
CREATE INDEX idx_alert_logs_created ON alert_logs(created);
```

**Retenção**: Últimos 30 dias

### 10. settings

Configurações gerais do sistema.

```sql
CREATE TABLE settings (
    id SERIAL PRIMARY KEY,
    key VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    type VARCHAR(20) DEFAULT 'string',  -- 'string', 'integer', 'boolean', 'json'
    description TEXT,
    modified DATETIME NOT NULL
);

CREATE INDEX idx_settings_key ON settings(key);
```

**Configurações padrão:**
```
site_name: "ISP Status"
site_url: "https://status.example.com"
email_from: "noreply@example.com"
email_from_name: "ISP Status"
smtp_host: "smtp.example.com"
smtp_port: 587
smtp_username: "user"
smtp_password: "encrypted"
default_check_interval: 60
check_retention_days: 30
log_retention_days: 7
status_page_public: true
status_page_cache_seconds: 30
```

### 11. users

Usuários do painel administrativo.

```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,  -- bcrypt hash
    email VARCHAR(255) NOT NULL UNIQUE,
    role VARCHAR(20) NOT NULL DEFAULT 'user',  -- 'admin', 'user', 'viewer'
    active BOOLEAN DEFAULT 1,
    last_login DATETIME,
    created DATETIME NOT NULL,
    modified DATETIME NOT NULL
);

CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
```

### 12. organizations (SaaS)

Tabela de organizacoes para multi-tenancy.

```sql
CREATE TABLE organizations (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    plan VARCHAR(20) NOT NULL DEFAULT 'free',  -- 'free', 'pro', 'business'
    stripe_customer_id VARCHAR(255),
    stripe_subscription_id VARCHAR(255),
    trial_ends_at TIMESTAMP,
    timezone VARCHAR(50) DEFAULT 'America/Sao_Paulo',
    language VARCHAR(10) DEFAULT 'pt_BR',
    custom_domain VARCHAR(255),
    logo_url VARCHAR(500),
    settings TEXT,  -- JSONB in PostgreSQL
    active BOOLEAN DEFAULT true,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL
);

CREATE UNIQUE INDEX idx_organizations_slug ON organizations(slug);
CREATE INDEX idx_organizations_stripe ON organizations(stripe_customer_id);
CREATE INDEX idx_organizations_domain ON organizations(custom_domain);
```

### 13. organization_users (SaaS)

Tabela de associacao entre usuarios e organizacoes com papeis.

```sql
CREATE TABLE organization_users (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role VARCHAR(20) NOT NULL DEFAULT 'member',  -- 'owner', 'admin', 'member', 'viewer'
    invited_by INTEGER,
    invited_at TIMESTAMP,
    accepted_at TIMESTAMP,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL,
    UNIQUE (organization_id, user_id)
);

CREATE INDEX idx_org_users_org ON organization_users(organization_id);
CREATE INDEX idx_org_users_user ON organization_users(user_id);
CREATE INDEX idx_org_users_role ON organization_users(role);
```

### 14. plans (SaaS)

Definicoes de planos de assinatura.

```sql
CREATE TABLE plans (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    stripe_price_id VARCHAR(255),
    monitor_limit INTEGER NOT NULL DEFAULT 5,
    check_interval_min INTEGER NOT NULL DEFAULT 300,
    retention_days INTEGER NOT NULL DEFAULT 30,
    features TEXT,  -- JSON array of feature flags
    price_monthly DECIMAL(10,2),
    price_yearly DECIMAL(10,2),
    active BOOLEAN DEFAULT true,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL
);
```

### 15. api_keys (SaaS)

Chaves de API por organizacao para acesso programatico.

```sql
CREATE TABLE api_keys (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    user_id INTEGER NOT NULL REFERENCES users(id),
    name VARCHAR(255) NOT NULL,
    key_prefix VARCHAR(10) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    scopes TEXT,  -- JSON array of allowed scopes
    last_used_at TIMESTAMP,
    expires_at TIMESTAMP,
    active BOOLEAN DEFAULT true,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL
);

CREATE INDEX idx_api_keys_org ON api_keys(organization_id);
CREATE INDEX idx_api_keys_prefix ON api_keys(key_prefix);
```

### 16. invitations (SaaS)

Convites para organizacao.

```sql
CREATE TABLE invitations (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    email VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'member',
    token VARCHAR(255) NOT NULL UNIQUE,
    invited_by INTEGER REFERENCES users(id),
    accepted_at TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL
);

CREATE INDEX idx_invitations_org ON invitations(organization_id);
CREATE INDEX idx_invitations_token ON invitations(token);
```

### 17. heartbeats (SaaS)

Monitores do tipo heartbeat (cron job ping-in).

```sql
CREATE TABLE heartbeats (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    monitor_id INTEGER NOT NULL REFERENCES monitors(id) ON DELETE CASCADE,
    token VARCHAR(255) NOT NULL UNIQUE,
    interval_seconds INTEGER NOT NULL DEFAULT 300,
    grace_seconds INTEGER NOT NULL DEFAULT 60,
    last_ping_at TIMESTAMP,
    status VARCHAR(20) DEFAULT 'waiting',  -- 'up', 'down', 'waiting'
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL
);

CREATE INDEX idx_heartbeats_token ON heartbeats(token);
CREATE INDEX idx_heartbeats_org ON heartbeats(organization_id);
```

### 18. status_pages (SaaS)

Paginas de status customizaveis por organizacao.

```sql
CREATE TABLE status_pages (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    custom_domain VARCHAR(255),
    theme TEXT,  -- JSON with color/logo/branding config
    header_text TEXT,
    footer_text TEXT,
    is_public BOOLEAN DEFAULT true,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL,
    UNIQUE (organization_id, slug)
);

CREATE INDEX idx_status_pages_org ON status_pages(organization_id);
CREATE INDEX idx_status_pages_domain ON status_pages(custom_domain);
```

### 19. maintenance_windows (SaaS)

Janelas de manutencao programadas.

```sql
CREATE TABLE maintenance_windows (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    monitor_id INTEGER REFERENCES monitors(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    scheduled_start TIMESTAMP NOT NULL,
    scheduled_end TIMESTAMP NOT NULL,
    actual_start TIMESTAMP,
    actual_end TIMESTAMP,
    status VARCHAR(20) DEFAULT 'scheduled',  -- 'scheduled', 'in_progress', 'completed', 'cancelled'
    suppress_alerts BOOLEAN DEFAULT true,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL
);

CREATE INDEX idx_maint_org ON maintenance_windows(organization_id);
CREATE INDEX idx_maint_schedule ON maintenance_windows(scheduled_start, scheduled_end);
```

### 20. webhook_endpoints (SaaS)

Endpoints de webhook configurados por organizacao.

```sql
CREATE TABLE webhook_endpoints (
    id SERIAL PRIMARY KEY,
    organization_id INTEGER NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    url VARCHAR(500) NOT NULL,
    secret VARCHAR(255) NOT NULL,
    events TEXT NOT NULL,  -- JSON array of event types
    active BOOLEAN DEFAULT true,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL
);

CREATE INDEX idx_webhook_ep_org ON webhook_endpoints(organization_id);
```

### 21. webhook_deliveries (SaaS)

Log de entregas de webhook.

```sql
CREATE TABLE webhook_deliveries (
    id SERIAL PRIMARY KEY,
    webhook_endpoint_id INTEGER NOT NULL REFERENCES webhook_endpoints(id) ON DELETE CASCADE,
    event VARCHAR(100) NOT NULL,
    payload TEXT NOT NULL,
    response_status INTEGER,
    response_body TEXT,
    attempts INTEGER DEFAULT 0,
    delivered_at TIMESTAMP,
    next_retry_at TIMESTAMP,
    created TIMESTAMP NOT NULL
);

CREATE INDEX idx_webhook_del_ep ON webhook_deliveries(webhook_endpoint_id);
CREATE INDEX idx_webhook_del_retry ON webhook_deliveries(next_retry_at);
```

### 22. check_regions (SaaS)

Regioes de verificacao distribuida.

```sql
CREATE TABLE check_regions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    location VARCHAR(255),
    endpoint_url VARCHAR(500),
    active BOOLEAN DEFAULT true,
    created TIMESTAMP NOT NULL,
    modified TIMESTAMP NOT NULL
);

CREATE INDEX idx_check_regions_slug ON check_regions(slug);
```

## Migrations CakePHP

As migrations sao criadas na ordem:
1. Users
2. Settings
3. Integrations
4. Integration_logs
5. Monitors
6. Monitor_checks
7. Incidents
8. Subscribers
9. Subscriptions
10. Alert_rules
11. Alert_logs
12. Organizations (SaaS)
13. Organization_users (SaaS)
14. Add organization_id FK to all tenant tables (SaaS)
15. Plans (SaaS)
16. Api_keys (SaaS)
17. Invitations (SaaS)
18. Heartbeats (SaaS)
19. Status_pages (SaaS)
20. Maintenance_windows (SaaS)
21. Webhook_endpoints (SaaS)
22. Webhook_deliveries (SaaS)
23. Check_regions (SaaS)

## Seeds (Dados Iniciais)

### Usuário Admin Padrão
```php
username: admin
password: admin123 (deve ser alterado no primeiro login)
email: admin@localhost
role: admin
```

### Settings Padrão
Todas as configurações listadas acima com valores padrão.

### Monitor de Exemplo
Um monitor HTTP verificando o próprio sistema (localhost/health).

## Performance e Otimização

### Índices
Todos os índices necessários estão definidos nas tabelas acima.

### Limpeza Automática
Cron jobs para:
- Deletar `monitor_checks` com mais de 30 dias
- Deletar `integration_logs` com mais de 7 dias
- Deletar `alert_logs` com mais de 30 dias

### Vacuum / Maintenance PostgreSQL
Executar `VACUUM ANALYZE` periodicamente para otimizar o banco. O autovacuum do PostgreSQL cuida da maioria dos casos automaticamente.

## Backup

Script de backup automatico (via pg_dump):
```bash
#!/bin/bash
pg_dump -h localhost -U isp_status isp_status_page > backups/database_$(date +%Y%m%d_%H%M%S).sql
# Manter apenas ultimos 30 backups
```

## Nota sobre SQLite (Testes)

O SQLite e utilizado exclusivamente na suite de testes PHPUnit para manter a execucao rapida e sem dependencias externas. As migrations sao compativeis com ambos os bancos gracas ao uso da API Phinx (sem SQL raw). Ao escrever queries raw, use sintaxe ANSI SQL compativel com PostgreSQL e SQLite.

## Queries Importantes

### Status atual de todos os monitores
```sql
SELECT * FROM monitors
WHERE active = 1
ORDER BY display_order, name;
```

### Incidentes ativos
```sql
SELECT * FROM incidents
WHERE status != 'resolved'
ORDER BY severity, started_at;
```

### Proximos monitores a verificar
```sql
SELECT * FROM monitors
WHERE active = true
AND next_check_at <= NOW()
ORDER BY next_check_at
LIMIT 100;
```

### Uptime de um monitor (últimos 30 dias)
```sql
SELECT
    monitor_id,
    COUNT(*) as total_checks,
    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_checks,
    ROUND(
        (SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) * 100.0) / COUNT(*),
        2
    ) as uptime_percentage
FROM monitor_checks
WHERE checked_at >= NOW() - INTERVAL '30 days'
GROUP BY monitor_id;
```
