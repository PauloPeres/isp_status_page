# SaaS Settings Restructuring Plan

> Created: 2026-03-28

## Rationale

In a SaaS model, the platform operator manages infrastructure. Customers should NOT need to configure SMTP servers, FTP backups, or system-level settings. These are platform responsibilities. The customer Settings page should be simple and focused on their own preferences.

## Architecture Decision: System vs Organization Settings

### SYSTEM-LEVEL (Super Admin only)

These are managed by the platform operator in the Super Admin panel:

| Setting | Reason |
|---------|--------|
| **SMTP/Email** (host, port, user, password, encryption) | Platform provides email delivery for all customers. Customers just receive alerts — they don't configure mail servers. |
| **FTP/SFTP Backup** (host, port, credentials, path) | Platform-level database backups. Customers don't need to know about this. |
| **Check scheduling defaults** | Platform controls minimum check intervals (enforced by plan tier). |
| **Check regions** | Platform manages where checks run from. |
| **System name, default language** | Platform-wide defaults. |

### ORGANIZATION-LEVEL (Customer settings)

Each customer configures their own:

| Setting | Reason |
|---------|--------|
| **Notification channels** (Slack webhook URL, Discord webhook, Telegram bot token, custom webhook URL) | Each customer has their own Slack workspace, Discord server, etc. |
| **Alert rules** | Per-customer: which monitors trigger which alerts to which channels. |
| **IXC Integration** (API URL, credentials) | Customer-specific ERP monitoring. IXC is an ISP ERP system — each customer has their own IXC instance with custom API calls to monitor service status. |
| **Zabbix Integration** (API URL, credentials) | Customer-specific infrastructure monitoring. Collects host statuses and alerts from the customer's Zabbix server to display on their status page. |
| **REST API integrations** | Customer-specific external API monitoring. |
| **Status page branding** (colors, logo, custom CSS) | Per-customer public status page. |
| **API keys** | Per-customer API access. |
| **Team/users** | Per-customer team management. |
| **Language/timezone** | Per-user/org preference. |
| **Organization name, slug** | Per-customer identity. |

### Integration Clarification

**IXC Integration:** IXC Provedor is an ERP system used by Brazilian ISPs. The integration makes custom API calls to monitor:
- Service availability (is the IXC system responding?)
- Customer service status (are ISP services operational?)
- Equipment/ONU status (are network devices online?)

Each ISP customer has their own IXC instance, so the API URL, credentials, and monitored resources are organization-level.

**Zabbix Integration:** Zabbix is an infrastructure monitoring tool. The integration:
- Connects to the customer's Zabbix server via JSON-RPC API
- Collects host availability statuses
- Collects trigger/alert states
- Displays these on the customer's status page

Each customer has their own Zabbix server, so this is organization-level.

---

## Tasks

### TASK-SET-001: Move SMTP Settings to Super Admin
- **Status:** COMPLETED
- **Description:** Remove the "Email" tab from the customer Settings page. Move SMTP configuration (host, port, username, password, encryption, sender name, sender email) to a new "Email" section in the Super Admin panel. The EmailService reads from system settings (SettingService without org override). All customer emails go through the platform's SMTP.
- **Files to modify:**
  - `src/templates/Settings/index.php` — remove Email tab
  - `src/src/Controller/SuperAdmin/SettingsController.php` — create new controller for system settings
  - `src/templates/SuperAdmin/Settings/index.php` — create system settings page with Email tab
  - `src/src/Service/SettingService.php` — ensure system settings are read without org scope
  - `src/templates/element/super_admin/sidebar.php` — add "Settings" link
- **Result:** Email tab removed from customer settings. SMTP settings now managed via Super Admin at /super-admin/settings?tab=email. Test email functionality moved to Super Admin. Settings link added to super admin sidebar.

