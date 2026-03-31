# Notification System Redesign Plan

> Created: 2026-03-31
> Status: PLANNED

## Overview

Replace the current Alert Rules + Escalation Policies with a unified 3-layer notification system:

```
Channels (reusable connections)
    ↓
Notification Policies (reusable chains)
    ↓
Monitors (assign a policy)
```

---

## Layer 1: Notification Channels (Administration)

A **Channel** is a configured connection to a notification service. Created once, reused everywhere.

### Examples:
| Channel Name | Type | Configuration |
|---|---|---|
| DevOps Email | email | devops@isp.com, noc@isp.com |
| #incidents Slack | slack | https://hooks.slack.com/T0... |
| Ops Telegram | telegram | bot_token + chat_id |
| PagerDuty Ops | pagerduty | routing_key: abc123 |
| NOC SMS | sms | +5511999..., +5511888... |
| Webhook CRM | webhook | https://crm.example.com/hook |

### Database Schema:
```sql
CREATE TABLE notification_channels (
    id SERIAL PRIMARY KEY,
    organization_id INT NOT NULL REFERENCES organizations(id),
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL, -- email, slack, discord, telegram, sms, whatsapp, pagerduty, opsgenie, webhook
    configuration JSONB NOT NULL, -- type-specific: {recipients: [...], webhook_url: "...", bot_token: "..."}
    active BOOLEAN DEFAULT true,
    created TIMESTAMP,
    modified TIMESTAMP
);
```

### Configuration by Type:
| Type | Config Fields |
|---|---|
| email | recipients: ["email1", "email2"] |
| slack | webhook_url: "https://hooks.slack.com/..." |
| discord | webhook_url: "https://discord.com/api/webhooks/..." |
| telegram | bot_token: "...", chat_id: "..." |
| sms | phone_numbers: ["+55...", "+55..."] |
| whatsapp | phone_numbers: ["+55..."] |
| pagerduty | routing_key: "..." |
| opsgenie | api_key: "..." |
| webhook | url: "https://...", secret: "..." |

### UI: Administration > Channels
- List page: shows all channels with type badge, name, active status
- Form: name, type selector, type-specific configuration fields
- Test button: sends a test message through the channel

---

## Layer 2: Notification Policies (replaces Alert Rules + Escalation)

A **Policy** is a reusable notification chain that defines WHEN and HOW to notify.

### Database Schema:
```sql
CREATE TABLE notification_policies (
    id SERIAL PRIMARY KEY,
    organization_id INT NOT NULL REFERENCES organizations(id),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    trigger_type VARCHAR(20) NOT NULL DEFAULT 'down', -- down, up, degraded, any
    repeat_interval_minutes INT DEFAULT 0, -- 0 = notify once
    active BOOLEAN DEFAULT true,
    created TIMESTAMP,
    modified TIMESTAMP
);

CREATE TABLE notification_policy_steps (
    id SERIAL PRIMARY KEY,
    notification_policy_id INT NOT NULL REFERENCES notification_policies(id) ON DELETE CASCADE,
    step_order INT NOT NULL DEFAULT 1,
    delay_minutes INT NOT NULL DEFAULT 0, -- 0 = immediate
    notification_channel_id INT NOT NULL REFERENCES notification_channels(id),
    notify_on_resolve BOOLEAN DEFAULT true,
    created TIMESTAMP
);
```

### Key Design:
- Steps reference **Channels** (not raw recipients) → change the channel config once, all policies update
- Step 1 is always `delay_minutes: 0` (immediate)
- Steps 2+ have increasing delays ("if not acknowledged after X min")
- `repeat_interval_minutes` = how often to re-notify after the full chain completes (replaces "cooldown")

### UI: Notifications (sidebar)
- List page: shows policies with trigger badge, step count, monitor count
- Form: name, trigger type, chain of steps (each picks a Channel + delay), repeat interval, active

```
Policy: "Production Down Alert"
Trigger: Down

Step 1 — Immediately
  Channel: [DevOps Email ▼]
  ☑ Notify on resolve

Step 2 — After 5 minutes if not acknowledged
  Channel: [#incidents Slack ▼]
  ☑ Notify on resolve

Step 3 — After 15 minutes if not acknowledged
  Channel: [NOC SMS ▼]
  ☑ Notify on resolve

[+ Add Step]

Advanced:
  Repeat every [30] minutes until resolved
  ☑ Active
```

---

## Layer 3: Monitor Assignment

Each monitor has a `notification_policy_id` FK.

