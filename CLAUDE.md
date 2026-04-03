# CLAUDE.md — ISP Status Page
# Multi-Agent Autonomous Completion Guide

## 🎯 Project Overview

**ISP Status Page** is a CakePHP 5.x monitoring and status page system for Internet Service Providers (ISPs).
It integrates with IXC, Zabbix, and REST APIs to monitor services and display real-time status to customers.

**Repository**: https://github.com/PauloPeres/isp_status_page
**Current State**: SaaS platform — fully featured

**Language**: PHP 8.4 / CakePHP 5.2.9
**Database**: PostgreSQL 16 (SQLite for tests)
**Tests**: PHPUnit 10.5.58
**Container**: Docker (use `make quick-start` to boot)
**Queue**: Redis-backed (cakephp/queue) with dedicated worker containers
**Scheduler**: Long-running `bin/cake scheduler` daemon pushes due checks to queue

---

## 📁 Critical Project Structure

```
isp_status_page/
├── src/                        ← CakePHP lives HERE (all commands run from /src)
│   ├── bin/cake                ← CLI entry point
│   ├── config/
│   │   ├── Migrations/         ← Database migrations
│   │   └── Seeds/              ← Database seeders
│   ├── src/
│   │   ├── Command/            ← CLI commands (scheduler, monitor_check, cleanup, backup)
│   │   ├── Controller/         ← HTTP controllers
│   │   ├── Integration/        ← IXC, Zabbix, REST adapters
│   │   ├── Model/
│   │   │   ├── Entity/         ← Entities with helpers/constants
│   │   │   └── Table/          ← Tables with validations/finders
│   │   └── Service/            ← Business logic services
│   │       ├── Alert/          ← Alert channels (email, etc)
│   │       └── Check/          ← Monitor checkers (HTTP, Ping, Port)
│   ├── templates/              ← CakePHP views (.php)
│   │   ├── element/            ← Reusable components
│   │   │   ├── monitor/        ← Monitor form partials
│   │   │   ├── status/         ← Status page components
│   │   │   └── tooltip.php     ← Tooltip system (68+ translations)
│   │   ├── layout/
│   │   │   ├── admin.php       ← Admin layout (already done)
│   │   │   └── public.php      ← Public layout (already done)
│   │   └── email/html/         ← Email templates
│   ├── tests/
│   │   ├── TestCase/           ← PHPUnit tests
│   │   └── Fixture/            ← Test fixtures
│   └── webroot/
│       ├── css/                ← admin.css, public.css
│       └── js/                 ← monitor-form.js, charts.js
├── docs/                       ← Architecture, DB schema, API docs
│   ├── TASKS.md                ← Full task breakdown (READ THIS)
│   ├── DATABASE.md             ← Full DB schema reference
│   ├── API_INTEGRATIONS.md     ← IXC, Zabbix, REST API specs
│   └── DESIGN.md              ← Design system (colors, components)
├── docker-compose.yml
└── Makefile                    ← 30+ useful commands
```

> ⚠️ **ALL `bin/cake` commands must be run from inside `/src`**
> Use `make` from the project root as a shortcut.

---

## ✅ What Is Already Done (DO NOT REDO)

- Authentication system (login/logout, bcrypt, CSRF, sessions)
- Admin layout + sidebar + dashboard
- Public status page layout
- Monitor CRUD (HTTP, Ping, Port types) with dynamic forms
- Check engine: CheckService, HttpChecker, PingChecker, PortChecker
- MonitorCheckCommand (`bin/cake monitor_check`)
- IncidentService (auto-create/resolve on monitor status change)
- IncidentsController + views (index, view)
- ChecksController + views (index, view)
- SubscribersController (subscribe/verify/unsubscribe flow)
- SubscribersController admin (index, view, toggle, delete)
- EmailLogsController + views
- SettingsController (tabs: General, Email, Monitoring, Notifications)
- AlertRule + AlertLog models + fixtures
- Integration interface + AbstractIntegration base class
- CleanupCommand + BackupCommand
- 78+ tests passing (191 assertions)
- Docker setup with scheduler daemon, queue workers, cron fallback, migrations, seeds
- Tooltip system with 68+ translations (pt_BR, en, es)

---

