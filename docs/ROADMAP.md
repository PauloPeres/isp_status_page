# ISP Status Page — Product Roadmap

> Last Updated: 2026-03-30 (Phase A completed)
> Status: Production SaaS Platform (Angular + Ionic frontend, CakePHP API backend)

---

## What's Been Built (Completed)

### Core Platform
- [x] Multi-tenant architecture (organizations, row-level isolation)
- [x] PostgreSQL 16 + Redis 7 infrastructure
- [x] JWT API v2 with ~110 endpoints
- [x] Angular 19 + Ionic 8 SPA frontend (22 feature modules)
- [x] Capacitor-ready for native mobile builds
- [x] CakePHP serves landing pages, public status, API

### Monitoring
- [x] HTTP, Ping, Port, API, Heartbeat, Keyword, SSL certificate monitoring
- [x] IXC ERP integration (per-org)
- [x] Zabbix integration (per-org)
- [x] REST API generic adapter
- [x] Monitor tags/groups with filtering
- [x] Bulk operations (pause/resume/delete) + CSV import
- [x] 30-day uptime history bars
- [x] Response time charts (24h/7d/30d)

### Alerting
- [x] Email, Slack, Discord, Telegram, Custom Webhook channels
- [x] SMS + WhatsApp via Twilio (credit-based billing)
- [x] Alert escalation policies (multi-step with timing)
- [x] Notification quiet hours
- [x] Maintenance windows (alert suppression)
- [x] Incident acknowledgement via SMS/WhatsApp/Telegram

### Incidents
- [x] Auto-detection + manual creation
- [x] Timeline with team updates (Investigating → Identified → Monitoring → Resolved)
- [x] Public incident updates on status page
- [x] Acknowledgement via web, email, SMS, WhatsApp, Telegram

### Billing & Credits
- [x] Stripe integration (checkout, portal, webhooks)
- [x] 4 plans: Free, Pro ($15/mo), Business ($45/mo), Enterprise (custom)
- [x] Notification credits system (SMS/WhatsApp: 1 credit each)
- [x] Monthly credit grants (Pro: 50, Business: 200)
- [x] Plan limit enforcement (monitors, team members, intervals)

### Auth & Security
- [x] JWT authentication (access + refresh tokens)
- [x] Google + Microsoft OAuth
- [x] Two-factor authentication (TOTP + recovery codes)
- [x] Brute force protection (rate limiting)
- [x] Security headers middleware
- [x] Security audit logging
- [x] RBAC (owner/admin/member/viewer)
- [x] Team invitations

### Status Pages
- [x] Custom status pages per org (branding, colors, logo, CSS)
- [x] Public badges/shields (SVG: uptime, status, response time)
- [x] Embeddable status widget
- [x] RSS feed for incidents
- [x] Maintenance windows on public page

### Reporting
- [x] CSV export (uptime, incidents, response time)
- [x] Scheduled email reports (weekly/monthly)
- [x] SLA tracking (target vs actual uptime, breach alerts)

### Super Admin
- [x] Dashboard (MRR, orgs, monitors, health KPIs)
- [x] Organization management + impersonation
- [x] Revenue analytics
- [x] Platform health metrics
- [x] Security logs
- [x] System settings (SMTP, backup, system-wide)
- [x] Credit management

### Infrastructure
- [x] DB optimization (composite indexes, rollup aggregation, batched retention)
- [x] Redis caching (uptime, dashboard, badges)
- [x] PWA (installable, offline page, push notifications ready)
- [x] Dark mode

### Angular Polish (Phase A)
- [x] Real-time updates via Server-Sent Events (SSE)
- [x] Onboarding wizard (4-step guided setup after registration)
- [x] Dark mode toggle (light/dark/system with persistence)
- [x] Field-level form validation on all 9 form components
- [x] Loading skeletons on all list/detail/settings pages
- [x] Pull-to-refresh on all 24 data-loading pages
- [x] Search/filter bars on all 13 list pages

---

## What's Next (Remaining Tasks)

### Phase A — Angular Polish (Priority: HIGH) — ALL COMPLETED

