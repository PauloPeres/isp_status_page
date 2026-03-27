# SaaS Transformation Plan — ISP Status Page → Multi-Tenant Monitoring Platform

> Last updated: 2026-03-27

## Overview

Transforming the existing ISP Status Page (CakePHP 5.x) into a full SaaS UptimeRobot/BetterUptime alternative with multi-tenancy, Stripe billing, REST API, and feature parity.

---

## Phase 0: Infrastructure Foundation

### TASK-500: Add PostgreSQL + Redis to Docker Compose
- **Status:** COMPLETED
- **Description:** Replace SQLite with PostgreSQL 16. Add Redis 7 for sessions/cache/queue. Add `cakephp/queue` for background jobs, `stripe/stripe-php` for billing, `firebase/php-jwt` for API auth.
- **Files to modify:** `docker-compose.yml`, `Dockerfile`, `src/composer.json`, `src/config/app.php`, `src/config/app_local.php`
- **Result:** Added PostgreSQL 16 and Redis 7 services to docker-compose.yml with healthchecks, volume persistence, and proper dependency ordering. Updated Dockerfile to install pdo_pgsql, pgsql, and phpredis extensions alongside postgresql-client for the entrypoint wait loop. Added cakephp/queue, stripe/stripe-php, and firebase/php-jwt to composer.json. Rewrote app_local.php to support PostgreSQL via DATABASE_URL parsing (postgres:// and postgresql:// schemes), Redis-backed cache (using RedisEngine on databases 0-2), and Redis-backed sessions (via php session.save_handler on database 3), with SQLite/file fallbacks for local dev without Docker. Replaced SQLite-specific entrypoint logic with pg_isready wait loop and psql-based table count for seed detection. Updated Makefile db-reset to use psql DROP/CREATE, backup/restore to use pg_dump/psql, and added db-shell, redis-cli, and redis-flush targets.

### TASK-501: Review and Adapt Existing Migrations for PostgreSQL
- **Status:** COMPLETED
- **Description:** Verify all 16 existing Phinx migrations work with PostgreSQL. Fix any SQLite-specific syntax. Change TEXT+JSON fields to JSONB where appropriate.
- **Files to modify:** All files in `src/config/Migrations/`
- **Depends on:** TASK-500
- **Result:** Reviewed all 18 migration files for PostgreSQL compatibility. Fixed 2 files with issues: (1) `20260327160000_AddBackupFtpSettings.php` -- replaced backtick-quoted identifier `` `key` `` with ANSI SQL double-quoted `"key"` in raw SQL queries (backticks are MySQL/SQLite-specific; `key` is a PostgreSQL reserved word requiring proper quoting). (2) `20260328000003_AddOrganizationIdToAllTables.php` -- replaced raw `ALTER TABLE ... ALTER COLUMN ... SET NOT NULL` (PostgreSQL-only, fails on SQLite) with Phinx `changeColumn()` API; replaced raw `INSERT ... NOW() ... ON CONFLICT DO NOTHING` with PHP `date()` + Phinx `insert()/saveData()` since `NOW()` is PostgreSQL-specific and SQLite uses `datetime('now')`. The remaining 16 files were clean -- all use Phinx API with correct types (boolean with true/false defaults, datetime columns, proper VARCHAR limits, no raw SQL). TEXT columns storing JSON (configuration, details, recipients, settings, template) were kept as TEXT to preserve SQLite test compatibility; JSONB migration deferred to a future task. Warnings: (a) `key` column in settings table is a PG reserved word -- Phinx handles it but future raw SQL must use `"key"`; (b) `'after'` option in addColumn is silently ignored by PostgreSQL; (c) `'default' => 'CURRENT_TIMESTAMP'` on datetime columns works on both engines but CakePHP Timestamp behavior is preferred for created/modified.

---

## Phase 1: Multi-Tenancy Foundation

### TASK-600: Create `organizations` Table
- **Status:** COMPLETED
- **Description:** Create organizations table with: id, name, slug (unique subdomain), plan, stripe_customer_id, stripe_subscription_id, trial_ends_at, timezone, language, custom_domain, logo_url, settings (JSONB), active, created, modified.
- **Files to create:** Migration, OrganizationsTable, Organization entity, OrganizationsFixture
- **Result:** Created migration `20260328000001_CreateOrganizations.php` with all columns and indexes (unique on slug, indexes on stripe_customer_id and custom_domain). Created `OrganizationsTable.php` with hasMany associations to OrganizationUsers, Monitors, Incidents, Integrations, AlertRules, Subscribers, plus validation rules for name, slug, plan and a unique slug build rule. Created `Organization.php` entity with plan constants, virtual properties (is_free_plan, is_pro_plan, is_business_plan, is_trial_active), helper methods, and JSON settings mutator. Created `OrganizationsFixture.php` with two test organizations (one free, one pro with Stripe IDs and trial).

### TASK-601: Create `organization_users` Join Table
- **Status:** COMPLETED
- **Description:** Create join table linking users to organizations with roles (owner, admin, member, viewer). Columns: organization_id, user_id, role, invited_by, invited_at, accepted_at. Unique constraint on (organization_id, user_id).
- **Files to create:** Migration, OrganizationUsersTable, OrganizationUser entity, fixture
- **Result:** Created migration `20260328000002_CreateOrganizationUsers.php` with all columns, unique constraint on (organization_id, user_id), foreign keys to organizations and users with CASCADE delete, and indexes on organization_id, user_id, and role. Created `OrganizationUsersTable.php` with belongsTo Organizations and Users associations, role validation (enum: owner, admin, member, viewer), and build rules for existsIn and isUnique. Created `OrganizationUser.php` entity with role constants and helper methods: isOwner(), isAdmin(), isMember(), isViewer(), hasAdminAccess(), hasWriteAccess(). Created `OrganizationUsersFixture.php` with 3 test records covering owner, admin, and member roles.

### TASK-602: Add `organization_id` FK to All Existing Tables
- **Status:** COMPLETED
- **Description:** Add organization_id to: monitors, incidents, monitor_checks, alert_rules, alert_logs, subscribers, subscriptions, integrations, integration_logs. Migration creates default org, updates existing rows, then sets NOT NULL. Add indexes.
- **Files to create:** Migration
- **Files to modify:** All Table classes to add belongsTo Organizations association
- **Depends on:** TASK-600, TASK-601
- **Result:** Created migration `20260328000003_AddOrganizationIdToAllTables.php` that: (1) inserts a "Default Organization" record (id=1, slug='default', plan='free'), (2) adds `organization_id` as nullable integer to all 9 tenant-scoped tables (monitors, incidents, monitor_checks, alert_rules, alert_logs, subscribers, subscriptions, integrations, integration_logs), (3) backfills existing rows with organization_id=1, (4) changes each column to NOT NULL, (5) adds FK constraints to organizations(id) with CASCADE delete, (6) adds indexes on organization_id. Updated all 9 Table classes (MonitorsTable, IncidentsTable, MonitorChecksTable, AlertRulesTable, AlertLogsTable, SubscribersTable, SubscriptionsTable, IntegrationsTable, IntegrationLogsTable) to add `belongsTo('Organizations')` association and `existsIn` build rule. Updated all 9 Entity classes to add `'organization_id' => true` to `$_accessible`. Added `buildRules()` method to MonitorsTable and IntegrationsTable which previously lacked one.

### TASK-603: Tenant-Scoped Base Table Behavior
- **Status:** COMPLETED
- **Description:** Create TenantScopeBehavior that auto-adds `WHERE organization_id = X` to all finds and auto-sets organization_id on new entities. Create TenantContext static holder for current org ID.
- **Files to create:** `src/src/Model/Behavior/TenantScopeBehavior.php`, `src/src/Tenant/TenantContext.php`, tests
- **Depends on:** TASK-602
- **Result:** Created `TenantContext` static holder class with setCurrentOrgId/getCurrentOrgId, setCurrentOrganization/getCurrentOrganization, reset(), and isSet() methods. Created `TenantScopeBehavior` CakePHP behavior with three callbacks: beforeFind (adds WHERE organization_id condition, skippable via skipTenantScope option or when context is not set for CLI/testing), beforeSave (auto-sets organization_id on new entities, blocks cross-tenant updates), and beforeDelete (blocks cross-tenant deletes). Created comprehensive test suites: TenantContextTest (9 tests) and TenantScopeBehaviorTest (10 tests). All 19 tests pass with 24 assertions. Also fixed pre-existing migration bug in TASK-602's migration where the property name `$tables` conflicted with the parent Phinx class.

### TASK-604: Tenant Resolution Middleware
- **Status:** COMPLETED
- **Description:** Middleware to determine current org from: API key header, subdomain, session, or path prefix. Sets TenantContext for downstream use.
- **Files to create:** `src/src/Middleware/TenantMiddleware.php`, tests
- **Files to modify:** `src/src/Application.php` (register middleware)
- **Depends on:** TASK-602
- **Result:** Created `TenantMiddleware.php` implementing five-step resolution chain: (1) API header X-Organization-Id, (2) subdomain slug lookup, (3) session current_organization_id, (4) /org/{slug}/... path prefix, (5) default single-org user. Middleware resets TenantContext per request, skips public routes (login, register, status, heartbeat, webhooks, api/docs, acknowledge), sets TenantContext and request attribute on success, returns 403 JSON for unresolved API requests, and redirects authenticated web users to /organizations/select when no org can be determined. Registered in Application.php after AuthenticationMiddleware. Created comprehensive test suite (12 test methods) covering all resolution strategies, public route bypass, inactive org rejection, multi-org redirect, and context reset.

### TASK-605: Update All Existing Controllers for Tenant Context
- **Status:** COMPLETED
- **Description:** Add currentOrganization to AppController. Update all controllers and services to use tenant context. Update MonitorCheckCommand to iterate per-org.
- **Files to modify:** AppController, all existing controllers, IncidentService, SettingService, CheckService, MonitorCheckCommand
- **Depends on:** TASK-603, TASK-604
- **Result:** Updated AppController with `$currentOrganization` property and TenantContext integration in `initialize()` -- the current org is loaded from TenantContext (set by TenantMiddleware) and passed to all views via `$currentOrganization`. Added `TenantScope` behavior to all 9 tenant-scoped Table classes (MonitorsTable, IncidentsTable, MonitorChecksTable, AlertRulesTable, AlertLogsTable, SubscribersTable, SubscriptionsTable, IntegrationsTable, IntegrationLogsTable), placed after the Timestamp behavior in each `initialize()` method. This ensures all queries are automatically filtered by `organization_id` when TenantContext is set. Verified MonitorCheckCommand works in "system mode" -- it does not set TenantContext, so TenantScopeBehavior skips filtering (CLI mode), allowing the command to check all monitors across all organizations. Added `getOrgSetting()` method to SettingService that checks organization-level settings (from the `settings` JSON column on organizations) before falling back to global system settings. Verified StatusController works correctly -- since it inherits from AppController and TenantScopeBehavior handles query filtering automatically, the public status page shows only monitors for the resolved organization when TenantContext is set by TenantMiddleware.

### TASK-606: Update All Test Fixtures
- **Status:** PENDING
- **Description:** Add organization_id to all fixtures. Create default organization fixture.
- **Files to modify:** All fixture files
- **Depends on:** TASK-602
- **Result:** _pending_

---

## Phase 2: Auth & Onboarding

### TASK-700: Public Registration Flow
- **Status:** PENDING
- **Description:** Registration form (name, email, password). Creates User + Organization + OrganizationUser (role=owner). Email verification with token. Migration adds email_verified, email_verification_token columns to users.
- **Files to create:** RegistrationController, templates, email template, migration, tests
- **Depends on:** Phase 1
- **Result:** _pending_

### TASK-701: Organization Creation & Onboarding Wizard
- **Status:** PENDING
- **Description:** 3-step wizard: 1) Org name/slug, 2) Create first monitor, 3) Invite team. OnboardingController + OnboardingService.
- **Files to create:** OnboardingController, 4 templates, OnboardingService, tests
- **Depends on:** TASK-700
- **Result:** _pending_

