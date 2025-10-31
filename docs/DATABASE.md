# Estrutura de Banco de Dados

## Visão Geral

O sistema utiliza SQLite como banco de dados, proporcionando simplicidade e portabilidade. As tabelas seguem as convenções do CakePHP para aproveitar ao máximo o ORM.

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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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
    id INTEGER PRIMARY KEY AUTOINCREMENT,
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

## Migrations CakePHP

As migrations serão criadas na ordem:
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

### Vacuum SQLite
Executar `VACUUM` semanalmente para otimizar o banco.

## Backup

Script de backup automático:
```bash
#!/bin/bash
cp database.db backups/database_$(date +%Y%m%d_%H%M%S).db
# Manter apenas últimos 30 backups
```

## Migrações Futuras

Se necessário migrar para PostgreSQL/MySQL:
- CakePHP facilita a migração
- Apenas ajustar tipos de dados específicos (TEXT para JSON nativo, etc)
- Migrations existentes podem ser adaptadas

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

### Próximos monitores a verificar
```sql
SELECT * FROM monitors
WHERE active = 1
AND next_check_at <= datetime('now')
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
WHERE checked_at >= datetime('now', '-30 days')
GROUP BY monitor_id;
```