## 🚧 What Needs to Be Built (YOUR MISSION)

### PRIORITY ORDER (complete in this sequence within each phase):

#### PHASE A — Alert System -- COMPLETED
1. **TASK-251**: `AlertService` + `EmailAlertChannel` -- COMPLETED
2. **TASK-260**: Incident Acknowledgement System -- COMPLETED

#### PHASE B — Integrations -- COMPLETED
3. **TASK-301**: IXC Adapter + Client + Checkers -- COMPLETED
4. **TASK-302**: Zabbix Adapter + Client + Checkers -- COMPLETED
5. **TASK-303**: REST API Generic Adapter + Checker -- COMPLETED
6. **TASK-310**: IntegrationsController (admin CRUD + test connection) -- COMPLETED

#### PHASE C — Dashboard & Maintenance -- COMPLETED
7. **TASK-400**: Admin Dashboard with charts (Chart.js) -- COMPLETED
8. **TASK-421**: Backup FTP/SFTP upload service -- COMPLETED

#### PHASE D — SaaS Transformation (see docs/SAAS_PLAN.md for details)

- **TASK-500**: PostgreSQL + Redis infrastructure -- COMPLETED
- **TASK-501**: Migration compatibility for PostgreSQL -- COMPLETED
- **TASK-600**: Organizations table (multi-tenancy) -- COMPLETED
- **TASK-601**: Organization-users join table -- COMPLETED
- **TASK-602**: organization_id FK on all tenant tables -- COMPLETED
- **TASK-603**: Tenant-scoped base table behavior -- COMPLETED

**SaaS Features:**
- Multi-tenancy with organization isolation (row-level scoping)
- Stripe billing integration (free / pro / business plans)
- JWT-based REST API authentication (firebase/php-jwt)
- Background job queue (cakephp/queue + Redis)
- Redis-backed sessions and cache
- PostgreSQL 16 as primary database
- Organization invitations and role-based access (owner, admin, member, viewer)
- Custom domains and branded status pages per organization
- Webhook endpoints and delivery tracking
- Heartbeat monitoring and check regions
- Maintenance windows
- API keys per organization

---

## 🤖 Multi-Agent Strategy

This project is designed for **parallel agent execution**. Spawn subagents as follows:

### Agent Roles

```
ORCHESTRATOR (you)
├── Agent Alpha   → TASK-251 (AlertService + Email)
├── Agent Beta    → TASK-260 (Acknowledgement System)
│   └── [waits for Alpha to finish AlertService skeleton]
├── Agent Gamma   → TASK-301 (IXC Integration)
├── Agent Delta   → TASK-302 (Zabbix Integration)
├── Agent Epsilon → TASK-303 + TASK-310 (REST API + UI)
└── Agent Zeta    → TASK-400 + TASK-421 (Dashboard + FTP Backup)
```

### Parallel Safety Rules for Agents

- Agents working on **integrations** (Gamma, Delta, Epsilon) are fully independent — they touch different files
- Agent Alpha (AlertService) must commit before Agent Beta starts `AlertService` modifications
- Agent Zeta (Dashboard) is fully independent — only touches `DashboardController` + `charts.js`
- All agents: **run tests before committing** — never commit broken tests
- All agents: **commit per completed task** with message format `feat: complete TASK-XXX - description`

---

## 📋 Detailed Task Specifications

### TASK-251: Alert Service + Email Channel

**Files to create:**
```
src/src/Service/Alert/ChannelInterface.php
src/src/Service/Alert/AlertService.php
src/src/Service/Alert/EmailAlertChannel.php
src/templates/email/html/incident_down.php
src/templates/email/html/incident_up.php
src/tests/TestCase/Service/Alert/AlertServiceTest.php
```

**AlertService responsibilities:**
- `dispatch(Monitor $monitor, Incident $incident)` — find applicable AlertRules, trigger channels
- `shouldTrigger(AlertRule $rule, Monitor $monitor)` — check trigger type (down/up/degraded/any)
- `checkThrottle(AlertRule $rule)` — respect `cooldown_minutes` field, check AlertLogs for last send
- `logAlert(AlertRule $rule, Incident $incident, string $status, ?string $error)` — save to alert_logs