### TASK-702: Team Invitation System
- **Status:** PENDING
- **Description:** Invitations table (org_id, email, role, token, expires_at). Send invite emails, accept/revoke invitations. Creates OrganizationUser on acceptance.
- **Files to create:** Migration, InvitationsTable, InvitationsController, InvitationService, email template, tests
- **Depends on:** TASK-700
- **Result:** _pending_

### TASK-703: RBAC (Role-Based Access Control)
- **Status:** PENDING
- **Description:** Permission policies for org, monitors, invitations. Roles: owner (full), admin (team+resources), member (resources), viewer (read-only). Uses cakephp/authorization.
- **Files to create:** Policy classes, AuthorizationService, middleware, tests
- **Depends on:** Phase 1
- **Result:** _pending_

### TASK-704: OAuth/Social Login (Google, GitHub)
- **Status:** PENDING
- **Description:** OAuth controller with redirect/callback flow. Migration adds oauth_provider, oauth_id to users.
- **Files to create:** OAuthController, OAuthService, migration
- **Depends on:** TASK-700
- **Result:** _pending_

### TASK-705: Organization Switcher
- **Status:** PENDING
- **Description:** UI dropdown in admin header for switching between orgs. Session management for current org.
- **Files to create:** OrganizationSwitcherController, org_switcher element
- **Files to modify:** admin layout, AppController
- **Depends on:** TASK-700
- **Result:** _pending_