### Monitor Form Addition:
```
Notification Policy: [Production Down Alert ▼]
  (Email → Slack → SMS, trigger: down)
  [Create new policy]
```

### Monitor Detail Addition:
"Who Gets Notified" card showing the full chain:
```
Policy: Production Down Alert
1. Immediately → DevOps Email (devops@, noc@)
2. After 5 min → #incidents Slack
3. After 15 min → NOC SMS (+5511999...)
Repeat: every 30 min until resolved
```

---

## Sidebar Menu Changes

### Before:
```
Monitoring
  Monitors
  Checks
  Incidents
  Alert Rules        ← REMOVE
  Escalation         ← REMOVE
  Integrations
  Status Pages
  Maintenance

Administration
  ...
```

### After:
```
Monitoring
  Monitors
  Checks
  Incidents
  Notifications      ← NEW (replaces Alert Rules + Escalation)
  Integrations
  Status Pages
  Maintenance

Administration
  My Profile
  Users
  Invitations
  Channels           ← NEW
  API Keys
  Settings
  Billing
  Activity Log
```

---

## Onboarding Wizard Update

Current steps:
1. Create first monitor
2. Set up alerts ← UPDATE
3. Create status page
4. Invite team

Updated step 2: "Set up notifications"
- Sub-flow:
  1. Create your first Channel (auto-suggest email with user's email)
  2. Create a Notification Policy using that channel
  3. Assign it to your monitor

The onboarding service checks:
- `notification_channels` count > 0 AND `notification_policies` count > 0

---

## Migration Strategy

### Phase 1: Create new tables + API + frontend (additive)
- Create notification_channels, notification_policies, notification_policy_steps tables
- Create backend controllers + routes
- Create frontend components
- Add "Channels" to sidebar under Administration
- Add "Notifications" to sidebar under Monitoring
- Keep old Alert Rules + Escalation pages working

### Phase 2: Data migration
- For each existing AlertRule:
  - Create a Channel from its channel type + recipients
  - Create a NotificationPolicy with one step pointing to that channel
  - Assign to the monitor
- For each EscalationPolicy:
  - Create Channels from each step's channel + recipients
  - Merge steps into the associated NotificationPolicy
- Run migration script, verify data

### Phase 3: Update monitor form + detail
- Add notification_policy_id selector to monitor form
- Add "Who Gets Notified" card to monitor detail
- Update AlertService to read from new tables

### Phase 4: Update onboarding wizard
- Change step 2 to use Channels + Policies

### Phase 5: Remove old system
- Remove Alert Rules + Escalation sidebar items
- Add redirect routes for bookmarks
- Eventually drop old tables

---

## Implementation Order (files)

### Backend:
1. Migration: CreateNotificationChannels
2. Migration: CreateNotificationPolicies + Steps
3. Migration: AddNotificationPolicyIdToMonitors
4. Entity: NotificationChannel
5. Entity: NotificationPolicy + NotificationPolicyStep
6. Table: NotificationChannelsTable (with TenantScope)
7. Table: NotificationPoliciesTable + NotificationPolicyStepsTable
8. Controller: Api/V2/NotificationChannelsController (CRUD + test)
9. Controller: Api/V2/NotificationPoliciesController (CRUD)
10. Routes
11. Update MonitorsTable + entity (belongsTo NotificationPolicy)
12. Data migration script (existing rules → new tables)
13. Update AlertService dispatch logic

### Frontend:
1. notification-channel.service.ts
2. notification-channel-list.component.ts
3. notification-channel-form.component.ts (with type-specific config fields)
4. notification-policy.service.ts
5. notification-policy-list.component.ts
6. notification-policy-form.component.ts (with step chain builder)
7. Update app.routes.ts
8. Update app-layout.component.ts (sidebar)
9. Update monitor-form.component.ts (policy selector)
10. Update monitor-detail.component.ts (who gets notified card)
11. Update onboarding.service.ts + component

---

## Estimated Effort

| Phase | Files | Complexity |
|---|---|---|
| Phase 1: New tables + API | 12 backend files | Medium |
| Phase 2: Data migration | 1 migration script | Medium |
| Phase 3: Frontend (channels) | 3 components + service | Medium |
| Phase 4: Frontend (policies) | 3 components + service | Medium-Large |
| Phase 5: Monitor integration | 2 component updates | Small |
| Phase 6: Onboarding update | 2 file updates | Small |
| Phase 7: Cleanup old system | Remove files + routes | Small |

Total: ~25 files, significant but incremental.
