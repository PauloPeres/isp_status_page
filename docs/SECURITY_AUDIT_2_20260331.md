# Security Audit Report #2 — 2026-03-31

## Summary

| Severity | Count |
|----------|-------|
| CRITICAL | 2 |
| HIGH | 4 |
| MEDIUM | 5 |
| LOW | 3 |

## Critical

### VULN-201: Incident acknowledgement_token exposed in API responses
- Entity has no $_hidden for acknowledgement_token
- Any viewer can read token and acknowledge via public URL
- Fix: Add $_hidden to Incident entity

### VULN-202: Telegram webhook token uses !== instead of hash_equals()
- Timing attack can leak the token character by character
- Fix: Use hash_equals() for constant-time comparison

## High

### VULN-203: Settings save() can overwrite passwords with masked values
- Settings index() masks passwords as '••••••••' but save() doesn't filter them
- If user saves without changing password, masked value overwrites real password
- Fix: Filter out masked values before saving

### VULN-204: Integration entity exposes API credentials in responses
- IXC/Zabbix/REST API configuration (base_url, username, password) visible
- Fix: Add $_hidden for configuration.password or mask sensitive config fields

### VULN-205: CSV export vulnerable to formula injection
- Values starting with =, +, -, @ can execute formulas when opened in Excel
- Fix: Prefix dangerous values with single quote in csvEscape()

### VULN-206: Incident entity allows mass assignment of acknowledgement fields
- acknowledgement_token, acknowledged_at, acknowledged_by_user_id are accessible
- Attacker can set a known token on any incident
- Fix: Set these to false in $_accessible

## Medium

### VULN-207: SSE EventsController may leak cross-tenant events
- If org_id is 0 or missing, events from all tenants could be streamed
- Fix: Verify org_id is valid before streaming

### VULN-208: NotificationSchedulesController missing org_id scoping on edit/delete
- IDOR: user can edit/delete schedules from other organizations by ID
- Fix: Add organization_id WHERE clause to edit/delete queries

### VULN-209: Webhook HMAC lacks replay protection
- No timestamp in signature means captured webhooks can be replayed
- Fix: Include timestamp in HMAC and reject old signatures

### VULN-210: Organization entity exposes Stripe IDs
- stripe_customer_id and stripe_subscription_id visible in responses
- Fix: Add to $_hidden

### VULN-211: Incident acknowledge uses !== instead of hash_equals()
- Same timing attack as VULN-202
- Fix: Use hash_equals()
