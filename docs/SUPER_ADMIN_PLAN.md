# Super Admin Panel — Implementation Plan

> Created: 2026-03-28

## Overview

Super Admin dashboard for the ISP Status Page SaaS platform operator. Provides cross-tenant visibility into all organizations, revenue metrics, platform health, and customer management.

---

## Tasks

### TASK-SA-001: Migration — Add `is_super_admin` to Users
- **Status:** COMPLETED
- **Description:** Add boolean `is_super_admin` column (default false) to users table. Update User entity with helper method.
- **Files:** Migration, User entity, UsersFixture
- **Result:** Migration 20260328000070_AddSuperAdminToUsers.php created. User entity updated with isSuperAdmin() method and $_accessible entry. UsersFixture updated with is_super_admin field on all records.

### TASK-SA-002: SuperAdminMiddleware
- **Status:** COMPLETED
- **Description:** Middleware gating /super-admin/* routes. Checks is_super_admin, resets TenantContext.
- **Depends on:** SA-001
- **Result:** SuperAdminMiddleware.php created with JSON/HTML 403 responses. Registered in Application.php after TenantMiddleware. /super-admin added to TenantMiddleware $publicPaths.

### TASK-SA-003: SuperAdminAppController Base Class
- **Status:** COMPLETED
- **Description:** Base controller for all super admin controllers. Sets super_admin layout, bypasses TenantScope.
- **Depends on:** SA-002
- **Result:** SuperAdmin/AppController.php created with super_admin layout, TenantContext::reset(), and fetchTableAll() helper.

### TASK-SA-004: Super Admin Layout and Sidebar
- **Status:** COMPLETED
- **Description:** Dedicated layout with dark/red accent, sidebar nav, impersonation banner. CSS in admin.css.
- **Depends on:** SA-003
- **Result:** super_admin.php layout, super_admin/sidebar.php, super_admin/navbar.php created. Super admin CSS styles appended to admin.css with dark charcoal/red color scheme.

### TASK-SA-005: Routes Configuration
- **Status:** COMPLETED
- **Description:** All /super-admin/* routes using CakePHP prefix routing.
- **Depends on:** SA-003
- **Result:** SuperAdmin prefix routes added to routes.php (Dashboard, Organizations, Users, Revenue, Health + impersonation). Super Admin link added to admin sidebar (conditional on isSuperAdmin). AppController updated to load and set isSuperAdmin view variable.

### TASK-SA-006: MetricsService — Revenue KPIs
- **Status:** COMPLETED
- **Description:** MRR, ARR, revenue by plan, churn rate, ARPU, trial conversion. 5-min Redis cache.
- **Depends on:** SA-001
- **Result:** Implemented in `src/src/Service/SuperAdmin/MetricsService.php` — methods: `getRevenueMetrics()`, `getGrowthMetrics()`, `getTrialMetrics()`

### TASK-SA-007: MetricsService — Customer & Platform KPIs
- **Status:** COMPLETED
- **Description:** Total orgs, top orgs by usage, platform health (checks/alerts/API), user engagement (DAU/WAU/MAU).
- **Depends on:** SA-006
- **Result:** Implemented in `src/src/Service/SuperAdmin/MetricsService.php` — methods: `getCustomerMetrics()`, `getPlatformHealthMetrics()`, `getUserEngagementMetrics()`

### TASK-SA-008: Super Admin Dashboard Controller & View
- **Status:** COMPLETED
- **Description:** Main /super-admin page with KPI cards (MRR, Orgs, Monitors, Incidents), charts (plan distribution, signup trends), tables (recent signups, at-risk customers).
- **Depends on:** SA-006, SA-007, SA-004
- **Result:** Created `DashboardController.php` with MetricsService integration. Template includes 4 KPI cards (MRR, Active Orgs, Total Monitors, Active Incidents), Plan Distribution doughnut chart, New Signups line chart, Recent Signups and Top Organizations tables, Platform Health cards, and Trial metrics. Chart.js loaded from CDN with data passed via `window.superAdminData`.

### TASK-SA-009: Organizations List & Detail
- **Status:** COMPLETED
- **Description:** Searchable/filterable/sortable table of all orgs. Detail view with monitors, users, billing, activity.
- **Depends on:** SA-003, SA-004
- **Result:** Created `SuperAdmin/OrganizationsController.php` with `index()` (search, plan filter, pagination, monitor counts) and `view()` (org details, team members, monitors, recent checks, recent incidents). Templates: `SuperAdmin/Organizations/index.php` and `SuperAdmin/Organizations/view.php` with responsive tables, badges, and pagination.

### TASK-SA-010: Organization Impersonation
- **Status:** COMPLETED
- **Description:** "Login as" any org for debugging. Sets TenantContext via session. Red banner in admin UI.
- **Depends on:** SA-009
- **Result:** Added `impersonate()` and `stopImpersonation()` actions to OrganizationsController. Modified `TenantMiddleware` with `resolveFromImpersonation()` method that verifies super admin status before honoring impersonation session. Added red impersonation banner to both `admin.php` and `super_admin.php` layouts reading directly from session.

### TASK-SA-011: Users List & Detail
- **Status:** COMPLETED
- **Description:** Cross-org user directory. Search, filter, view details, toggle super admin flag.
- **Depends on:** SA-003, SA-004
- **Result:** Created `UsersController` (index with search/pagination, view with org memberships and API keys). Templates: `SuperAdmin/Users/index.php` (search bar, users table with org badges, pagination), `SuperAdmin/Users/view.php` (user info cards, organizations table, API keys table). Added `hasMany OrganizationUsers` association to `UsersTable`.

### TASK-SA-012: Revenue Dashboard
- **Status:** COMPLETED
- **Description:** MRR/ARR cards, revenue by plan chart, plan distribution doughnut, top orgs by revenue, trial metrics.
- **Depends on:** SA-006, SA-004
- **Result:** Created `RevenueController.php` with MetricsService integration and Organizations query for paying customers. Template includes MRR/ARR/ARPU/Paid Orgs KPI cards, Revenue by Plan horizontal bar chart, Plan Distribution doughnut chart, Paying Customers table (name, plan, monthly price, since), and Trial metrics section (active trials, conversion rate, total trialed). Shares `super-admin-charts.js` with the dashboard.

### TASK-SA-013: Platform Health Dashboard
- **Status:** COMPLETED
- **Description:** Checks per hour, alert dispatches, monitor type distribution, system status.
- **Depends on:** SA-007, SA-004
- **Result:** Created `HealthController` with MetricsService integration, monitor type distribution query, failed alerts query, and platform stats. Template includes health KPI cards (Total Monitors, Checks Today, Active Incidents, Alerts Today), horizontal bar chart for monitor type distribution (Chart.js), user engagement grid (DAU/WAU/MAU/API Adoption), platform stats cards (Orgs, Users, API Keys, Webhooks), check volume table, and recent failed alerts table.

### TASK-SA-014: Cache Configuration
- **Status:** COMPLETED
- **Description:** 5-minute Redis cache config for super admin metrics.
- **Result:** Added `super_admin` cache config to `src/config/app.php` and `src/config/app_local.php` — RedisEngine (db 4) when Redis available, FileEngine fallback, 300s duration, `sa_` prefix

### TASK-SA-015: Super Admin Link in Regular Sidebar
- **Status:** PENDING
- **Description:** Conditional "Super Admin" link when user has is_super_admin=true.
- **Depends on:** SA-001
- **Result:** _pending_

### TASK-SA-016: Integration Tests
- **Status:** PENDING
- **Description:** Tests for all super admin controllers: access control, page loads, impersonation flow.
- **Depends on:** All above
- **Result:** _pending_

---

## SaaS KPIs Displayed

### Revenue & Growth
- MRR (Monthly Recurring Revenue)
- ARR (Annual Recurring Revenue)
- Revenue by plan tier
- New signups (daily/weekly/monthly)
- Churn rate
- Trial conversion rate
- ARPU (Average Revenue Per User)

### Customer Metrics
- Total organizations (active/inactive/trial)
- Organizations by plan (pie chart)
- Top 10 by monitor count
- Top 10 by check volume
- Approaching plan limits
- At-risk customers

### Platform Health
- Total monitors, checks (today/week/month)
- Average checks per minute
- Alert dispatch rate
- API request volume

### User Engagement
- DAU/WAU/MAU
- API adoption rate
- Feature adoption
- Avg monitors/team size per org

---

## Agent Strategy

Sprint 1: SA-001 → SA-002 → SA-003 → SA-005 (sequential foundation)
Sprint 2 (parallel): SA-004+SA-015 | SA-006+SA-007+SA-014
Sprint 3 (parallel): SA-008+SA-012 | SA-009+SA-010 | SA-011+SA-013
Sprint 4: SA-016 (tests)