**EmailAlertChannel responsibilities:**
- Implements `ChannelInterface`
- Uses CakePHP's `Mailer` system with SMTP settings from `SettingService`
- `send(AlertRule $rule, Monitor $monitor, Incident $incident)` — sends email to each recipient in rule's JSON recipients list
- Recipients format: `["email@example.com", "other@example.com"]`
- Saves result to alert_logs (channel='email', status='sent'|'failed')

**Integration point:** Modify `MonitorCheckCommand` to call `AlertService::dispatch()` after saving check results and updating incident.

**Email templates:** Simple HTML emails in Portuguese. Down email: red header, monitor name, status, time. Up email: green header, resolved message.

**Tooltips already available (use them in any views):**
- `tooltip.alert_rule_name`, `tooltip.alert_rule_trigger`, `tooltip.alert_rule_channels`,
  `tooltip.alert_rule_recipients`, `tooltip.alert_rule_cooldown`, `tooltip.alert_rule_active`

---

### TASK-260: Incident Acknowledgement System

**Migration to create:**
```sql
-- Add to incidents table:
acknowledged_by_user_id INTEGER NULL (FK -> users)
acknowledged_at DATETIME NULL
acknowledged_via VARCHAR(20) NULL  -- 'email', 'web', 'telegram', 'sms'
acknowledgement_token VARCHAR(64) NULL  -- secure token for email links
```

**Files to create:**
```
src/config/Migrations/YYYYMMDDHHMMSS_AddAcknowledgementToIncidents.php
src/templates/email/html/incident_acknowledged.php
src/templates/element/incidents/acknowledge_badge.php
```

**Files to modify:**
```
src/src/Model/Entity/Incident.php       — add isAcknowledged(), acknowledgeBy()
src/src/Model/Table/IncidentsTable.php  — add association belongsTo Users
src/src/Controller/IncidentsController.php — add acknowledge($id, $token), acknowledgeAdmin($id)
src/src/Service/AlertService.php        — stop sending if incident already acknowledged
src/templates/Incidents/index.php       — show acknowledged badge
src/templates/Incidents/view.php        — show acknowledgement in timeline + button
src/templates/email/html/incident_down.php — add "Acknowledge" button with token link
```

**Business logic:**
- Only first acknowledgement is accepted (reject if `acknowledged_at` already set)
- Token expires 24h after incident creation
- After acknowledgement: notify other recipients via AlertService that "User X acknowledged at HH:MM"
- Public URL: `GET /incidents/acknowledge/{id}/{token}` — no auth required (token is the auth)
- Admin URL: `POST /incidents/{id}/acknowledge-admin` — requires authentication

---

### TASK-301: IXC Integration

**Reference:** Read `docs/API_INTEGRATIONS.md` fully before starting.

**Files to create:**
```
src/src/Integration/Ixc/IxcClient.php           — HTTP client wrapping CakePHP Http\Client
src/src/Integration/Ixc/IxcAdapter.php           — implements IntegrationInterface
src/src/Integration/Ixc/IxcMapper.php            — transforms IXC API data to internal format
src/src/Service/Check/IxcServiceChecker.php      — checks IXC service status
src/src/Service/Check/IxcEquipmentChecker.php    — checks IXC equipment/ONU status
src/tests/TestCase/Integration/Ixc/IxcAdapterTest.php
```

**IxcClient:** Token-based auth via `?token=BASE64(user:password_md5)`. Base URL from integration config. Methods: `get($endpoint, $params)`, `post($endpoint, $data)`.

**IxcAdapter:** Implements `connect()`, `testConnection()`, `getStatus($resourceId)`, `getMetrics($resourceId, $params)`.

**IxcServiceChecker:** Extends AbstractChecker. Monitor type = `ixc_service`. Checks if IXC service is responding and returns expected status.

**IxcEquipmentChecker:** Extends AbstractChecker. Monitor type = `ixc_equipment`. Checks ONU/equipment by serial number or ID.

**Tests:** Use mocks for HTTP calls. Test successful response, failed response, timeout, auth failure.

---

### TASK-302: Zabbix Integration

**Reference:** Read `docs/API_INTEGRATIONS.md` section on Zabbix before starting.

