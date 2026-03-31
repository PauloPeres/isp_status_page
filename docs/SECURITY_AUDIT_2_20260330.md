# Security Audit Report #2 -- 2026-03-30

**Scope**: Second-round audit of ISP Status Page, focusing on attack surfaces the first audit may have missed.

## Summary

| Severity | Count |
|----------|-------|
| CRITICAL | 2     |
| HIGH     | 4     |
| MEDIUM   | 5     |
| LOW      | 3     |
| INFO     | 2     |

---

## Critical Findings

### VULN-201: Incident Entity Leaks Acknowledgement Token in API Responses
**Severity**: CRITICAL
**File**: `src/src/Model/Entity/Incident.php` (missing `$_hidden`)
**Lines**: 75-97 (entire `$_accessible` block, no `$_hidden` array defined)

**Issue**: The `Incident` entity has no `$_hidden` array. When incidents are serialized to JSON (e.g., in `IncidentsController::view()` on line 149: `$this->success(['incident' => $incident])`), the `acknowledgement_token` field is included in the API response. This 64-character hex token is the sole authentication for acknowledging an incident via the public URL `/incidents/acknowledge/{id}/{token}`.

**Attack scenario**: Any authenticated user in the organization (including `viewer` role) can call `GET /api/v2/incidents/{id}`, read the `acknowledgement_token` from the response, and acknowledge incidents they should not have permission to acknowledge. More critically, if a viewer forwards the API response to someone outside the organization, that person can acknowledge the incident without any authentication.

**Fix**: Add `$_hidden` to the Incident entity:
```php
protected array $_hidden = [
    'acknowledgement_token',
];
```

---

### VULN-202: Telegram Webhook Token Comparison Vulnerable to Timing Attack
**Severity**: CRITICAL
**File**: `src/src/Controller/Api/V2/TelegramWebhookController.php`, line 71
**Code**: `$token !== $storedToken`

**Issue**: The webhook token comparison uses the `!==` operator, which is vulnerable to timing attacks. An attacker can measure response time differences to determine the correct token character by character. The Telegram webhook URL is `POST /api/v2/telegram/webhook/{org_id}/{token}`, meaning the token is the only authentication -- there is no JWT or other auth layer (the controller explicitly skips JWT auth in `beforeFilter`).

**Attack scenario**: An attacker who knows the org_id (easily guessable as sequential integers) can brute-force the webhook token using timing analysis on the string comparison. Once obtained, they can inject arbitrary Telegram update payloads to trigger bot commands within the organization.

**Fix**: Replace `$token !== $storedToken` with:
```php
if (empty($storedToken) || !hash_equals($storedToken, $token)) {
```

---

## High Findings

### VULN-203: Settings API Save Overwrites Passwords with Masked Value
**Severity**: HIGH
**File**: `src/src/Controller/Api/V2/SettingsController.php`, lines 51-68

**Issue**: The `index()` method masks sensitive values (e.g., `smtp_password` becomes `'--------'`). The `save()` method calls `$service->saveMultiple($data, $this->currentOrgId)` with the raw request data. If the frontend sends back the masked value `'--------'` for a sensitive field (because the user did not change it), the actual password in the database will be overwritten with the literal string `'--------'`.

Note: `saveMultiple` does not appear to be defined anywhere in `SettingService`. This means calling it will either trigger a PHP fatal error, or it falls through to some magic method. Either way, there is no logic to skip masked values.

**Attack scenario**: An admin user opens settings, changes one field (e.g., `site_name`), and submits. The SMTP password, FTP password, Stripe secret key, and Telegram bot token are all overwritten with `'--------'`, breaking email alerts, backups, billing, and Telegram integration.

**Fix**: In the `save()` method, filter out any values that match the mask before saving:
```php
$sensitiveKeys = ['smtp_password', 'backup_ftp_password', 'telegram_bot_token', 'stripe_secret_key', 'twilio_auth_token'];
foreach ($sensitiveKeys as $key) {
    if (isset($data[$key]) && $data[$key] === '--------') {
        unset($data[$key]);
    }
}
```
Also, define the `saveMultiple` method in `SettingService` since it is called but does not exist.

---

### VULN-204: Integration Entity Exposes Credentials in API Responses
**Severity**: HIGH
**File**: `src/src/Model/Entity/Integration.php` (no `$_hidden` array)

