# Plan Limit Enforcement — Implementation Plan

> Created: 2026-03-30
> Status: TO IMPLEMENT

## Current State

| Limit | Enforced? | Notes |
|-------|-----------|-------|
| `monitor_limit` | PARTIAL | API checks, but returns 403 (should be 402), swallows exceptions silently |
| `team_member_limit` | NO | `PlanService::canAddTeamMember()` exists but is never called |
| `status_page_limit` | NO | No method exists in PlanService |
| `check_interval_min` | NO | `PlanService::getMinCheckInterval()` exists but is never called |
| `api_rate_limit` | PARTIAL | Only v1 routes, v2 routes wide open |
| `data_retention_days` | YES | CleanupCommand handles this |
| Feature flags | NO | `PlanService::canUseFeature()` exists but is never called |

## Plan Limits Reference

| Limit | Free | Pro | Business |
|-------|------|-----|----------|
| Monitors | 1 | 50 | Unlimited |
| Team members | 1 | 5 | Unlimited |
| Status pages | 1 | 1 | 5 |
| Check interval (min) | 300s (5m) | 60s (1m) | 30s |
| API rate limit | 0 (no API) | 1000/hr | 10000/hr |
| Data retention | 7 days | 30 days | 90 days |
| Features | email_alerts | +slack, webhook, ssl, api_access, custom_domains | +all channels, custom_branding, multi_region |

---

## Tasks

### Backend — PlanService Enhancements

- [ ] **B1.** Add `canAddStatusPage(int $orgId): bool` — count org's status pages vs `status_page_limit`
- [ ] **B2.** Add `validateCheckInterval(int $orgId, int $requested): int` — clamp to plan minimum
- [ ] **B3.** Update `enforceLimit()` to support `'status_page'` type
- [ ] **B4.** All plan enforcement methods should return structured data: `['allowed' => bool, 'limit' => int, 'current' => int, 'plan' => string]`

### Backend — Controller Enforcement (402 errors)

All should return HTTP 402 with this response format:
```json
{
  "success": false,
  "message": "Monitor limit reached. Upgrade to Pro for up to 50 monitors.",
  "error_type": "plan_limit_exceeded",
  "data": {
    "limit_type": "monitors",
    "current": 1,
    "limit": 1,
    "current_plan": "free",
    "upgrade_to": "pro"
  }
}
```

| # | Controller | Method | Check | Priority |
|---|-----------|--------|-------|----------|
| **C1** | `MonitorsController` | `add()` | `canAddMonitor()` — change 403→402, add upgrade message | HIGH |
| **C2** | `MonitorsController` | `add()` | Validate `check_interval >= getMinCheckInterval()` | HIGH |
| **C3** | `StatusPagesController` | `add()` | `canAddStatusPage()` | HIGH |
| **C4** | `InvitationsController` | `send()` | `canAddTeamMember()` (count current members + pending invites) | HIGH |
| **C5** | `AlertRulesController` | `add()` | `canUseFeature()` for the selected channel type | MEDIUM |
| **C6** | `WebhookEndpointsController` | `add()` | `canUseFeature('webhook_alerts')` | MEDIUM |
| **C7** | `ApiKeysController` | `add()` | `canUseFeature('api_access')` | MEDIUM |
| **C8** | `IntegrationsController` | `add()` | `canUseFeature('api_access')` | MEDIUM |

### Backend — Feature Flag Mapping

Map alert channel types to feature flags:

| Channel | Required Feature |
|---------|-----------------|
| `email` | `email_alerts` (all plans) |
| `slack` | `slack_alerts` (Pro+) |
| `discord` | `slack_alerts` (Pro+, same tier) |
| `telegram` | `all_alert_channels` (Business+) |
| `sms` | `all_alert_channels` (Business+) |
| `whatsapp` | `all_alert_channels` (Business+) |
| `pagerduty` | `all_alert_channels` (Business+) |
| `opsgenie` | `all_alert_channels` (Business+) |
| `webhook` | `webhook_alerts` (Pro+) |

### Frontend — 402 Error Handling

- [ ] **F1.** Add 402 handling to `ApiService` error extractor — detect `error_type: 'plan_limit_exceeded'`
- [ ] **F2.** Create `UpgradePromptComponent` — modal/toast showing:
  - What limit was hit ("You've reached the monitor limit")
  - Current vs limit ("1 of 1 monitors used")
  - Current plan name
  - CTA button: "Upgrade to {plan}" → navigate to `/billing`
- [ ] **F3.** Wire 402 handling in JWT interceptor — when API returns 402, show the upgrade prompt instead of generic error toast
- [ ] **F4.** Proactive UI gating — on list pages, if limit is reached, change "Add" button to "Upgrade to add more" (optional, nice UX)

### Frontend — Plan Usage API

- [ ] **F5.** Create a `/api/v2/billing/usage` enhanced response that returns current counts:
  ```json
  {
    "monitors": { "current": 1, "limit": 1 },
    "team_members": { "current": 1, "limit": 1 },
    "status_pages": { "current": 0, "limit": 1 },
    "plan": { "slug": "free", "name": "Free" }
  }
  ```

---

## Implementation Order

### Phase 1 — Critical (blocks monetization)
1. B1 + B3: PlanService `canAddStatusPage()`
2. C1: Fix monitors 403→402 with upgrade message
3. C3: Status pages limit check
4. C4: Invitations/team member limit check
5. F1 + F2 + F3: Frontend 402 handling + upgrade prompt

### Phase 2 — Important (feature gating)
6. C2: Check interval enforcement
7. C5: Alert rule channel gating
8. C6-C8: Webhook, API keys, integrations gating
9. B2: Check interval validation

### Phase 3 — Polish
10. F4: Proactive UI button gating
11. F5: Usage API for dashboard display
12. API v2 rate limiting per plan