**Files to create:**
```
src/src/Integration/Zabbix/ZabbixClient.php        — JSON-RPC 2.0 client
src/src/Integration/Zabbix/ZabbixAdapter.php        — implements IntegrationInterface
src/src/Integration/Zabbix/ZabbixMapper.php         — maps Zabbix data to internal format
src/src/Service/Check/ZabbixHostChecker.php         — checks Zabbix host availability
src/src/Service/Check/ZabbixTriggerChecker.php      — checks Zabbix trigger state
src/tests/TestCase/Integration/Zabbix/ZabbixAdapterTest.php
```

**ZabbixClient:** JSON-RPC 2.0 over HTTP. Auth via `user.login`, store token in session. Methods: `call($method, $params)`, `login()`, `logout()`.

**ZabbixHostChecker:** Monitor type = `zabbix_host`. Calls `host.get` API, checks `available` field (1=available).

**ZabbixTriggerChecker:** Monitor type = `zabbix_trigger`. Calls `trigger.get`, checks `value` field (0=OK, 1=PROBLEM).

---

### TASK-303: REST API Generic Adapter

**Files to create:**
```
src/src/Integration/RestApi/RestApiAdapter.php
src/src/Integration/RestApi/RestApiClient.php
src/src/Service/Check/RestApiChecker.php
src/tests/TestCase/Integration/RestApi/RestApiAdapterTest.php
```

**RestApiChecker:** Monitor type = `api`. Configurable validators:
- `status_code`: expected HTTP status (default: 200)
- `json_path`: dot-notation path to check in JSON response (e.g. `status.health`)
- `expected_value`: value to match at json_path
- `content_contains`: substring to find in response body

---

### TASK-310: Integrations Controller

**Files to create:**
```
src/src/Controller/IntegrationsController.php
src/templates/Integrations/index.php
src/templates/Integrations/add.php
src/templates/Integrations/edit.php
src/templates/Integrations/view.php
```

