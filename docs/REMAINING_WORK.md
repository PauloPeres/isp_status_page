# Remaining Work — Complete Backlog

> Generated: 2026-03-27
> Sources: SAAS_PLAN.md, SUPER_ADMIN_PLAN.md, DB_OPTIMIZATION_PLAN.md, AUTH_SECURITY_PLAN.md, DEVELOPMENT_PLAN.md, TASKS.md, I18N_PLAN.md, PROJECT_SUMMARY.md, CLAUDE.md, USER_TESTING_MOM.md, USER_TESTING_DETAIL_QA.md, USER_TESTING_POWER_USER.md, DB_SPECIALIST_FINDINGS.md, QA_CHECKLIST.md, codebase TODOs

---

## Priority 1: Production Blockers
> Things that MUST be done before going live

### P1-001: Fix language consistency — translate all remaining Portuguese strings to English
- **Source:** USER_TESTING_MOM.md, USER_TESTING_DETAIL_QA.md (CRIT-001 through CRIT-003, HIGH-001 through HIGH-014), USER_TESTING_POWER_USER.md
- **Description:** The login page, registration page, public status page, integrations pages, super admin pages, users/add page, incidents tooltips, and error pages all contain Portuguese text in what should be an English-default application. This is the single most visible quality problem, affecting 8+ pages. Includes `<html lang="pt-BR">` on pages that should be `lang="en"`.
- **Complexity:** Medium (many files, but each change is simple string replacement)
- **Dependencies:** None

### P1-002: Fix auto-refresh timer mismatch on public status page
- **Source:** USER_TESTING_DETAIL_QA.md (CRIT-004)
- **Description:** Footer text says "Automatic updates every 30 seconds" but the JavaScript timer (RELOAD_INTERVAL) is set to 300 seconds (5 minutes). Factually incorrect information displayed to end users.
- **Complexity:** Small
- **Dependencies:** None

### P1-003: Remove hardcoded security salt from app_local.php -- DONE
- **Source:** AUTH_SECURITY_PLAN.md (TASK-AUTH-003, not completed)
- **Description:** `src/config/app_local.php` contains a hardcoded fallback security salt (`51020f949eb...`). Production deployments should require `SECURITY_SALT` env var with no fallback.
- **Complexity:** Small
- **Dependencies:** None
- **Resolution:** Replaced hardcoded salt with trigger_error for production, test-safe fallback for CLI/test.

### P1-004: Remove hardcoded DB credentials from docker-compose.yml -- DONE
- **Source:** AUTH_SECURITY_PLAN.md (TASK-AUTH-004, not completed)
- **Description:** `docker-compose.yml` contains `POSTGRES_PASSWORD: isp_secret` hardcoded. Should use env vars or Docker secrets.
- **Complexity:** Small
- **Dependencies:** None
- **Resolution:** All secrets now use ${VAR:-default} pattern. Created .env.example. Ports bound to 127.0.0.1. Redis requires password.

