# Notification Credits System + WhatsApp/SMS Channels

> Created: 2026-03-28

## Business Rationale

WhatsApp and SMS have real per-message costs ($0.04-0.08/msg for Brazil). To protect margins while offering these premium channels, we implement a credit-based system. Free channels (Email, Slack, Discord, Telegram, Webhook) remain unlimited.

## Vendor Selection

| Channel | Vendor | Cost per Message (Brazil) | Why |
|---------|--------|--------------------------|-----|
| SMS | Twilio | ~$0.04/msg | Reliable, global reach, single SDK |
| WhatsApp | Twilio WhatsApp API | ~$0.05/msg | Pre-approved templates, same SDK as SMS |

**Alternative vendors for future:** Zenvia (cheaper for Brazil), Vonage, Meta Cloud API (direct WhatsApp).

## Credit System Design

### Pricing
| Plan | Monthly Credits Included | Cost |
|------|-------------------------|------|
| Free | 0 | — |
| Pro | 50 | Included in $15/mo |
| Business | 200 | Included in $45/mo |
| Additional | Buy anytime | $5 per 100 credits |

### Credit Costs
| Channel | Credits per Message |
|---------|-------------------|
| Email | 0 (free) |
| Slack | 0 (free) |
| Discord | 0 (free) |
| Telegram | 0 (free) |
| Webhook | 0 (free) |
| SMS | 1 credit |
| WhatsApp | 1 credit |

### Behavior
- Before sending SMS/WhatsApp: check credit balance
- Sufficient balance → send, deduct 1 credit, log transaction
- Insufficient balance → skip paid channel, fall back to free channels, log warning
- Low credit warning at 10 credits remaining (email to org owner)
- Monthly grant: credits reset/added on billing cycle date
- Unused credits do NOT roll over (use it or lose it)
- Auto-recharge option: auto-buy 100 credits when balance < threshold

---

## Tasks

### TASK-NC-001: Create notification_credits and transactions tables
- **Status:** COMPLETED
- **Description:** Database tables for credit balances and transaction history.
- **Result:** Created migrations 20260328000140_CreateNotificationCredits.php and 20260328000141_CreateNotificationCreditTransactions.php. Created Model entities (NotificationCredit, NotificationCreditTransaction) and Table classes (NotificationCreditsTable, NotificationCreditTransactionsTable) with TenantScope, belongsTo Organizations, validation rules, and build rules.

### TASK-NC-002: Create NotificationCreditService
- **Status:** COMPLETED
- **Description:** Service for checking balance, deducting credits, purchasing credits, monthly grants, low-balance warnings.
- **Result:** Created src/Service/NotificationCreditService.php with getCredits(), hasCredits(), deduct(), addCredits(), grantMonthlyCredits(), getCostForChannel(), purchaseCredits() (Stripe checkout), logTransaction(), and sendLowBalanceWarning() methods.

### TASK-NC-003: Integrate credits with AlertService
- **Status:** COMPLETED
- **Description:** Before dispatching SMS/WhatsApp alerts, check credits. Fall back to free channels if insufficient.
- **Result:** Modified AlertService::dispatch() to check NotificationCreditService before sending on paid channels (sms, whatsapp). Insufficient credits logs a failure and falls back to email. Credits are deducted after successful send.

### TASK-NC-004: Implement Twilio SMS Alert Channel
- **Status:** COMPLETED
- **Description:** SmsAlertChannel using Twilio API. Env vars: TWILIO_SID, TWILIO_AUTH_TOKEN, TWILIO_FROM_NUMBER.
- **Result:** SmsAlertChannel created at src/Service/Alert/SmsAlertChannel.php. Implements ChannelInterface with Twilio Messages API. Registered in MonitorCheckCommand. Tests in tests/TestCase/Service/Alert/SmsAlertChannelTest.php (13 tests).

### TASK-NC-005: Implement Twilio WhatsApp Alert Channel
- **Status:** COMPLETED
- **Description:** WhatsAppAlertChannel using Twilio WhatsApp API. Uses approved message templates.
- **Result:** WhatsAppAlertChannel created at src/Service/Alert/WhatsAppAlertChannel.php. Uses same Twilio Messages API with 'whatsapp:' prefix on numbers. Env var: TWILIO_WHATSAPP_NUMBER. Registered in MonitorCheckCommand. Tests in tests/TestCase/Service/Alert/WhatsAppAlertChannelTest.php (14 tests).

### TASK-NC-006: Credits Dashboard UI
- **Status:** PENDING
- **Description:** Credit balance display, usage history, buy credits button (Stripe), auto-recharge toggle. Part of Billing page.
- **Result:** _pending_

### TASK-NC-007: Super Admin Credit Management
- **Status:** PENDING
- **Description:** Super Admin view of credit usage across all orgs. Manual credit grant/adjustment. Revenue tracking from credit purchases.
- **Result:** _pending_

### TASK-NC-008: Monthly Credit Grant Command
- **Status:** PENDING
- **Description:** Cron command that grants monthly credits to Pro/Business orgs on their billing cycle.
- **Result:** _pending_

---

## Execution Plan

Agent 1: TASK-NC-001 + NC-002 + NC-003 (credit system core + AlertService integration)
Agent 2: TASK-NC-004 + NC-005 (Twilio SMS + WhatsApp channels)
Agent 3: TASK-NC-006 + NC-007 + NC-008 (UI + Super Admin + monthly grant)