**Issue**: The `Integration` entity has no `$_hidden` array. The `configuration` field typically contains API credentials (IXC tokens, Zabbix passwords, REST API auth headers). When the entity is serialized in `IntegrationsController::view()` (line 61: `$this->success(['integration' => $integration])`), all credentials are exposed in the JSON response.

**Attack scenario**: A member or viewer in the organization calls `GET /api/v2/integrations/{id}` and obtains IXC/Zabbix credentials, enabling them to access those systems directly without proper authorization.

**Fix**: Either add sensitive configuration sub-keys to `$_hidden`, or implement a custom `jsonSerialize()` method that redacts credential fields from the configuration JSON.

---

### VULN-205: CSV Export Vulnerable to Formula Injection
**Severity**: HIGH
**File**: `src/src/Controller/Api/V2/ActivityLogController.php`, lines 118-134

**Issue**: The `csvEscape()` method on line 147-153 only handles commas, quotes, and newlines. It does NOT sanitize formula injection characters. If a user's `username`, `event_type`, or `details` field starts with `=`, `+`, `-`, or `@`, the value is written directly into the CSV. When opened in Excel or Google Sheets, this triggers formula execution.

The same issue exists in the CSV import path in `MonitorsController::import()` -- while this is an input path (less dangerous), the monitor names imported via CSV could contain formula-injection payloads that later appear in exported reports.

**Attack scenario**: An attacker registers with username `=cmd|'/C calc'!A1`. An admin exports the activity log CSV and opens it in Excel. The formula executes, potentially running arbitrary commands on the admin's machine.

**Fix**: Prefix any cell value starting with `=`, `+`, `-`, `@`, `\t`, or `\r` with a single quote (`'`) in `csvEscape()`:
```php
private function csvEscape(string $value): string
{
    // Prevent formula injection
    if (preg_match('/^[=+\-@\t\r]/', $value)) {
        $value = "'" . $value;
    }
    if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
        return '"' . str_replace('"', '""', $value) . '"';
    }
    return $value;
}
```

---

### VULN-206: Mass Assignment on Incident Entity Allows Token and Acknowledgement Manipulation
**Severity**: HIGH
**File**: `src/src/Model/Entity/Incident.php`, lines 75-97

**Issue**: The `$_accessible` array allows mass assignment of `acknowledged_by_user_id`, `acknowledged_at`, `acknowledged_via`, and `acknowledgement_token`. In `IncidentsController::add()` (line 188), `$data = $this->request->getData()` is passed directly to `newEntity($data)`. An attacker with `owner` or `admin` role can create an incident that is pre-acknowledged by setting these fields in the POST body, bypassing the acknowledgement workflow entirely. They can also set `acknowledgement_token` to a known value, then use the public acknowledge URL.

**Attack scenario**: An admin creates a manual incident with `{"title": "test", "acknowledgement_token": "aaaa", "acknowledged_at": null}`. They now know the token and can share the acknowledge URL externally.

**Fix**: Set these fields to `false` in `$_accessible`:
```php
'acknowledged_by_user_id' => false,
'acknowledged_at' => false,
'acknowledged_via' => false,
'acknowledgement_token' => false,
```

---

## Medium Findings

### VULN-207: EventsController SSE Stream Bypasses TenantScope with Loose Comparison
**Severity**: MEDIUM
**File**: `src/src/Controller/Api/V2/EventsController.php`, lines 88-92

**Issue**: The `getNewEvents()` method fetches monitors with `->applyOptions(['skipTenantScope' => true])` (line 88) and then manually filters with `$monitor->organization_id == $orgId || !$orgId` (line 92). This has two problems:
1. Uses loose comparison (`==`) instead of strict (`===`). Since `$orgId` comes from the JWT payload as an int, type juggling is possible in edge cases.
2. The condition `!$orgId` means if `currentOrgId` is 0 (which happens when JWT has no org_id or for unauthenticated users), ALL organizations' events are returned.

**Attack scenario**: A user whose JWT has `org_id: 0` (e.g., a user who was removed from all organizations but still has a valid access token) can connect to the SSE stream and see monitor status changes and incident creations for ALL organizations.

**Fix**: Use strict comparison and reject `orgId === 0`:
```php
if ($orgId === 0) { return []; }
// ...
if ($monitor->organization_id === $orgId) {
```