### P1-005: Fix migration 90 (PartitionMonitorChecks) failure -- DONE
- **Source:** QA_CHECKLIST.md (Note #3)
- **Description:** Migration 90 (PartitionMonitorChecks) fails with "relation monitor_checks_id_seq already exists". Tables were created manually as workaround. This needs a proper fix for clean deployments.
- **Complexity:** Medium
- **Dependencies:** None
- **Resolution:** Added DROP SEQUENCE IF EXISTS before rename. Used IF EXISTS on sequence operations to make migration idempotent.

### P1-006: Implement the MonitorsController test connection action -- DONE
- **Source:** Codebase TODO at `src/src/Controller/MonitorsController.php:313`
- **Description:** The `testConnection` action has a TODO comment: "Implement actual connection test based on monitor type". Currently non-functional.
- **Complexity:** Medium
- **Dependencies:** None
- **Resolution:** Implemented real connection tests: HTTP (cURL HEAD request), Ping (exec ping), Port (fsockopen). Returns JSON with success, response_time, status_code, message.

### P1-007: Implement email resend in EmailLogsController -- DONE
- **Source:** Codebase TODO at `src/src/Controller/EmailLogsController.php:155`
- **Description:** The resend action has a TODO comment: "Implement email resend when EmailService is ready". Currently non-functional.
- **Complexity:** Small
- **Dependencies:** None
- **Resolution:** Implemented using AlertService/EmailAlertChannel to re-dispatch the original alert rule, monitor, and incident data.

### P1-008: Super Admin link in regular admin sidebar
- **Source:** SUPER_ADMIN_PLAN.md (TASK-SA-015, PENDING)
- **Description:** Conditional "Super Admin" link in the regular admin sidebar when user has `is_super_admin=true`. Currently no way to navigate to super admin from the regular admin panel.
- **Complexity:** Small
- **Dependencies:** None

### P1-009: Fix seed data inconsistency — free plan org has 4 monitors
- **Source:** QA_CHECKLIST.md (Note #7), USER_TESTING_POWER_USER.md
- **Description:** The default organization is on the Free plan (limit 1 monitor) but seed data creates 4 monitors. Plan limits appear unenforced for seed data.
- **Complexity:** Small
- **Dependencies:** None

### P1-010: Fix public status page HTTP 500 for partial outage
- **Source:** QA_CHECKLIST.md (Note #4)
- **Description:** GET /status returns HTTP 500 when monitors are in partial-outage state. Should use 503 (Service Unavailable) instead of 500 (Internal Server Error), which implies an application crash.
- **Complexity:** Small
- **Dependencies:** None

---

## Priority 2: Important Features
> Things that significantly improve the product

### P2-001: Route queries to rollup data (UptimeCalculationService)
- **Source:** DB_OPTIMIZATION_PLAN.md (DB-007, COMPLETED per plan but PENDING per DB_SPECIALIST_FINDINGS.md)
- **Description:** Create an `UptimeCalculationService` that routes queries to raw data (last 24h) vs rollup data (historical). BadgeService and DashboardController still query raw `monitor_checks` for all time ranges. For 30-day uptime, this means scanning 43,200 rows instead of 30 rollup rows per monitor.
- **Complexity:** Large
- **Dependencies:** DB-004 (completed)

### P2-002: Redis caching layer for dashboard/badges
- **Source:** DB_OPTIMIZATION_PLAN.md (DB-008, COMPLETED per plan but PENDING per DB_SPECIALIST_FINDINGS.md)
- **Description:** Implement MonitorCacheService with Redis-backed caching: uptime (60s TTL), dashboard summary (30s TTL), badge SVGs (5min TTL). Reduces redundant DB queries for frequently accessed data.
- **Complexity:** Medium
- **Dependencies:** DB-007

### P2-003: Response time graphs on monitor detail pages -- DONE
- **Source:** USER_TESTING_POWER_USER.md (Recommendation #2)
- **Description:** Per-monitor response time charts over 24h/7d/30d time ranges. This is the single most expected feature from any monitoring tool and is currently absent from the monitor view page.
- **Complexity:** Medium
- **Dependencies:** None
- **Resolution:** Added Chart.js line chart on monitor detail page with green/red dots for success/failure, time range selector (24h/7d/30d), and tooltips showing exact values.

### P2-004: Alert rules management UI in admin panel -- DONE
- **Source:** USER_TESTING_POWER_USER.md, QA_CHECKLIST.md
- **Description:** Alert rules currently can only be managed via API (`/api/v1/alert-rules`). There is no admin panel UI for creating, editing, or deleting alert rules. This is a critical UX gap.
- **Complexity:** Medium
- **Dependencies:** None
- **Resolution:** Created AlertRulesController (web) with index/add/edit/delete, templates, and sidebar link under Monitoring section.

### P2-005: Super Admin integration tests
- **Source:** SUPER_ADMIN_PLAN.md (TASK-SA-016, PENDING)
- **Description:** Tests for all super admin controllers: access control (non-super-admin gets 403), page loads, impersonation flow, MetricsService.
- **Complexity:** Medium
- **Dependencies:** All Super Admin tasks (completed)

### P2-006: Complete i18n implementation (locale files and template wrapping)
- **Source:** I18N_PLAN.md (nearly all items unchecked), USER_TESTING_MOM.md, USER_TESTING_DETAIL_QA.md
- **Description:** The i18n plan identifies 38+ templates needing translation wrapping, 6 controllers needing Flash message translation, 5 models needing validation message translation, and 4 email templates. Locale .po files exist for some domains (billing, api, onboarding, organizations) but most original templates (Monitors, Incidents, Checks, Subscribers, Users, Settings, EmailLogs, Status, Error pages, layouts) still use hardcoded strings. The checklist from I18N_PLAN.md shows nearly every item unchecked.
- **Complexity:** Large
- **Dependencies:** None

### P2-007: Standardize UI consistency issues
- **Source:** USER_TESTING_DETAIL_QA.md (MED-001 through MED-017)
- **Description:** Multiple consistency issues: (a) Date format inconsistency across pages (DD/MM, YYYY-MM-DD, ISO, local-datetime JS). (b) Pagination text format varies across pages. (c) "Add" button uses 3 different CSS classes. (d) Submit buttons use 4 different styles. (e) Page headers mix h1/h2, inconsistent wrapper classes, some with emojis. (f) Monitor type capitalization differs (HTTP vs Http). (g) Empty states have varying detail levels.
- **Complexity:** Medium (many small fixes across many files)
- **Dependencies:** None

### P2-008: Notification channels — Slack, Discord, Teams (with setup UI) -- DONE
- **Source:** USER_TESTING_POWER_USER.md (Recommendation #3), PROJECT_SUMMARY.md
- **Description:** Alert channel implementations exist (Slack, Discord, Telegram, Webhook) but there is no admin UI for configuring them. Settings page has SMS/Telegram/WhatsApp toggles but no credential configuration UI. Users need a way to set up Slack webhooks, Discord webhooks, Telegram bot tokens, etc. from the admin panel.
- **Complexity:** Large
- **Dependencies:** TASK-1003 (completed)
- **Resolution:** Added "Channels" tab to Settings page with channel cards for Slack, Discord, Telegram, and Custom Webhook. Each card has input fields, connection status badges, and test buttons. Added saveChannels() and testNotificationChannel() actions to SettingsController. Channel configs stored in settings table with channel_ prefix.

### P2-009: Add "Quick Setup" wizard for monitors -- DONE
- **Source:** USER_TESTING_MOM.md (Recommendation #2), USER_TESTING_POWER_USER.md
- **Description:** For the most common case (website monitoring), just ask for a URL and auto-fill technical defaults. Hide advanced fields (Headers, Body, HTTP methods, SSL verification) behind an "Advanced Options" toggle. The current form scored 1/5 from the non-technical user persona.
- **Complexity:** Medium
- **Dependencies:** None
- **Resolution:** Added Quick Setup / Advanced mode toggle to Monitors/add.php. Quick Setup shows URL input, auto-filled name, interval dropdown, and notification checkboxes. Auto-detects monitor type (HTTP, Ping, Port) from URL pattern. JavaScript in monitor-form.js handles mode switching and auto-detection.

### P2-010: Add landing/welcome page -- DONE
- **Source:** USER_TESTING_MOM.md (Recommendation #3), USER_TESTING_POWER_USER.md
- **Description:** No landing page exists. Visiting / when unauthenticated redirects straight to login with no context. Need a page explaining what the tool does, with pricing info and a sign-up CTA. Pricing page is currently behind authentication.
- **Complexity:** Medium
- **Dependencies:** None
- **Resolution:** PagesController now renders landing.php for unauthenticated visitors (redirects to /dashboard for authenticated). Landing page includes hero section with blue gradient, features grid (4 cards), pricing section (Free/Pro/Business), and footer. Created landing.css with responsive design. Route unchanged (/ already points to Pages::home).

### P2-011: Uptime history bars (30/60/90 day visual bars) -- DONE
- **Source:** USER_TESTING_POWER_USER.md (Recommendation #8)
- **Description:** The 30/60/90-day visual bar showing green/red segments by day is the most recognized UX pattern in monitoring tools. Should appear on monitor list, monitor detail, and status pages.
- **Complexity:** Medium
- **Dependencies:** DB-007 (rollup data)
- **Resolution:** Created uptime_bar element with color-coded 30-day segments (green/yellow/red/grey). Displayed on monitor detail page, monitors index (compact), and public status page. Uses efficient GROUP BY DATE aggregate query.

### P2-012: Monitor groups/tags -- DONE
- **Source:** USER_TESTING_POWER_USER.md (Recommendation #5)
- **Description:** At 50+ monitors, a flat list becomes unmanageable. Need tags, folders, or grouping. No tagging system exists currently.
- **Complexity:** Medium
- **Dependencies:** None
- **Resolution:** Added tags TEXT (JSON array) column to monitors table via migration. Monitor entity has getTags(), setTags(), hasTag(), getTagColor() methods. Tags shown as colored pills on index page. Tag filter dropdown in monitors list. Tags input (comma-separated) on add/edit forms. Controller parses tags on save and supports ?tag= query param.

### P2-NEW: Fix mobile status page and landing page responsive issues -- DONE
- **Source:** Manual mobile audit
- **Description:** Fixed mobile responsive issues across public status page, landing page, monitor detail page, and auth pages. Improvements include: uptime bar overflow on narrow screens (height/min-width adjustments at 768px and 480px), landing page nav hamburger touch target (min 44px), landing page nav links touch-friendly sizing, hero/CTA buttons full-width on mobile, feature/pricing cards disable hover transform on mobile, footer links touch targets, landing page overflow-x prevention, monitor detail page chart overflow fix, stat cards/details grid mobile sizing, incident list mobile stacking.
- **Complexity:** Medium (multiple CSS files)
- **Dependencies:** None
- **Resolution:** Updated public.css (uptime bar mobile rules), landing.css (nav toggle, mobile nav links, hero, features, pricing, footer touch targets, overflow prevention), Monitors/view.php inline styles (chart overflow, stat cards, details grid, incidents mobile).

### P2-013: Bulk operations -- DONE
- **Source:** USER_TESTING_POWER_USER.md (Recommendation #6)
- **Description:** Select-all checkbox, bulk pause, bulk delete, bulk tag assignment. Also add CSV/JSON import for migration from other tools (UptimeRobot/Pingdom).
- **Complexity:** Medium
- **Dependencies:** None
- **Resolution:** Added select-all checkbox in table header, per-row checkboxes, bulk action bar (Pause/Resume/Delete Selected) with confirmation dialogs. Added bulkAction() POST endpoint in MonitorsController. Added CSV import feature with import() action, file upload UI, and support for name/type/url/host/port/check_interval/tags columns. Routes added for /monitors/bulk-action and /monitors/import.

### P2-014: Fix Response Time column showing "-" on monitors list -- DONE
- **Source:** USER_TESTING_DETAIL_QA.md (MED-013), USER_TESTING_POWER_USER.md
- **Description:** All monitors show a dash "-" in the Response Time column on /monitors, even though the dashboard shows response time data for these same monitors. The data exists but is not being displayed in the list view.
- **Complexity:** Small
- **Dependencies:** None
- **Resolution:** Root cause: template accessed $monitor->response_time but the Monitor entity has no such field (response_time lives on MonitorCheck). Fixed by adding a DISTINCT ON query in MonitorsController::index() to fetch the latest check per monitor, passing $latestChecks to the template, and reading response_time from $latestChecks[$monitor->id] instead of the monitor entity.

### P2-015: Comprehensive browser-based testing (Playwright/Cypress)
- **Source:** QA_CHECKLIST.md (~298 of ~370 test cases marked NOT TESTED or REQUIRES BROWSER), DEVELOPMENT_PLAN.md (Module 4.5 E2E Tests)
- **Description:** The QA checklist documents ~298 untested scenarios that require browser-based testing. Need to set up Playwright or Cypress with test scenarios covering: login/registration flows, monitor CRUD, incident management, integration CRUD, status page rendering, billing flows, mobile responsiveness, org switching, API key management, invitation flows, and more.
- **Complexity:** Large
- **Dependencies:** None

---

## Priority 3: Nice-to-Have
> Improvements that can wait

### P3-001: Batch insert for check results
- **Source:** DB_OPTIMIZATION_PLAN.md (DB-009), DB_SPECIALIST_FINDINGS.md
- **Description:** Batch all check results from a single `monitor_check` run into a single multi-row INSERT instead of individual INSERTs. At 10,000 monitors, reduces round-trip overhead by 99.99%.
- **Complexity:** Small
- **Dependencies:** None

### P3-002: Separate error details table
- **Source:** DB_OPTIMIZATION_PLAN.md (DB-011), DB_SPECIALIST_FINDINGS.md
- **Description:** Move `error_message` and `details` TEXT columns from `monitor_checks` to a companion `monitor_check_details` table. Reduces main table heap by ~30%.
- **Complexity:** Medium
- **Dependencies:** None

### P3-003: PostgreSQL table partitioning
- **Source:** DB_OPTIMIZATION_PLAN.md (DB-006), DB_SPECIALIST_FINDINGS.md
- **Description:** Weekly range partitions on `monitor_checks` using `checked_at`. Enables instant partition DROP instead of batched DELETE for retention. Implement when `monitor_checks` exceeds 100M rows.
- **Complexity:** Large
- **Dependencies:** None

### P3-004: Add breadcrumb navigation
- **Source:** USER_TESTING_DETAIL_QA.md (LOW-001)
- **Description:** No pages include breadcrumb navigation. Sub-pages like /monitors/add, /monitors/view/1, /monitors/edit/1 have no breadcrumb trail.
- **Complexity:** Medium
- **Dependencies:** None

### P3-005: Manual incident creation button
- **Source:** USER_TESTING_DETAIL_QA.md (LOW-002)
- **Description:** The incidents list page has no "Create Incident" button, unlike Monitors which has "+ New Monitor". Manual incident creation requires editing the URL.
- **Complexity:** Small
- **Dependencies:** None

### P3-006: Dashboard contextual help and plain-language explanations
- **Source:** USER_TESTING_MOM.md (Recommendations #4 and #5)
- **Description:** Add tooltips/help text explaining: what "Degraded" and "Unknown" mean, what response time means ("How fast your website responds -- lower is better"), what the numbers in Recent Checks mean, severity levels in plain language.
- **Complexity:** Small
- **Dependencies:** None

### P3-007: Image upload for site logo (instead of URL-only)
- **Source:** USER_TESTING_MOM.md
- **Description:** Settings page requires a URL for site logo. Users should be able to upload an image file directly.
- **Complexity:** Medium
- **Dependencies:** None

### P3-008: Real-time dashboard updates (WebSocket or auto-refresh)
- **Source:** USER_TESTING_POWER_USER.md (Recommendation #4)
- **Description:** Dashboard and monitor pages are static server-rendered HTML with no auto-refresh. Add WebSocket, SSE, or polling (60s fallback) for live updates.
- **Complexity:** Large
- **Dependencies:** None

### P3-009: Dark mode
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** No dark mode available for the admin panel.
- **Complexity:** Medium
- **Dependencies:** None

### P3-010: Report export (PDF/CSV)
- **Source:** USER_TESTING_POWER_USER.md (Recommendation #9), DEVELOPMENT_PLAN.md (Module 4.2)
- **Description:** PDF and CSV export of uptime reports, incident history, and response time data. Enterprise customers require this for SLAs and compliance.
- **Complexity:** Medium
- **Dependencies:** None

### P3-011: Status page custom branding (themes, colors, custom CSS)
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** No custom branding beyond header/footer text. Need color themes, custom CSS injection, and per-status-page logo support.
- **Complexity:** Medium
- **Dependencies:** None

### P3-012: Embeddable status widget/badge for external sites
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** No embedded status widget for external sites. Need an embeddable JS snippet or iframe widget showing current status.
- **Complexity:** Medium
- **Dependencies:** None

### P3-013: RSS/Atom feed for incidents
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** No RSS/Atom feed exists for incident updates.
- **Complexity:** Small
- **Dependencies:** None

### P3-014: Scheduled maintenance display on public status page
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** Active maintenance windows are not shown on the public status page.
- **Complexity:** Small
- **Dependencies:** None

### P3-015: Users page search/filter
- **Source:** USER_TESTING_DETAIL_QA.md (LOW-006)
- **Description:** The Users page has no search or filter form, unlike other list pages.
- **Complexity:** Small
- **Dependencies:** None

### P3-016: Consistent confirmation dialog messages
- **Source:** USER_TESTING_DETAIL_QA.md (LOW-021)
- **Description:** Delete confirmations inconsistently mention "This action cannot be undone". Should be standardized.
- **Complexity:** Small
- **Dependencies:** None

### P3-017: Fix "Duration: 00h 00m" for short incidents
- **Source:** USER_TESTING_DETAIL_QA.md (LOW-019)
- **Description:** Resolved incidents shorter than 1 minute show "Duration: 00h 00m" instead of the actual seconds.
- **Complexity:** Small
- **Dependencies:** None

### P3-018: Fix "Business" plan badge color (red/danger implies negative)
- **Source:** USER_TESTING_DETAIL_QA.md (LOW-023)
- **Description:** Business plan uses `badge-danger` (red) in super admin, implying something negative when it is the highest tier.
- **Complexity:** Small
- **Dependencies:** None

### P3-019: Bundle Chart.js locally instead of CDN
- **Source:** USER_TESTING_POWER_USER.md, USER_TESTING_DETAIL_QA.md (LOW-008)
- **Description:** Chart.js and Swagger UI are loaded from CDN. Enterprise/air-gapped deployments need local bundling. API docs page is blank if CDN is unavailable.
- **Complexity:** Small
- **Dependencies:** None

### P3-020: Add loading states for dashboard charts
- **Source:** USER_TESTING_DETAIL_QA.md (LOW-017)
- **Description:** Chart.js canvases have no loading state. Users see empty white rectangles while charts render.
- **Complexity:** Small
- **Dependencies:** None

### P3-021: Two-factor auth page missing lang attribute and empty footer
- **Source:** USER_TESTING_DETAIL_QA.md (MED-014, LOW-014)
- **Description:** The `/two-factor/setup` page has no `lang` attribute on `<html>` and renders an empty `<footer>` tag.
- **Complexity:** Small
- **Dependencies:** None

### P3-022: API rate limiting headers not returned
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** API responses should include `X-RateLimit-Limit` and `X-RateLimit-Remaining` headers. The middleware exists but headers may not be surfacing correctly.
- **Complexity:** Small
- **Dependencies:** None

### P3-023: API pagination parameters undocumented
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** List endpoints lack documented pagination parameters. OpenAPI spec should include page/limit query params.
- **Complexity:** Small
- **Dependencies:** None

### P3-024: Fix API monitor creation — configuration field returns null
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** Creating monitors via API results in the `configuration` field returning null in the response, suggesting the JSON configuration is not being properly saved or returned.
- **Complexity:** Small
- **Dependencies:** None

### P3-025: Fix duplicate "QA Test Monitor" on status page
- **Source:** USER_TESTING_DETAIL_QA.md (MED-015), USER_TESTING_POWER_USER.md
- **Description:** The monitor "QA Test Monitor" appears twice in the public status page services section. Data issue or display bug.
- **Complexity:** Small
- **Dependencies:** None

---

## Priority 4: Future Roadmap
> Long-term vision items

### P4-001: WhatsApp Business API alert channel
- **Source:** PROJECT_SUMMARY.md (2026 Q1-Q2 planned), CLAUDE.md
- **Description:** WhatsApp Business API integration for alert notifications.
- **Complexity:** Large
- **Dependencies:** Alert channel infrastructure (completed)

### P4-002: Telegram Bot alert channel (dedicated)
- **Source:** PROJECT_SUMMARY.md (2026 Q1-Q2 planned)
- **Description:** Dedicated Telegram Bot with interactive features (acknowledge incidents, check status). Basic Telegram alert channel exists but a full bot with interactive commands is planned.
- **Complexity:** Large
- **Dependencies:** TASK-1003 (completed)

### P4-003: SMS Gateway alert channel
- **Source:** PROJECT_SUMMARY.md (2026 Q1-Q2 planned)
- **Description:** SMS gateway integration (Twilio, Vonage, etc.) for alert notifications.
- **Complexity:** Medium
- **Dependencies:** Alert channel infrastructure (completed)

### P4-004: SLA tracking
- **Source:** PROJECT_SUMMARY.md (2026 Q1-Q2 planned)
- **Description:** SLA definition per monitor, tracking actual vs. committed uptime, SLA breach alerts, and SLA reports.
- **Complexity:** Large
- **Dependencies:** Rollup data (DB-007)

### P4-005: TimescaleDB evaluation and migration
- **Source:** DB_OPTIMIZATION_PLAN.md (DB-012), DB_SPECIALIST_FINDINGS.md
- **Description:** Evaluate TimescaleDB as a drop-in replacement for manual partitioning, aggregation, and retention. Provides automatic hypertables, continuous aggregates, and 10-20x compression. Adopt after 1M+ rows/day. Full evaluation document at `src/docs/TIMESCALEDB_EVALUATION.md`.
- **Complexity:** Large
- **Dependencies:** DB-006 (partitioning)

### P4-006: Multi-region distributed check workers
- **Source:** SAAS_PLAN.md (TASK-1007 architecture only), USER_TESTING_POWER_USER.md
- **Description:** Architecture for check regions exists (check_regions table, region_id on monitor_checks, 3 seed regions) but actual distributed workers are not implemented. Need worker deployment, result aggregation, and region selection UI.
- **Complexity:** Large
- **Dependencies:** TASK-1007 (completed architecture)

### P4-007: Alert escalation policies
- **Source:** USER_TESTING_POWER_USER.md (Recommendation #7)
- **Description:** Define escalation policies: if no acknowledgment in 5 minutes, escalate to phone call; if no acknowledgment in 15 minutes, page the team lead. Critical for on-call teams.
- **Complexity:** Large
- **Dependencies:** Alert channels, acknowledgment system (completed)

### P4-008: Notification scheduling (quiet hours)
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** Allow users to set quiet hours where non-critical alerts are suppressed or batched.
- **Complexity:** Medium
- **Dependencies:** None

### P4-009: Mobile app / PWA
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** No mobile app and no PWA manifest detected. Mobile web works but a dedicated app or PWA would improve the on-the-go experience.
- **Complexity:** Large
- **Dependencies:** API (completed)

### P4-010: Scheduled email reports
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** Automated weekly/monthly uptime reports delivered via email.
- **Complexity:** Medium
- **Dependencies:** Report export (P3-010)

### P4-011: Monitor import from UptimeRobot/Pingdom/CSV
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** CSV/JSON import and migration tooling from competing services.
- **Complexity:** Medium
- **Dependencies:** None

### P4-012: PagerDuty / OpsGenie / VictorOps integrations
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** First-class integrations with incident management platforms.
- **Complexity:** Medium per integration
- **Dependencies:** Alert channel infrastructure (completed)

### P4-013: Enterprise/custom billing plan
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** No enterprise or custom plan option for large customers. Need configurable plan limits and custom pricing.
- **Complexity:** Medium
- **Dependencies:** Billing system (completed)

### P4-014: Keyboard shortcuts
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** No keyboard shortcuts for power users (e.g., quick-add monitor, navigate between pages).
- **Complexity:** Small
- **Dependencies:** None

### P4-015: Audit log visible to regular admins
- **Source:** USER_TESTING_POWER_USER.md
- **Description:** Only super-admin has access to security logs. Regular org admins should see an activity log for their organization.
- **Complexity:** Medium
- **Dependencies:** Security audit logging (completed)

---

## Summary Statistics

| Priority | Count | Notes |
|----------|-------|-------|
| P1: Production Blockers | 10 | Language fixes, security, broken features |
| P2: Important Features | 15 | UX improvements, missing UI, testing |
| P3: Nice-to-Have | 25 | Polish, minor features, consistency |
| P4: Future Roadmap | 15 | Long-term vision items |
| **Total** | **65** | |

### Top 5 Items by Impact

1. **P1-001** (Language consistency) -- Affects every user on every page visit
2. **P2-006** (Complete i18n) -- Foundation for international adoption
3. **P2-004** (Alert rules admin UI) -- Critical missing admin feature
4. **P2-003** (Response time graphs) -- Table-stakes monitoring feature
5. **P1-003 + P1-004** (Security hardcoding) -- Production security requirement