---

## Phase 3: Stripe Billing

### TASK-800: Plans & Pricing Configuration
- **Status:** PENDING
- **Description:** Plans table: Free (1 monitor, 5min, email only), Pro $15/mo (50 monitors, 1min, Slack+webhook, API), Business $45/mo (unlimited, 30s, all channels, multi-region). PlanService for limit enforcement.
- **Files to create:** Migration, PlansTable, Plan entity, PlansSeed, PlanService, fixture
- **Result:** _pending_

### TASK-801: Stripe Integration Service
- **Status:** PENDING
- **Description:** StripeService wrapping Stripe SDK. SubscriptionService for managing subscriptions. UsageService for tracking limits.
- **Files to create:** StripeService, SubscriptionService, UsageService, tests
- **Depends on:** TASK-800
- **Result:** _pending_

### TASK-802: Stripe Checkout & Customer Portal
- **Status:** PENDING
- **Description:** BillingController with plans(), checkout(), portal(), success(), cancel(). Pricing page UI.
- **Files to create:** BillingController, templates, tests
- **Depends on:** TASK-801
- **Result:** _pending_

### TASK-803: Stripe Webhook Handler
- **Status:** PENDING
- **Description:** Handle: checkout.session.completed, subscription.updated/deleted, invoice.payment_succeeded/failed. CSRF-exempt webhook route.
- **Files to create:** WebhookController, StripeWebhookHandler
- **Files to modify:** routes.php
- **Depends on:** TASK-801
- **Result:** _pending_

