# Security Audit Report — 2026-03-30

## Summary

| Severity | Count | Status |
|----------|-------|--------|
| CRITICAL | 3 | TO FIX |
| HIGH | 5 | TO FIX |
| MEDIUM | 7 | TO FIX (priority items) |
| LOW | 3 | NOTED |

## Critical Findings

### VULN-01: Super Admin Authorization Bypass
**Severity**: CRITICAL
**File**: SuperAdmin/AppController.php beforeFilter
**Issue**: `error()` sets status but doesn't halt execution — action continues running
**Fix**: Return response after error to halt dispatch

### VULN-02: JWT Secret Falls Back to 'change-me'
**Severity**: CRITICAL
**File**: JwtService.php line 30
**Issue**: Missing JWT_SECRET/SECURITY_SALT defaults to hardcoded string — attacker can forge tokens
**Fix**: Throw exception if no secret configured

### VULN-05: Bulk Operations Bypass Tenant Scope
**Severity**: CRITICAL
**File**: MonitorsController.php bulkAction()
**Issue**: updateAll/deleteAll skip TenantScope behavior — can modify other orgs' data
**Fix**: Add organization_id condition to bulk operations

## High Findings

### VULN-03: SQL Wildcard Injection in Search
**Fix**: Escape % and _ in LIKE parameters

### VULN-04: Raw SQL Queries Missing Tenant Scope
**Fix**: Add organization_id to raw SQL WHERE clauses

### VULN-06: Missing TenantScope on 5 Tables
**Tables**: SlaDefinitions, SlaReports, ScheduledReports, Heartbeats, Invitations
**Fix**: Add TenantScope behavior

### VULN-07: Settings API Exposes Passwords
**Fix**: Filter sensitive keys from response

### VULN-08/09: No Rate Limiting on API v2
**Fix**: Extend rate limiter to v2 routes

## Medium Findings (fix priority items)

- VULN-10/11: SSRF via monitor URLs and webhook URLs
- VULN-15: WebhookEndpoints index missing role check
- VULN-16: Missing Content-Security-Policy header
- VULN-17: Telegram bot commands lack user authorization
- VULN-18: SQL wildcards in Telegram commands

## Good Practices Observed
- Bcrypt password hashing
- JWT HS256 with explicit algorithm (no confusion attack)
- Refresh tokens stored as SHA-256 hashes
- CSPRNG for all random tokens
- CSRF properly configured
- Sensitive entity fields hidden
- .env in .gitignore
- CORS with origin allowlist
