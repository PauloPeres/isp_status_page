# ISP Status Page -- Power User Evaluation

**Evaluator:** Jake (DevOps Engineer)
**Date:** 2026-03-27
**Background:** Extensive experience with UptimeRobot, BetterUptime, and Pingdom
**Test Method:** curl-based page inspection, API testing with Bearer auth, database inspection
**Instance:** http://localhost:8765 (Docker: CakePHP + PostgreSQL + Redis)

---

## Executive Summary

ISP Status Page is a self-hosted, multi-tenant monitoring platform with a surprisingly complete feature set for its maturity level. It covers the core monitoring workflow -- creating monitors, running checks, auto-generating incidents, displaying status pages, and exposing a REST API. The multi-tenant architecture with organizations, billing plans, and a super-admin panel positions it as a SaaS-ready platform rather than a simple tool. However, it falls short of production SaaS standards in several critical areas: no bulk operations, no response time graphs on monitor detail pages, limited alerting channels, and mixed-language UI strings.

**Overall Verdict:** Promising foundation, not yet production-ready for paying customers. Would not replace UptimeRobot or BetterUptime today, but could become competitive with 3-6 months of focused development.

---

## Category Ratings and Detailed Analysis

### 1. Onboarding -- Rating: 5/10

**Strengths:**
- Login is straightforward (username/password)
- Dashboard loads immediately after login with a clear overview
- Adding a first monitor is available in 2 clicks (Dashboard -> Monitors -> New Monitor)
- The add-monitor form has helpful tooltips explaining each field
- Multiple monitor types available out of the box: HTTP, Ping, Port, Heartbeat, Keyword, SSL Certificate

**Weaknesses:**
- No guided onboarding wizard or first-run experience
- No "Add your first monitor in 30 seconds" flow like UptimeRobot
- No sample monitors or templates to get started quickly (seed data exists but only for demo purposes)
- No quick-add shortcut from the dashboard -- must navigate to /monitors/add
- No import from UptimeRobot/Pingdom/CSV to ease migration
- Default admin credentials (admin/admin123) with no forced password change on first login -- a security concern

**Comparison to UptimeRobot:** UptimeRobot gets you to your first monitor in ~30 seconds with a minimal form. This app requires navigating through a full admin panel, finding the monitors section, and filling out a more complex multi-section form. Time-to-first-value is roughly 2-3 minutes vs. 30 seconds.

---

### 2. Dashboard -- Rating: 6/10

**Strengths:**
- Clean summary cards showing Total Monitors (4), Online (3), Offline (0), Degraded (0), Unknown (1)
- Active incidents section with severity badges (2 Major)
- Chart.js-powered Uptime (24h) and Average Response Time charts
- Recent Checks table with monitor name, status, response time, and timestamp
- Recent Alerts section (empty state handled)
- Data is passed cleanly to JavaScript via `window.dashboardData`
- Organization switcher in the navbar for multi-tenant support