### TASK-SET-002: Move FTP/Backup Settings to Super Admin
- **Status:** COMPLETED
- **Description:** Remove the "Backup" tab from customer Settings. Move FTP/SFTP configuration to Super Admin system settings. BackupCommand reads from system settings.
- **Files to modify:**
  - `src/templates/Settings/index.php` — remove Backup tab
  - `src/src/Controller/SuperAdmin/SettingsController.php` — add Backup section
  - `src/src/Service/BackupUploaderService.php` — ensure reads system settings
- **Result:** Backup tab removed from customer settings. FTP/SFTP configuration now managed via Super Admin at /super-admin/settings?tab=backup. Test FTP connection functionality moved to Super Admin. testFtpConnection method removed from customer SettingsController.

### TASK-SET-003: Simplify Customer Settings Page
- **Status:** COMPLETED
- **Description:** The customer Settings page should only have:
  - **General** tab: Organization name, slug, logo, language, timezone
  - **Notifications** tab: Enable/disable alert types, default cooldown
  - **Channels** tab: Slack/Discord/Telegram/Webhook configuration (already implemented)
  Remove: Email tab, Backup tab, Monitoring tab (check intervals controlled by plan).
- **Files to modify:**
  - `src/templates/Settings/index.php` — restructure tabs
  - `src/src/Controller/SettingsController.php` — update to save org-level settings
- **Result:** Restructured Settings page to 3 tabs (General, Notifications, Channels). Removed Email, Backup, and Monitoring tabs. Updated controller index() to only load org-level settings. Added ALLOWED_ORG_KEYS whitelist to save() action to reject system-level keys. Updated getDefaultSettings() to only include General and Notifications defaults. testFtpConnection() now redirects non-super-admins. Email channel card updated to say "Managed by platform".

### TASK-SET-004: Create Super Admin System Settings Controller
- **Status:** COMPLETED
- **Description:** New controller at `/super-admin/settings` with tabs:
  - **Email** — SMTP host, port, username, password, encryption, sender name/email. Test email button.
  - **Backup** — FTP/SFTP host, port, credentials, path, enabled toggle. Test connection button.
  - **System** — Default language, site name, system-wide announcement.
  - **Limits** — Default check intervals, global rate limits.
- **Files to create:**
  - `src/src/Controller/SuperAdmin/SettingsController.php`
  - `src/templates/SuperAdmin/Settings/index.php`
- **Result:** Created SuperAdmin SettingsController with index, save, testEmail, testFtp actions. Created template with 3 tabs (Email, Backup, System). Added routes for /super-admin/settings, /super-admin/settings/save, /super-admin/settings/test-email, /super-admin/settings/test-ftp. Added Settings link to super admin sidebar.

### TASK-SET-005: Update SettingService for System vs Org Settings
- **Status:** COMPLETED
- **Description:** SettingService needs clear separation:
  - `getSystem($key)` — reads from settings table without org scope (for SMTP, FTP, system defaults)
  - `getOrg($key)` — reads from org's settings JSON (for org preferences)
  - `get($key)` — reads org first, falls back to system (backward compatible)
  Ensure SMTP settings always come from system level regardless of TenantContext.
- **Files to modify:**
  - `src/src/Service/SettingService.php`
- **Result:** Added getSystem() method that queries settings table with skipTenantScope option. Added getOrg() method that reads from Organization.settings JSON via TenantContext. Updated get() to cascade: org -> system -> default, with system-only enforcement for smtp_*, backup_ftp_*, and default_language keys. Added SYSTEM_ONLY_PREFIXES, SYSTEM_ONLY_KEYS, and ORG_OVERRIDABLE_KEYS constants. Deprecated getOrgSetting() in favor of get() which now handles cascading natively. site_name is org-overridable (org can override the system default).

---

## Execution Plan

All 5 tasks can be done by 2 agents:
- **Agent A:** TASK-SET-004 + TASK-SET-001 + TASK-SET-002 (Super Admin settings + move SMTP/FTP)
- **Agent B:** TASK-SET-003 + TASK-SET-005 (Simplify customer settings + SettingService refactor)