---

### VULN-208: NotificationSchedules Index Missing Organization Scope
**Severity**: MEDIUM
**File**: `src/src/Controller/Api/V2/NotificationSchedulesController.php`, lines 13-32

**Issue**: The `index()` method fetches all notification schedules without filtering by `organization_id`. Compare this to every other controller's `index()` which includes `->where(['Table.organization_id' => $this->currentOrgId])`. The `edit()` and `delete()` methods also fetch by raw ID without organization scope (lines 74, 108).

**Attack scenario**: Any authenticated user can see all organizations' notification schedules. By manipulating the `id` parameter in `edit()` or `delete()`, a user can modify or delete another organization's schedules (IDOR).

**Fix**: Add organization_id filtering to `index()`, and add `->where(['organization_id' => $this->currentOrgId])` to the queries in `edit()` and `delete()`.

---

### VULN-209: WebhookDeliveryService HMAC Signing Not Timing-Safe on Verification Side
**Severity**: MEDIUM
**File**: `src/src/Service/WebhookDeliveryService.php`, line 219

**Issue**: The `sign()` method generates HMAC-SHA256 signatures correctly. However, there is no corresponding `verify()` method provided. If webhook recipients need to verify signatures, the documentation/examples should instruct them to use `hash_equals()`. This is not a direct vulnerability in the application itself, but the absence of a timing-safe verification method could lead customers to implement insecure verification.

Additionally, the `X-Webhook-Signature` header sends a raw hex HMAC. Best practice is to include a timestamp in the signature to prevent replay attacks (e.g., `t=timestamp,v1=signature`).

**Fix**: Add a timestamp to the signature scheme and document timing-safe verification for consumers.

---

### VULN-210: Organization Entity Exposes stripe_customer_id and stripe_subscription_id
**Severity**: MEDIUM
**File**: `src/src/Model/Entity/Organization.php` (no `$_hidden`)

**Issue**: The Organization entity has no `$_hidden` array. In `OrganizationsController::current()` (line 63), the entire organization entity is returned: `$this->success(['organization' => $org])`. This exposes `stripe_customer_id`, `stripe_subscription_id`, and the entire `settings` JSON column (which may contain sensitive configuration).

**Attack scenario**: Any authenticated member of the organization can obtain the Stripe customer/subscription IDs, which could be used for social engineering against Stripe support.

**Fix**: Add `$_hidden` to Organization entity:
```php
protected array $_hidden = [
    'stripe_customer_id',
    'stripe_subscription_id',
    'settings',
];
```

---

### VULN-211: Incident Acknowledge Endpoint Token Comparison Not Timing-Safe
**Severity**: MEDIUM
**File**: `src/src/Controller/IncidentsController.php`, line 164
**Code**: `$token !== $incident->acknowledgement_token`

**Issue**: Same class of vulnerability as VULN-202. The public acknowledge endpoint at `GET /incidents/acknowledge/{id}/{token}` uses `!==` for token comparison, making it vulnerable to timing attacks. The token is 64 hex characters (256 bits of entropy), which provides a large keyspace, but timing attacks can reduce the effective entropy significantly.

**Fix**: Replace with `!hash_equals($incident->acknowledgement_token, $token)`.

---

## Low Findings

### VULN-212: CSP Allows 'unsafe-inline' and 'unsafe-eval' for Scripts
**Severity**: LOW
**File**: `src/src/Middleware/SecurityHeadersMiddleware.php`, line 36

**Issue**: The Content-Security-Policy header includes `script-src 'self' 'unsafe-inline' 'unsafe-eval'`. While `'unsafe-inline'` is often necessary for CakePHP's inline scripts, `'unsafe-eval'` should not be needed and significantly weakens XSS protection. `'unsafe-eval'` allows `eval()`, `Function()`, and similar dynamic code execution in JavaScript, which is a common XSS exploitation vector.

**Fix**: Remove `'unsafe-eval'` from the CSP. If the Chart.js CDN or Angular requires it, use a nonce-based approach instead.

---

### VULN-213: No File Size Limit on CSV Import Upload
**Severity**: LOW
**File**: `src/src/Controller/Api/V2/MonitorsController.php`, lines 547-570