| # | Task | Complexity | Description |
|---|------|-----------|-------------|
| ~~A-01~~ | ~~Real-time updates (SSE)~~ | ~~Medium~~ | ~~Server-Sent Events for live dashboard/monitor status updates without refresh~~ **DONE** |
| ~~A-02~~ | ~~Angular onboarding wizard~~ | ~~Medium~~ | ~~Step-by-step guide after registration (4-step wizard: create monitor → set alerts → status page → invite team). OnboardingService + OnboardingComponent, dashboard banner, auto-detects progress from API~~ **DONE** |
| ~~A-03~~ | ~~Angular dark mode toggle~~ | ~~Small~~ | ~~ThemeService with 3 modes (light/dark/system), toggle in sidebar header, persists to localStorage, SCSS mixin-based~~ **DONE** |
| ~~A-04~~ | ~~Improve form validation~~ | ~~Small~~ | ~~Shared FieldErrorComponent for reactive forms, submitted-flag validation for template-driven forms, field-level errors on all 9 form components, markAllAsTouched on submit~~ **DONE** |
| ~~A-05~~ | ~~Loading skeletons~~ | ~~Small~~ | ~~Shared ListSkeletonComponent, skeleton loading on 13 list pages + settings + dashboard + detail pages~~ **DONE** |
| ~~A-06~~ | ~~Pull-to-refresh on all pages~~ | ~~Small~~ | ~~All 24 data-loading pages have ion-refresher (added to Settings which was the only one missing)~~ **DONE** |
| ~~A-07~~ | ~~Search on all list pages~~ | ~~Small~~ | ~~Client-side search bars on all 13 list pages with field-specific filtering (name, email, status, etc.)~~ **DONE** |

### Phase B — Infrastructure (Priority: MEDIUM)

| # | Task | Complexity | Description |
|---|------|-----------|-------------|
| ~~B-01~~ | ~~OpenAPI v2 specification~~ | ~~Medium~~ | ~~3,608-line OpenAPI 3.1 spec at docs/openapi-v2.yaml — ~126 operations across 23 tags, full schemas, JWT auth~~ **DONE** |
| ~~B-02~~ | ~~Capacitor native builds~~ | ~~Medium~~ | ~~Capacitor 8 config with plugins (SplashScreen, StatusBar, PushNotifications, Keyboard), iOS+Android packages, icon/splash SVG sources, build scripts, signing docs at docs/NATIVE_BUILDS.md. Note: `cap add ios/android` requires Node 22+~~ **DONE** |
| ~~B-03~~ | ~~CI/CD pipeline~~ | ~~Medium~~ | ~~GitHub Actions at .github/workflows/ci.yml — 5 jobs: PHP lint, PHP tests (Postgres+Redis services), frontend lint/typecheck, frontend prod build (artifact upload), Docker build+push to GHCR on main~~ **DONE** |
| ~~B-04~~ | ~~Production deployment guide~~ | ~~Medium~~ | ~~Comprehensive docs/PRODUCTION_DEPLOYMENT.md — 8-step guide: clone/configure, build frontend, Docker Compose, migrations, super admin, SSL (Nginx + Traefik options), Stripe webhook, OAuth setup, env var reference, troubleshooting~~ **DONE** |
| B-05 | TimescaleDB evaluation | Large | _Deferred to Future backlog_ |

### Phase C — Advanced Features (Priority: MEDIUM)

