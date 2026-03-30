# ISP Status Page — Product Roadmap

> Last Updated: 2026-03-30
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

---

## What's Next (Remaining Tasks)

### Phase A — Angular Polish (Priority: HIGH)

| # | Task | Complexity | Description |
|---|------|-----------|-------------|
| ~~A-01~~ | ~~Real-time updates (SSE)~~ | ~~Medium~~ | ~~Server-Sent Events for live dashboard/monitor status updates without refresh~~ **DONE** |
| A-02 | Angular onboarding wizard | Medium | Step-by-step guide after registration (org setup → first monitor → invite team) |
| A-03 | Angular dark mode toggle | Small | Wire Ionic dark mode toggle in settings/navbar |
| A-04 | Improve form validation | Small | Better error messages, field-level validation in all Angular forms |
| A-05 | Loading skeletons | Small | Replace spinners with Ionic skeleton-text on all list/detail pages |
| A-06 | Pull-to-refresh on all pages | Small | Some pages may be missing ion-refresher |
| A-07 | Search on all list pages | Small | Ensure every list has a search bar |

### Phase B — Infrastructure (Priority: MEDIUM)

| # | Task | Complexity | Description |
|---|------|-----------|-------------|
| B-01 | OpenAPI v2 specification | Medium | Document all ~110 API v2 endpoints in openapi-v2.yaml |
| B-02 | Capacitor native builds | Medium | Configure iOS + Android projects, app icons, splash screens |
| B-03 | CI/CD pipeline | Medium | GitHub Actions: lint, test, build frontend, build Docker |
| B-04 | Production deployment guide | Medium | Document: domain setup, SSL, Stripe config, SMTP, OAuth keys |
| B-05 | TimescaleDB evaluation | Large | Migrate to TimescaleDB when >1M rows/day (evaluation doc exists) |

### Phase C — Advanced Features (Priority: MEDIUM)

| # | Task | Complexity | Description |
|---|------|-----------|-------------|
| C-01 | Multi-region check workers | Large | Deploy check workers in US/EU/Asia, aggregate results |
| C-02 | PagerDuty / OpsGenie integration | Medium | First-class incident management platform integration |
| C-03 | Monitor import from competitors | Medium | Import from UptimeRobot, Pingdom, BetterUptime (CSV/API) |
| C-04 | Interactive Telegram bot | Medium | Acknowledge, check status, manage monitors via Telegram commands |
| C-05 | Notification scheduling | Medium | Advanced quiet hours: per-channel, per-severity, business hours |

### Phase D — Growth & Enterprise (Priority: LOW)

| # | Task | Complexity | Description |
|---|------|-----------|-------------|
| D-01 | SSO/SAML for enterprise | Large | Enterprise SSO integration (Azure AD, Okta, OneLogin) |
| D-02 | Custom billing plans | Medium | Admin can create custom plan tiers for enterprise customers |
| D-03 | White-label / reseller mode | Large | Remove ISP Status branding, allow resellers |
| D-04 | GraphQL API | Large | Alternative to REST for frontend (optional) |
| D-05 | Audit trail export | Small | Export audit logs as CSV/JSON for compliance |

---

## Reference Documents

These older planning docs are now historical reference:

| Document | Purpose | Status |
|----------|---------|--------|
| SAAS_PLAN.md | Original SaaS transformation (37 tasks) | All COMPLETED |
| SUPER_ADMIN_PLAN.md | Super Admin panel (16 tasks) | All COMPLETED |
| DB_OPTIMIZATION_PLAN.md | Database optimization (12 tasks) | All COMPLETED |
| AUTH_SECURITY_PLAN.md | Auth/security improvements (21 tasks) | All COMPLETED |
| ANGULAR_MIGRATION_PLAN.md | Angular migration (54 tasks) | 47/54 COMPLETED |
| NOTIFICATION_CREDITS_PLAN.md | Credit system (8 tasks) | All COMPLETED |
| SAAS_SETTINGS_RESTRUCTURE.md | Settings separation (5 tasks) | All COMPLETED |
| UI_DESIGN_AUDIT.md | Design system review | Applied |
| QA_FINAL_REPORT.md | Post-migration QA | Passed |
| DB_SPECIALIST_FINDINGS.md | Database architecture analysis | Applied |
| REMAINING_WORK.md | Detailed backlog (now superseded by this file) | Superseded |