**Issue**: The `import()` and `importCompetitor()` methods accept file uploads (`csv_file` / `file`) but do not enforce a file size limit. A malicious user could upload a very large CSV file (e.g., 500MB) to cause memory exhaustion or disk space issues.

**Fix**: Add a file size check:
```php
$file = $this->request->getUploadedFile('csv_file');
if ($file && $file->getSize() > 5 * 1024 * 1024) { // 5MB limit
    $this->error('CSV file is too large (max 5MB)', 400);
    return;
}
```

---

### VULN-214: Error Messages Expose Internal Exception Details
**Severity**: LOW
**Files**: Multiple controllers

**Issue**: Several controllers pass `$e->getMessage()` directly to error responses:
- `SettingsController::save()` line 66: `'Failed to save settings: ' . $e->getMessage()`
- `BillingController::checkout()` line 68: `'Failed to create checkout session: ' . $e->getMessage()`
- `IntegrationsController::test()` line 214: `'Connection test failed: ' . $e->getMessage()`
- `ScheduledReportsController::preview()` line 192: `'Failed to generate preview: ' . $e->getMessage()`

Exception messages can leak internal paths, database details, or third-party API error details to the client.

**Fix**: Log the full exception internally and return a generic error message to the client, or sanitize the exception message to remove internal details.

---

## Informational Findings

### VULN-215: API Key Returned in Full on Creation
**Severity**: INFO
**File**: `src/src/Controller/Api/V2/ApiKeysController.php`, line 65

**Issue**: When creating an API key (line 65: `$key->set('key', bin2hex(random_bytes(32)))`), the full plaintext key is set on the entity and returned in the response (line 73). The `key_hash` field is hidden, but the plaintext `key` field is not (it is set directly, not via a table column). This is actually correct behavior (the key must be shown once on creation), but the key should be clearly marked as "show once" in the API response to prevent accidental logging.

**Fix**: No code change needed, but document in the API response that the key will only be shown once.

---

### VULN-216: saveMultiple Method Does Not Exist
**Severity**: INFO
**File**: `src/src/Controller/Api/V2/SettingsController.php`, line 62

**Issue**: `$service->saveMultiple($data, $this->currentOrgId)` is called but `saveMultiple()` is not defined in `SettingService`. This will cause a PHP fatal error when any user tries to save settings via the API. While this is a bug rather than a security vulnerability per se, it means the settings save functionality is completely broken, which prevents security-critical configuration changes.

**Fix**: Implement `saveMultiple()` in `SettingService`, or replace the call with iterative `set()` calls.

---

## Verification of Previously Fixed Items

| Fix | Status | Notes |
|-----|--------|-------|
| VULN-01: SuperAdmin AppController throws ForbiddenException | VERIFIED | `SuperAdmin/AppController.php` line 28-29: throws `ForbiddenException` (halts execution) |
| VULN-02: JWT secret fallback | VERIFIED | `JwtService.php` lines 31-38: throws RuntimeException if empty, blocks weak secrets in production |
| VULN-05: Bulk operations include organization_id | VERIFIED | `MonitorsController.php` lines 513, 521, 527: all include `'organization_id' => $this->currentOrgId` |
| VULN-07: Settings mask passwords | PARTIALLY | `SettingsController::index()` masks values, but `save()` may overwrite them with the mask (see VULN-203) |
| VULN-15: WebhookEndpoints index role check | VERIFIED | Line 22: `requireRole(['owner', 'admin'])` |
| VULN-16: CSP header | PARTIALLY | CSP added but includes `'unsafe-eval'` (see VULN-212) |

---

## Good Practices Observed (no change from first audit)

- Bcrypt password hashing via entity setter
- JWT HS256 with explicit algorithm (no algorithm confusion)
- Refresh tokens stored as SHA-256 hashes, plain token never persisted
- CSPRNG (`random_bytes`) for all tokens (refresh, API key, acknowledge, reset, email verification)
- CSRF properly configured with skip for API routes
- User entity `$_hidden` covers password and 2FA secrets
- User entity `$_accessible` blocks `role`, `is_super_admin`, and other privilege fields
- Stripe webhook uses `Webhook::constructEvent()` with proper signature verification
- OAuth state parameter verified with `hash_equals()`
- CORS restricted to allowlisted origins
- TenantScope behavior properly applied to most table models
- Registration returns generic error message to prevent user enumeration