| # | Task | Complexity | Description |
|---|------|-----------|-------------|
| ~~C-01~~ | ~~Multi-region check workers~~ | ~~Large~~ | ~~CheckRegions API (CRUD), MonitorCheckCommand --region flag tags results with region_id, per-region uptime/response breakdown on monitor detail API+UI, CheckRegion entity+service in frontend. DB schema was already in place (check_regions table, region_id FK on monitor_checks, 3 seeded regions)~~ **DONE** |
| ~~C-02~~ | ~~PagerDuty / OpsGenie integration~~ | ~~Medium~~ | ~~PagerDutyAlertChannel (Events API v2: trigger/resolve, dedup_key, severity mapping) + OpsGenieAlertChannel (Alert API v2: create/close, priority mapping, alias-based resolution). Registered in MonitorCheckCommand. Channel options added to alert-rule + escalation forms~~ **DONE** |
| ~~C-03~~ | ~~Monitor import from competitors~~ | ~~Medium~~ | ~~MonitorImportService with auto-format detection + parsers for UptimeRobot (JSON+CSV), Pingdom (CSV), BetterUptime (JSON API), generic CSV. New /monitors/import-competitor API endpoint. Angular MonitorImportComponent with platform selector, paste area, result display with error details. Import button on monitor list~~ **DONE** |
| C-03b | Webhook delivery retry queue | Medium | _(bonus)_ WebhookRetryCommand (cron every 2min, exponential backoff, dry-run), WebhookEndpointsController API (CRUD+test+delivery history), routes, cron entry **DONE** |
| ~~C-04~~ | ~~Interactive Telegram bot~~ | ~~Medium~~ | ~~TelegramBotService with 8 commands: /status (overview), /monitors (list), /incidents (active), /ack (acknowledge), /check (monitor detail), /pause, /resume, /help. TelegramWebhookController receives updates via POST /telegram/webhook/{org_id}/{token}. Token-based auth (no JWT). HTML-formatted responses with emoji~~ **DONE** |
| ~~C-05~~ | ~~Notification scheduling~~ | ~~Medium~~ | ~~NotificationSchedule model (migration, entity, table) with per-channel, per-severity, day-of-week, time window support. Two actions: suppress (block during window) and allow (only send during window). NotificationScheduleService integrated into AlertService dispatch loop. NotificationSchedulesController API (CRUD). Routes added~~ **DONE** |

### Phase D — Growth & Enterprise (Priority: LOW)

| # | Task | Complexity | Description |
|---|------|-----------|-------------|
| ~~D-02~~ | ~~Custom billing plans~~ | ~~Medium~~ | ~~SuperAdmin PlansController API (CRUD + duplicate + delete protection for in-use plans). Angular SuperAdminPlansComponent at /super-admin/plans with card-based UI, inline create/edit via alerts, duplicate button, org count per plan. Routes for all 7 endpoints~~ **DONE** |
| ~~D-05~~ | ~~Audit trail export~~ | ~~Small~~ | ~~GET /activity-log/export with CSV and JSON formats, date range filtering (from/to), event type filter. Angular export button with action sheet (CSV/JSON), fetch+blob download with JWT auth~~ **DONE** |

### Deferred Tasks (Future)

| # | Task | Complexity | Description |
|---|------|-----------|-------------|
| D-01 | SSO/SAML for enterprise | Large | Enterprise SSO integration (Azure AD, Okta, OneLogin) |
| D-03 | White-label / reseller mode | Large | Remove ISP Status branding, allow resellers |
| D-04 | GraphQL API | Large | Alternative to REST for frontend (optional) |
| B-05 | TimescaleDB evaluation | Large | Migrate to TimescaleDB when >1M rows/day |

---

## Reference Documents

These older planning docs are now historical reference:

| Document | Purpose | Status |
|----------|---------|--------|
| SAAS_PLAN.md | Original SaaS transformation (37 tasks) | All COMPLETED |
| SUPER_ADMIN_PLAN.md | Super Admin panel (16 tasks) | All COMPLETED |
| DB_OPTIMIZATION_PLAN.md | Database optimization (12 tasks) | All COMPLETED |
| AUTH_SECURITY_PLAN.md | Auth/security improvements (21 tasks) | All COMPLETED |
| ANGULAR_MIGRATION_PLAN.md | Angular migration (54 tasks) | 54/54 COMPLETED (Phase A polish complete) |
| NOTIFICATION_CREDITS_PLAN.md | Credit system (8 tasks) | All COMPLETED |
| SAAS_SETTINGS_RESTRUCTURE.md | Settings separation (5 tasks) | All COMPLETED |
| UI_DESIGN_AUDIT.md | Design system review | Applied |
| QA_FINAL_REPORT.md | Post-migration QA | Passed |
| DB_SPECIALIST_FINDINGS.md | Database architecture analysis | Applied |
| REMAINING_WORK.md | Detailed backlog (now superseded by this file) | Superseded |