**Actions:** `index` (list all with type badges + status), `add` (create new), `edit`, `delete`, `test` (AJAX — calls adapter's `testConnection()`, returns JSON).

**Use tooltips:** All available in `tooltip.integration_*` (see TASKS.md TASK-310 section).

**Add to sidebar** in `templates/element/admin/sidebar.php` under "Integrações" menu item.

---

### TASK-400: Admin Dashboard

**Files to create:**
```
src/src/Controller/DashboardController.php   (replaces/extends AdminController index)
src/templates/Dashboard/index.php
src/webroot/js/charts.js                     (Chart.js based)
```

**Dashboard widgets:**
1. **Summary cards**: Total monitors, Up (green), Down (red), Degraded (yellow), Unknown (grey)
2. **Active incidents**: Count with severity badges
3. **Uptime chart**: Line chart — last 24h uptime % per monitor (Chart.js)
4. **Response time chart**: Bar chart — average response time per monitor
5. **Recent checks table**: Last 20 checks across all monitors (monitor name, status, time)
6. **Recent alerts table**: Last 10 alert_logs entries

**Chart.js**: Load from CDN `https://cdn.jsdelivr.net/npm/chart.js`. Put initialization in `webroot/js/charts.js`. Pass data from controller as JSON in `<script>` tags.

---

### TASK-421: Backup FTP/SFTP Upload

**Files to create:**
```
src/config/Migrations/YYYYMMDDHHMMSS_AddBackupFtpSettings.php
src/src/Service/BackupUploaderService.php
```

**Files to modify:**
```
src/src/Command/BackupCommand.php            — call BackupUploaderService after backup
src/src/Controller/SettingsController.php    — add testFtpConnection() action
src/templates/Settings/index.php             — add "Backup" tab
```

**Settings to add via migration (using SettingsSeed pattern):**
```
backup_ftp_enabled    (boolean, default: false)
backup_ftp_type       (string, default: 'ftp')   -- 'ftp' or 'sftp'
backup_ftp_host       (string, default: '')
backup_ftp_port       (integer, default: 21)
backup_ftp_username   (string, default: '')
backup_ftp_password   (string, default: '')
backup_ftp_path       (string, default: '/backups')
backup_ftp_passive    (boolean, default: true)
```

**BackupUploaderService:**
- FTP: use native PHP `ftp_connect`, `ftp_login`, `ftp_put`
- SFTP: use `phpseclib/phpseclib` (add to composer.json)
- Methods: `upload(string $localPath)`, `testConnection()`, `disconnect()`
- Log all activity to `logs/backup.log`

---

## 🏁 Definition of Done (DoD)

The project is **COMPLETE** when ALL of the following are true:

### Functional
- [ ] `AlertService` dispatches email alerts when monitors go DOWN or UP
- [ ] Throttling works — no duplicate alerts within cooldown window
- [ ] Incident acknowledgement works via email link and admin panel
- [ ] IXC integration: `testConnection()` works with real IXC API structure
- [ ] Zabbix integration: `testConnection()` works with real Zabbix API structure
- [ ] REST API checker works with configurable json_path validation
- [ ] IntegrationsController has full CRUD + working "Test Connection" button
- [ ] Dashboard shows real data from DB with Chart.js charts
- [ ] Backup command uploads to FTP/SFTP when enabled in settings
- [ ] FTP "Test Connection" button works in Settings UI

### Quality
- [ ] All existing 78 tests still pass (no regressions)
- [ ] New tests written for every new Service, Model, and Command
- [ ] Test coverage for new features ≥ 75%
- [ ] `make cs-check` returns 0 errors (PHP CS Fixer)
- [ ] No PHP errors or warnings in `docker-compose logs app`

### Infrastructure
- [ ] `make quick-start` boots everything from scratch with no manual steps
- [ ] All new DB fields added via proper migrations (not manual SQL)
- [ ] New settings added via Settings seeds or migrations
- [ ] All new routes accessible from the admin sidebar

---

## Docker Architecture (Phase 4+)

The application runs as multiple containers in production (`docker-compose.prod.yml`):

| Container | Role | Key env |
|-----------|------|---------|
| **app** | Apache + PHP web server, serves HTTP requests. Runs cron as safety fallback. | `ENABLE_CRON=true` |
| **postgres** | PostgreSQL 16 database | |
| **redis** | Redis 7 -- cache, sessions, and job queue broker | |
| **scheduler** | Long-running `bin/cake scheduler` daemon. Evaluates monitors on their individual intervals and pushes `MonitorCheckJob` / `EscalationCheckJob` to the Redis queue. Uses Redis locks to prevent duplicate scheduling. | `ENABLE_CRON=false` |
| **worker** | `bin/cake queue worker --config default` -- processes monitor check and escalation jobs from the default queue. | `ENABLE_CRON=false` |
| **worker-notifications** | `bin/cake queue worker --config notifications` -- processes alert, webhook delivery, and notification jobs. | `ENABLE_CRON=false` |

### How monitoring works (queue-based flow)

1. **Scheduler daemon** (`bin/cake scheduler`) runs continuously, sleeping between ticks.
2. Each tick evaluates which monitors are due for a check based on their `check_interval`.
3. For each due monitor, the scheduler pushes a `MonitorCheckJob` to the Redis `default` queue.
4. **worker** container picks up `MonitorCheckJob`, runs the checker, saves results, and triggers alerts if status changed.
5. Alert dispatch pushes `WebhookDeliveryJob` / notification jobs to the `notifications` queue.
6. **worker-notifications** container processes those asynchronously.

### Cron (safety fallback only)

The **app** container still runs cron with `ENABLE_CRON=true` as a belt-and-suspenders fallback:
- `* * * * *` -- `bin/cake scheduler --once` (single-tick fallback if scheduler daemon dies)
- `* * * * *` -- `bin/cake escalation_check`
- `0 * * * *` -- `bin/cake send_scheduled_reports`
- `0 0 1 * *` -- `bin/cake grant_monthly_credits`
- `0 3 * * *` -- `bin/cake cleanup`
- `0 2 * * *` -- `bin/cake backup`

Old cron entries for `monitor_check` (every minute) and `webhook_retry` (every 2 minutes) have been removed -- these are now handled by the scheduler + queue workers.

### WebhookRetryCommand (deprecated)

`bin/cake webhook_retry` is kept for backward compatibility but is deprecated. In queue mode it simply pushes `WebhookDeliveryJob` instances. The queue workers now handle webhook retries automatically via job-level retry logic.

---

## 🧪 How to Run Tests

```bash
# Run all tests
make test

# Run specific test file
make test-specific FILE=AlertServiceTest

# Run tests with coverage
make test-coverage

# Run PHP code style check
make cs-check

# Auto-fix code style
make cs-fix
```

**After each task: run `make test` and fix any failures before moving on.**

---

## 🔧 Key Patterns to Follow

### 1. Using SettingService (for reading config)
```php
$settingService = new SettingService();
$smtpHost = $settingService->getString('smtp_host', 'localhost');
$smtpPort = $settingService->getInt('smtp_port', 587);
```

### 2. Logging pattern
```php
$this->log("Message here", 'info');    // info, debug, warning, error
$this->log("Error: {$e->getMessage()}", 'error');
```

### 3. Using AbstractChecker (for new checkers)
```php
class IxcServiceChecker extends AbstractChecker {
    public function check(Monitor $monitor): array {
        // Use $this->buildSuccessResult(), $this->buildErrorResult(), $this->buildDegradedResult()
    }
    public function getType(): string { return 'ixc_service'; }
    public function validateConfig(array $config): bool { /* ... */ }
    public function getName(): string { return 'IXC Service Checker'; }
}
```

### 4. Registering new checkers in MonitorCheckCommand
```php
$this->checkService->registerChecker('ixc_service', new IxcServiceChecker());
```

### 5. Tooltip system (use in ALL new form fields)
```php
<?= $this->element('tooltip', ['text' => __d('monitors', 'tooltip.field_name')]) ?>
```

### 6. Design system colors (from docs/DESIGN.md)
```css
--color-primary: #1E88E5;   /* Blue — buttons, links, headers */
--color-success: #43A047;   /* Green — online, positive */
--color-danger:  #E53935;   /* Red — offline, errors */
--color-warning: #FDD835;   /* Yellow — degraded, warnings */
```

### 7. Git commit convention
```bash
git commit -m "feat: complete TASK-251 - AlertService + EmailAlertChannel"
git commit -m "feat: complete TASK-260 - incident acknowledgement system"
git commit -m "fix: correct throttle logic in AlertService"
```

---

## ⚠️ Important Constraints

- **DO NOT** change existing migrations — create new ones for any schema changes
- **DO NOT** modify existing tests — only fix if your changes broke them
- **DO NOT** use external paid APIs or services in the core application code
- **DO NOT** remove the tooltip system — add tooltips to all new form fields
- **DO NOT** hardcode SMTP settings — always read from `SettingService`
- **DO NOT** commit `.env` files or `database.db`
- **ALWAYS** handle exceptions — never let checkers throw unhandled exceptions
- **ALWAYS** log errors — use `$this->log()` in services and commands
- **ALWAYS** follow the existing code patterns — look at similar files first

---

## 🚀 Getting Started Checklist

Before starting any task, an agent should:

1. `cd /path/to/isp_status_page && make quick-start` — boot Docker environment
2. `make test` — confirm 78 tests pass (baseline)
3. Read `docs/DATABASE.md` for the full schema
4. Read `docs/API_INTEGRATIONS.md` if working on integrations
5. Read `docs/DESIGN.md` for UI work
6. Look at a similar existing file before writing new code
7. Write the test file FIRST, then implement
8. Run `make test` after each significant change
9. `make cs-fix` before committing

---

## 📬 When to Notify the Human (via Telegram)

Send a Telegram message when:
- ✅ A full TASK (e.g., TASK-251) is complete and tests pass
- ❌ You hit a blocker that requires a decision (e.g., missing API credentials for IXC/Zabbix)
- ⚠️ Tests are failing and you cannot determine why after 3 attempts
- 🏁 ALL tasks are complete and DoD is fully satisfied

**Message format:**
```
✅ TASK-251 Complete
- AlertService dispatches email alerts
- Throttling working (cooldown respected)
- 8 new tests passing (24 assertions)
- Next: starting TASK-260

OR

⚠️ TASK-301 Blocker
IXC integration needs real API credentials to test testConnection().
Please provide: IXC_URL, IXC_USER, IXC_TOKEN
I can mock the tests but cannot do an end-to-end test without credentials.
```