### TASK-804: Usage Metering & Limit Enforcement
- **Status:** PENDING
- **Description:** PlanLimitMiddleware checks monitor count before create. LimitEnforcer service.
- **Files to create:** PlanLimitMiddleware, LimitEnforcer
- **Files to modify:** MonitorsController, Application.php
- **Depends on:** TASK-801
- **Result:** _pending_

---

## Phase 4: Public REST API

### TASK-900: API Key Management
- **Status:** PENDING
- **Description:** api_keys table with key_hash, key_prefix, permissions, rate_limit. Admin UI for CRUD. ApiKeyService for generation/validation.
- **Files to create:** Migration, ApiKeysTable, ApiKey entity, ApiKeysController, templates, ApiKeyService, fixture
- **Result:** _pending_

### TASK-901: API Authentication Middleware
- **Status:** PENDING
- **Description:** ApiAuthMiddleware authenticates via Bearer token. ApiRateLimitMiddleware uses Redis for rate limiting per plan.
- **Files to create:** ApiAuthMiddleware, ApiRateLimitMiddleware, tests
- **Depends on:** TASK-900
- **Result:** _pending_

### TASK-902: REST API Controllers
- **Status:** PENDING
- **Description:** JSON API controllers for /api/v1/: monitors (CRUD + pause/resume), incidents (CRUD), checks (read), status-pages (CRUD), alert-rules (CRUD), webhooks (CRUD). Base Api/V1/AppController.
- **Files to create:** 7 API controllers, tests
- **Files to modify:** routes.php
- **Depends on:** TASK-901
- **Result:** _pending_

### TASK-903: OpenAPI/Swagger Documentation
- **Status:** PENDING
- **Description:** OpenAPI 3.0 spec (YAML). Swagger UI served at /api/docs.
- **Files to create:** openapi.yaml, DocsController, template
- **Depends on:** TASK-902
- **Result:** _pending_

### TASK-904: Webhook Delivery System
- **Status:** PENDING
- **Description:** webhook_endpoints + webhook_deliveries tables. WebhookDeliveryService with HMAC-SHA256 signing and retry logic. Background job.
- **Files to create:** Migrations, models, WebhookDeliveryService, DeliverWebhookJob
- **Depends on:** TASK-901
- **Result:** _pending_

---

## Phase 5: Feature Parity with UptimeRobot

### TASK-1000: Heartbeat/Cron Monitoring (Push-Based)
- **Status:** PENDING
- **Description:** Heartbeats table with token and expected_interval. Public ping endpoint GET /heartbeat/{token}. HeartbeatChecker detects stale pings. HeartbeatCheckCommand.
- **Files to create:** Migration, HeartbeatsTable, HeartbeatController, HeartbeatChecker, HeartbeatCheckCommand, form element
- **Result:** _pending_

### TASK-1001: Keyword Monitoring
- **Status:** PENDING
- **Description:** KeywordChecker extends HttpChecker with content matching (contains/not-contains text). New monitor type 'keyword'.
- **Files to create:** KeywordChecker
- **Files to modify:** MonitorsTable (add type), HttpChecker (refactor for extension)
- **Result:** _pending_