**Weaknesses:**
- No clickable summary cards to drill down (e.g., clicking "Offline" should filter to offline monitors)
- No real-time updates -- page is static server-rendered HTML, no WebSocket or auto-refresh
- Response time column in Recent Checks shows raw milliseconds with no visual indicator (color coding, sparkline)
- No date range selector for charts
- No uptime percentage displayed on dashboard summary
- Charts use CDN-loaded Chart.js (https://cdn.jsdelivr.net/npm/chart.js) -- a concern for air-gapped deployments
- Missing quick-action buttons (pause all monitors, acknowledge all incidents)

**Comparison to BetterUptime:** BetterUptime's dashboard is immediately actionable with color-coded cards, real-time WebSocket updates, and one-click incident acknowledgment. This dashboard is informational but not actionable.

---

### 3. Monitor Management -- Rating: 6/10

**Strengths:**
- Comprehensive monitor types: HTTP/HTTPS, Ping (ICMP), Port (TCP/UDP), Heartbeat, Keyword, SSL Certificate
- HTTP monitor supports all methods (GET/POST/PUT/DELETE/HEAD/OPTIONS/PATCH), custom headers, body, SSL verification, redirect following, expected content matching
- Sortable table columns (Status, Name, Type, Target, Last Check, State)
- Search and filter bar: text search, type filter, status filter, active/inactive filter
- Per-monitor actions: View, Edit, Activate/Deactivate (toggle), Delete (with confirmation)
- Pagination with page count display ("Page 1 of 1, showing 5 of 5 monitors")
- Monitor detail view shows uptime %, average response time, check count, and interval
- Configurable check interval, timeout, and retry count

**Weaknesses:**
- No bulk actions (select multiple monitors to pause, delete, or change interval)
- No monitor groups or tags for organization
- No drag-and-drop reordering (display_order field exists but no UI for it)
- No monitor cloning/duplication
- Response time column shows "-" for all monitors in the list view -- appears broken
- Monitor detail view lacks response time graph/chart -- critical missing feature
- No uptime history bars (the 30/60/90 day visual bars that UptimeRobot shows)
- Configuration field returns null in API for some monitors (QA Test Monitor) suggesting data integrity issues
- No "Test Now" / "Check Now" button to force an immediate check
- No multi-region check support

---

### 4. Alerting -- Rating: 4/10

**Strengths:**
- Alert rules API endpoint exists (/api/v1/alert-rules CRUD)
- Settings page has notification controls: enable/disable email, SMS, Telegram, WhatsApp alerts
- Alert throttle setting (minimum minutes between alerts for same monitor)
- Incidents are auto-created when monitors go down (auto_created flag, robot badge in UI)
- Incident acknowledgment system with token-based acknowledgment

**Weaknesses:**
- Alert rules creation via API returned validation errors -- unclear documentation on valid trigger_on values and recipients format
- No alert rule management UI found in the admin panel (only API-based?)
- No escalation policies (critical for on-call teams)
- No notification scheduling (quiet hours)
- No alert grouping or deduplication
- SMS, Telegram, and WhatsApp alert toggles exist in settings but no configuration UI for credentials/numbers
- No PagerDuty, OpsGenie, or VictorOps integration
- No phone call alerts
- No per-monitor alert configuration (only global settings visible)
- "Recent Alerts" section on dashboard shows "No recent alerts" despite active incidents

**Comparison to UptimeRobot:** UptimeRobot has a polished alert contacts system with per-monitor notification routing, escalation chains, and easy webhook/Slack configuration. This app's alerting feels incomplete.

---

### 5. Status Pages -- Rating: 6/10

**Strengths:**
- Multiple status pages supported (list at /status-pages with create/edit/delete)
- Customizable: name, URL slug, custom domain, header text, footer text
- Selective monitor display (choose which monitors appear)
- Password protection option
- Show/hide uptime chart and incident history toggles
- Active/inactive toggle per status page
- Public page design is clean with service cards showing uptime percentage, status badges, monitor type, and target
- Color-coded uptime percentage (excellent/good/poor)
- Incident timeline with status badges (Investigating, Resolved)
- Email subscription form built into public page
- History page at /status/history with timeline view
- Status banner shows overall system status ("Some services experiencing issues")

**Weaknesses:**
- No custom branding beyond header/footer text (no color themes, no CSS customization)
- No custom logo per status page (global site_logo_url only)
- Mixed languages: banner says "Alguns servicos estao com problemas" (Portuguese) while rest is English
- Public page shows "QA Test Monitor" listed twice -- appears to be a data bug
- No embedded status widget / badge for external sites
- No uptime history bars (the visual 90-day bar that BetterUptime does beautifully)
- No RSS/Atom feed for incidents
- No scheduled maintenance display on public page
- No custom CSS/JS injection for branding

**Comparison to BetterUptime:** BetterUptime's status pages are beautiful with custom themes, embedded widgets, and incident update timelines. This is functional but visually basic and lacks branding depth.

---

### 6. API -- Rating: 7/10

**Strengths:**
- Full OpenAPI 3.0.3 specification at /api-docs/openapi.yaml with Swagger UI at /api/docs
- Comprehensive endpoints: Monitors (CRUD + pause/resume), Incidents (CRUD), Checks (list), Alert Rules (CRUD)
- Clean REST design with consistent JSON responses: `{"success": true, "data": {...}}`
- Bearer token authentication with API key system
- API key management UI with granular permissions (read, write, admin)
- Keys are stored hashed (key_hash column) -- good security practice
- Key shown once on creation with warning "Copy the key now - you will not see it again!"
- Proper HTTP status codes (401 for invalid auth, 400 for validation errors)
- Detailed validation error messages: "Validation failed: channel: This field is required; trigger_on: This field is required"
- Monitor creation via API works correctly

**Weaknesses:**
- Configuration field not properly passed through in API-created monitors (returned null)
- No pagination parameters documented for list endpoints
- No rate limiting headers in response (X-RateLimit-Remaining, etc.)
- Alert rules API has undocumented validation requirements (trigger_on valid values, recipients format)
- No webhook/event subscription API (for real-time push notifications)
- No bulk operations endpoint
- No SDK or client library provided
- API versioning (/api/v1/) is good but no deprecation policy documented

**SDK-readiness:** The OpenAPI spec makes auto-generating clients possible, but the spec would need better examples and documentation of edge cases.

---

### 7. Billing -- Rating: 6/10

**Strengths:**
- Three clear plans: Free ($0), Pro ($15/mo), Business ($45/mo)
- Monthly/Yearly toggle with 20% annual discount
- Feature comparison is clear and differentiated:
  - Free: 1 monitor, 5min interval, email only, 1 status page, 1 team member, 7 days retention
  - Pro: 50 monitors, 1min interval, Email+Slack+Webhook, 1 custom status page, 5 team members, API access (1K req/hr), 30 days retention, SSL monitoring
  - Business: Unlimited monitors, 30sec interval, All channels+SMS, 5 custom status pages, unlimited team members, API access (10K req/hr), 90 days retention, SSL monitoring, priority support
- Super admin dashboard shows MRR ($45.00), plan distribution chart
- "Most Popular" badge on Pro plan

**Weaknesses:**
- No self-service upgrade flow visible (billing portal form exists but unclear if Stripe is integrated)
- No usage meters or current plan consumption display
- No invoice history or receipt download
- No trial period for paid plans
- No enterprise/custom plan option
- Plan limits don't appear to be enforced in the application (could create more monitors than plan allows)
- No prorated billing or mid-cycle changes mentioned
- Pricing page available only to logged-in users (should be public for marketing)

---

### 8. Integrations -- Rating: 3/10

**Strengths:**
- Integration system exists with add/edit/delete
- Three integration types: IXC Soft, Zabbix, REST API
- Configurable: base URL, HTTP method, timeout, auth type (none/bearer/basic/API key), custom headers
- Statistics cards (total, active, inactive, etc.)
- Filter and search UI

**Weaknesses:**
- No integrations configured out of the box -- empty state
- No Slack integration (despite being listed in Pro plan features)
- No Discord integration
- No webhook integration for outbound notifications
- No PagerDuty, OpsGenie, or other incident management tool integration
- Integration types (IXC Soft, Zabbix) are niche ISP-specific tools, not mainstream DevOps tools
- Mixed-language UI: "Integracoes", "Nova Integracao", "Informacoes Basicas", "Configuracao de Conexao"
- REST API integration type is for inbound data, not outbound notification

**Comparison to UptimeRobot:** UptimeRobot has 10+ built-in notification integrations (Slack, Discord, Teams, Telegram, PagerDuty, webhooks, etc.) that take 30 seconds to configure. This app's integration system is designed for ISP-specific data sources, not notification channels.

---

### 9. Missing Features vs. UptimeRobot -- Rating: 3/10

| Feature | Status | Notes |
|---------|--------|-------|
| Bulk monitor import | Missing | No CSV/JSON import, no migration from other tools |
| Monitor groups/tags | Missing | No tagging system, no grouping, flat list only |
| Uptime reports export (PDF/CSV) | Missing | No export functionality anywhere |
| Incident templates | Missing | Manual incident creation only, no templates |
| Multi-region checks | Missing | Single-region only (region_id field exists but unused) |
| Response time graphs | Partial | Dashboard has aggregate chart, but no per-monitor response time graph |
| Real-time WebSocket updates | Missing | All pages are static server-rendered HTML |
| Mobile app | Missing | No mobile app, no PWA manifest detected |
| Notification escalation policies | Missing | No escalation system |
| Scheduled reports | Missing | No scheduled email reports |
| Heartbeat monitoring | Present | Available as monitor type |
| SSL certificate monitoring | Present | Available as monitor type |
| Keyword monitoring | Present | Available as monitor type |
| Maintenance windows | Present | Full CRUD with affected monitors, auto-suppress alerts, subscriber notifications |
| Multi-tenant / Organizations | Present | Full multi-org support with org switcher |
| User management & invitations | Present | Users, roles, invitation system |
| Email logging | Present | Email send tracking with success/failure stats |
| Super admin panel | Present | Platform-wide overview, revenue tracking, org management, security logs |

---

### 10. Overall Polish -- Rating: 5/10

**Strengths:**
- Consistent admin panel design with sidebar navigation
- Mobile-responsive layout with hamburger menu
- Proper CSRF protection on all forms
- Confirmation dialogs on destructive actions (delete, deactivate)
- Proper favicon and theme-color meta tags
- Footer with version number (1.0.0)
- Reasonable page load times
- Good security practices: hashed API keys, CSRF tokens, permission-based API access
- Docker-based deployment with PostgreSQL and Redis

**Weaknesses:**
- Mixed language strings throughout the UI:
  - Portuguese: "Integracoes", "Nova Integracao", "Informacoes Basicas", "Configuracao de Conexao", "Alguns servicos estao com problemas", "Auto-criado", "Aguardando reconhecimento"
  - English: Most other UI elements
  - This is the single biggest polish issue -- it makes the product feel unfinished
- Emoji-heavy navigation (every nav item has an emoji icon) looks unprofessional
- Public status page shows "QA Test Monitor" duplicated -- data integrity issue
- Response time column in monitors list shows "-" for all monitors
- Monitor view page lacks charts/graphs -- just raw statistics
- No dark mode
- No keyboard shortcuts
- No loading states or skeleton screens
- External CDN dependency (Chart.js from jsdelivr) -- problematic for enterprise/air-gapped deployments
- No audit log visible to regular admins (only super-admin security logs)
- Settings page has no search -- must scan through all tabs

---

## Summary Scorecard

| Category | Rating | Weight | Weighted |
|----------|--------|--------|----------|
| 1. Onboarding | 5/10 | 10% | 0.50 |
| 2. Dashboard | 6/10 | 15% | 0.90 |
| 3. Monitor Management | 6/10 | 20% | 1.20 |
| 4. Alerting | 4/10 | 15% | 0.60 |
| 5. Status Pages | 6/10 | 10% | 0.60 |
| 6. API | 7/10 | 10% | 0.70 |
| 7. Billing | 6/10 | 5% | 0.30 |
| 8. Integrations | 3/10 | 5% | 0.15 |
| 9. Feature Completeness | 3/10 | 5% | 0.15 |
| 10. Overall Polish | 5/10 | 5% | 0.25 |
| **Overall Weighted Score** | | | **5.35/10** |

---

## Top 10 Recommendations (Priority Ordered)

1. **Fix the language consistency issue.** Every string in the UI must be in one language (English) or properly internationalized with locale switching. Mixed Portuguese/English is the most visible quality problem.

2. **Add response time graphs to monitor detail pages.** This is the single most expected feature from any monitoring tool. Per-monitor response time charts over 24h/7d/30d are table stakes.

3. **Build proper notification integrations.** Add Slack, Discord, Microsoft Teams, and generic webhook as first-class notification channels with easy setup UI. The current integration system is ISP-specific and does not serve the monitoring notification use case.

4. **Implement real-time updates.** Add WebSocket or SSE for dashboard and monitor pages. Polling every 60 seconds is acceptable as a fallback, but real-time updates are expected in 2026.

5. **Add monitor groups/tags.** At 50+ monitors, a flat list becomes unmanageable. Tags, folders, or groups are essential for any team managing infrastructure at scale.

6. **Add bulk operations.** Select-all checkbox, bulk pause, bulk delete, bulk tag assignment. Also add CSV/JSON import for migration from other tools.

7. **Build an alert escalation system.** Define escalation policies: if no acknowledgment in 5 minutes, escalate to phone call; if no acknowledgment in 15 minutes, page the team lead. This is what separates professional monitoring from hobby projects.

8. **Add uptime history bars.** The 30/60/90-day visual bar showing green/red segments by day is the most recognized UX pattern in monitoring. Both UptimeRobot and BetterUptime use this. It should appear on monitor list, monitor detail, and status pages.

9. **Add report export.** PDF and CSV export of uptime reports, incident history, and response time data. Enterprise customers require this for SLAs and compliance.

10. **Enforce plan limits.** The billing system defines limits (1 monitor on Free, 50 on Pro) but they do not appear to be enforced. This is critical for monetization.

---

## Would I Pay for This?

**Not today.** The product has strong architectural foundations (multi-tenant, API, OpenAPI spec, proper security), but the user-facing experience is not competitive with even UptimeRobot's free tier. The mixed-language issue alone would disqualify it for any English-speaking team. The lack of response time graphs, real-time updates, and proper notification integrations means it cannot replace the tools I currently use.

**However**, if the top 5 recommendations above were addressed, I would consider it for a self-hosted deployment where data sovereignty is a concern. The self-hosted angle is this product's strongest differentiator -- neither UptimeRobot nor BetterUptime offer self-hosted options. If the team leans into that positioning and delivers a polished experience, there is a real market opportunity.

---

## API Test Results Summary

| Endpoint | Method | Result |
|----------|--------|--------|
| /api/v1/monitors | GET | Pass -- returned 5 monitors with full details |
| /api/v1/monitors | POST | Partial -- created monitor but configuration field returned null |
| /api/v1/monitors/{id} | GET | Pass -- returned single monitor |
| /api/v1/monitors/{id} | DELETE | Pass -- clean deletion response |
| /api/v1/monitors/{id}/pause | POST | Pass -- toggled active to false |
| /api/v1/monitors/{id}/resume | POST | Pass -- toggled active to true |
| /api/v1/monitors/{id}/checks | GET | Pass -- returned check history |
| /api/v1/incidents | GET | Pass -- returned 2 incidents with monitor details |
| /api/v1/checks | GET | Pass -- returned recent checks across monitors |
| /api/v1/alert-rules | GET | Pass -- returned empty array |
| /api/v1/alert-rules | POST | Fail -- validation errors with unclear field requirements |
| Auth (invalid key) | GET | Pass -- proper 401 with clear error message |
| Auth (no header) | GET | Pass -- proper 401 with usage hint |

---

*Evaluation conducted 2026-03-27 by Jake (DevOps Engineer)*
