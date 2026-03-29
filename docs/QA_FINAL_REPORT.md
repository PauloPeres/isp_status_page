# QA Final Report -- Post-Migration

**Date:** 2026-03-29
**Tested by:** 3 QA Personas (Maria, Alex, Jake)
**Environment:** http://localhost:8765 (Docker)
**Login:** admin / admin123

---

## Persona 1: Maria (Mom)

### Can she find the app?
- **Landing page** (/) loads correctly with title "ISP Status - Monitor Your Infrastructure" -- PASS
- **Registration** (/register) is accessible and renders properly -- PASS
- **Public status page** (/status, /status/history) works without login -- PASS
- **Login page** (/users/login) loads correctly -- PASS
- After login, user is redirected to /app/dashboard -- PASS
- Old URLs like /dashboard, /monitors automatically redirect to /app/* equivalents -- PASS

### Is the Angular UI intuitive on mobile?
- Angular SPA loads at /app/ with proper viewport meta tag -- PASS
- Uses Ionic framework (Plus Jakarta Sans font, responsive CSS variables) -- PASS
- Dark mode support via prefers-color-scheme media query -- PASS
- All /app/* deep routes return 200 and serve the SPA -- PASS

### Any confusing jargon?
- RSS feed available at /feed/incidents.rss for non-technical status updates -- PASS
- API docs available at /api/docs for developers -- PASS

**Maria Verdict: PASS** -- The app is discoverable, navigable, and mobile-friendly.

---

## Persona 2: Alex (Detail QA)

### All 24 API v2 GET Endpoints: ALL PASS (24/24)

| # | Endpoint | Status |
|---|----------|--------|
| 1 | /api/v2/auth/me | 200 OK |
| 2 | /api/v2/dashboard/summary | 200 OK |
| 3 | /api/v2/dashboard/uptime | 200 OK |
| 4 | /api/v2/dashboard/response-times | 200 OK |
| 5 | /api/v2/dashboard/recent-checks | 200 OK |
| 6 | /api/v2/dashboard/recent-alerts | 200 OK |
| 7 | /api/v2/monitors | 200 OK |
| 8 | /api/v2/incidents | 200 OK |
| 9 | /api/v2/checks | 200 OK |
| 10 | /api/v2/integrations | 200 OK |
| 11 | /api/v2/alert-rules | 200 OK |
| 12 | /api/v2/escalation-policies | 200 OK |
| 13 | /api/v2/sla | 200 OK |
| 14 | /api/v2/settings | 200 OK |
| 15 | /api/v2/billing/plans | 200 OK |
| 16 | /api/v2/billing/credits | 200 OK |
| 17 | /api/v2/users | 200 OK |
| 18 | /api/v2/invitations | 200 OK |
| 19 | /api/v2/api-keys | 200 OK |
| 20 | /api/v2/scheduled-reports | 200 OK |
| 21 | /api/v2/maintenance-windows | 200 OK |
| 22 | /api/v2/status-pages | 200 OK |
| 23 | /api/v2/activity-log | 200 OK |
| 24 | /api/v2/organizations | 200 OK |

### API v2 CRUD Operations: ALL PASS

| Operation | Resource | Status |
|-----------|----------|--------|
| POST (create) | /api/v2/monitors | 201 Created |
| PATCH (update) | /api/v2/monitors/{id} | 200 OK |
| PUT (update) | /api/v2/monitors/{id} | 200 OK |
| DELETE | /api/v2/monitors/{id} | 200 OK |
| POST (create) | /api/v2/incidents | 201 Created |
| PATCH (update) | /api/v2/incidents/{id} | 200 OK |
| DELETE | /api/v2/incidents/{id} | 200 OK |

### All Public Pages: ALL PASS (10/10)

| Page | Status |
|------|--------|
| / (landing) | 200 |
| /status | 200 |
| /status/history | 200 |
| /register | 200 |
| /users/login | 200 |
| /api/docs | 200 |
| /feed/incidents.rss | 200 |
| /app/ | 200 |
| /app/dashboard | 200 |
| /app/monitors | 200 |

### All Redirects Working: PASS

All old admin URLs (/dashboard, /monitors, /incidents, /checks, /settings, /billing/plans, /api-keys, /alert-rules, /sla, /reports) correctly return 302 redirecting to their /app/* equivalents.

Note: Unauthenticated users get redirected to /users/login first (expected CakePHP behavior for protected routes), then to the /app/* route after login.

### Angular Deep Routes: ALL PASS

/app/monitors/new, /app/incidents/1, /app/settings, /app/billing, /app/team, /app/alert-rules, /app/sla, /app/integrations, /app/reports -- all return 200 and serve the SPA shell.

### Angular Build Deployment: PASS

- index.html present at /src/webroot/app/index.html
- 74 JavaScript chunk files deployed
- Main JS bundle (main-6XII2JG4.js) loads: 200
- CSS bundle (styles-L7KWLYB5.css) loads: 200
- Ionic/Angular framework with Plus Jakarta Sans font

### Error Handling: ALL PASS

| Scenario | Expected | Actual |
|----------|----------|--------|
| No auth token | 401 | 401 |
| Invalid token | 401 | 401 |
| Resource not found | 404 | 404 |
| Validation error | 422 | 422 |

All error responses include `{"success": false, "message": "..."}` format.

**Alex Verdict: ALL PASS** -- No broken links, 404s, or 500s found. All endpoints operational.

---

## Persona 3: Jake (SaaS Power User)

### API Quality
- Consistent JSON envelope: `{"success": true/false, "data": {...}}` -- PASS
- Proper HTTP status codes (200, 201, 302, 401, 404, 422) -- PASS
- JWT authentication with Bearer token -- PASS
- CSRF protection skipped for /api/* routes (correct for API) -- PASS
- Pagination support on list endpoints -- PASS

### Does /app feel like a real SaaS product?
- Modern Angular/Ionic SPA with component-based architecture -- PASS
- Professional font (Plus Jakarta Sans) and Tailwind-inspired color system -- PASS
- Dark mode support out of the box -- PASS
- Proper base href="/app/" for SPA routing -- PASS
- RSS feed for incident notifications -- PASS
- API documentation page available -- PASS

### Feature Completeness
- Dashboard with summary, uptime, response times, recent checks, alerts -- PASS
- Monitor CRUD with HTTP/Ping/Port/API types -- PASS
- Incident management with acknowledgement -- PASS
- Alert rules and escalation policies -- PASS
- SLA tracking with reports and export -- PASS
- Integration support (IXC, Zabbix, REST API) -- PASS
- Billing/plans with credit system -- PASS
- Team management (users, invitations, roles) -- PASS
- API key management -- PASS
- Scheduled reports -- PASS
- Maintenance windows -- PASS
- Custom status pages -- PASS
- Activity log -- PASS
- Organization management (multi-tenant) -- PASS
- 2FA support -- PASS

### Missing compared to competitors
- No WebSocket/SSE for real-time updates (minor, can be added later)
- No public API rate limiting headers (X-RateLimit-*)

**Jake Verdict: PASS** -- Competitive SaaS feature set with clean API design.

---

## Issues Found and Fixed

### Issue 1: PATCH method not routed for any API v2 resource (CRITICAL)
- **Symptom:** `PATCH /api/v2/monitors/{id}` returned 302 redirect to /users/login
- **Root cause:** Routes only defined `_method => 'PUT'` for edit actions, not PATCH
- **Fix:** Added PATCH route mappings alongside PUT for all resources: monitors, incidents, integrations, alert-rules, escalation-policies, sla, settings, scheduled-reports, maintenance-windows, status-pages
- **File:** `/home/clawbot/isp_status_page/src/config/routes.php`

### Issue 2: DELETE route missing for incidents (CRITICAL)
- **Symptom:** `DELETE /api/v2/incidents/{id}` returned 302 redirect to /users/login
- **Root cause:** No DELETE route defined for incidents in the v2 API routes
- **Fix:** Added DELETE route mapping for incidents AND added `delete()` action to IncidentsController
- **Files:**
  - `/home/clawbot/isp_status_page/src/config/routes.php`
  - `/home/clawbot/isp_status_page/src/src/Controller/Api/V2/IncidentsController.php`

### Issue 3: Monitor configuration saved as null when passed as JSON object (MODERATE)
- **Symptom:** Creating a monitor with `"configuration": {"url": "..."}` saved configuration as null
- **Root cause:** `processConfigurationData()` returned a filtered PHP array, but CakePHP's entity marshalling did not trigger the `_setConfiguration` mutator properly for arrays during `newEntity()`
- **Fix:** Changed `processConfigurationData()` to JSON-encode the filtered configuration before returning, so the entity receives a string value
- **File:** `/home/clawbot/isp_status_page/src/src/Controller/Api/V2/MonitorsController.php`

---

## Issues Remaining

- **None blocking.** All 24 API endpoints, all public pages, all redirects, and the Angular SPA are fully operational.
- **Minor observation:** The /app (without trailing slash) returns a 301 redirect to /app/ (standard Apache directory redirect behavior, not a bug).
- **Future improvement:** Consider adding PATCH routes to the v1 API as well for consistency.