### TASK-1002: SSL Certificate Monitoring
- **Status:** PENDING
- **Description:** SslCertChecker checks certificate expiry and chain validity. Alerts at configurable thresholds (30/14/7/1 days).
- **Files to create:** SslCertChecker, SslCertCheckJob, ssl_form element
- **Result:** _pending_

### TASK-1003: Alert Channels — Slack, Discord, Telegram, Webhook
- **Status:** PENDING
- **Description:** Four new alert channel implementations. Register in AlertService.
- **Files to create:** SlackAlertChannel, DiscordAlertChannel, TelegramAlertChannel, WebhookAlertChannel, tests
- **Files to modify:** AlertService, AlertRulesTable
- **Result:** _pending_

### TASK-1004: Custom Status Pages (Per-Org)
- **Status:** PENDING
- **Description:** status_pages table with slug, custom_domain, theme (JSONB), monitors list, password protection. Admin CRUD + public rendering by slug/domain.
- **Files to create:** Migration, StatusPagesTable, StatusPagesController, PublicStatusController, templates
- **Files to modify:** StatusController, routes.php
- **Result:** _pending_

### TASK-1005: Public Badges/Shields
- **Status:** PENDING
- **Description:** SVG badge generation for uptime %, status, response time. Endpoints: /badges/{token}/uptime.svg, status.svg, response-time.svg.
- **Files to create:** BadgesController, BadgeService
- **Result:** _pending_

### TASK-1006: Maintenance Windows
- **Status:** PENDING
- **Description:** maintenance_windows table with title, monitor_ids, starts_at, ends_at, auto_suppress_alerts. MaintenanceService suppresses alerts during windows. Admin CRUD.
- **Files to create:** Migration, MaintenanceWindowsTable, MaintenanceWindowsController, MaintenanceService, templates
- **Files to modify:** AlertService
- **Result:** _pending_

### TASK-1007: Multi-Region Checks (Architecture Only)
- **Status:** PENDING
- **Description:** check_regions table. Add region_id to monitor_checks. Architecture design only — actual distributed workers are a future milestone.
- **Files to create:** Migrations, CheckRegionsTable
- **Result:** _pending_

---

## Phase 6: i18n & Polish

### TASK-1100: Per-User Language Selection
- **Status:** PENDING
- **Description:** Add language/timezone columns to users. AppController reads from user → org → system default.
- **Files to create:** Migration
- **Files to modify:** AppController, User entity
- **Result:** _pending_

### TASK-1101: Complete i18n Coverage
- **Status:** PENDING
- **Description:** Audit all templates for hardcoded strings. Create .po files for new domains (billing, api, onboarding, status_pages, organizations) in en, pt_BR, es.
- **Files to create:** ~15 new .po files
- **Files to modify:** All templates from Phases 1-5
- **Result:** _pending_

### TASK-1102: Timezone per Organization
- **Status:** PENDING
- **Description:** Set timezone from org in AppController. Use org timezone for all date displays.
- **Files to modify:** AppController, IncidentService, date-displaying templates
- **Result:** _pending_

### TASK-1103: UI Polish
- **Status:** PENDING
- **Description:** Loading states, empty states, error states for all views. Consistent mobile experience. Responsive billing/pricing pages.
- **Files to modify:** All templates
- **Result:** _pending_

---

## Pricing Plans

| Feature | Free | Pro ($15/mo) | Business ($45/mo) |
|---------|------|-------------|-------------------|
| Monitors | 1 | 50 | Unlimited |
| Check interval | 5 min | 1 min | 30 sec |
| Alert channels | Email | Email+Slack+Webhook | All+SMS+Phone |
| Status pages | 1 (shared) | 1 custom | 5 custom |
| Team members | 1 | 5 | Unlimited |
| API access | No | Yes (1000 req/hr) | Yes (10000 req/hr) |
| Data retention | 7 days | 30 days | 90 days |
| SSL monitoring | No | Yes | Yes |

---

## Agent Parallelization Strategy

**Sprint 1:** TASK-500, TASK-501 (sequential) → TASK-600+601 (parallel) → TASK-602 → TASK-603+604 (parallel)
**Sprint 2:** TASK-605, TASK-606, TASK-700, TASK-703 (parallel after Phase 1)
**Sprint 3:** TASK-701+702, TASK-800+801, TASK-900+901 (parallel tracks)
**Sprint 4:** TASK-802+803, TASK-902+903+904, TASK-704+705 (parallel)
**Sprint 5:** All Phase 5 tasks (maximum parallelism — 7 agents)
**Sprint 6:** Phase 6 tasks (3 agents)
