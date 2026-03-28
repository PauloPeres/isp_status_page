# QA Test Checklist -- ISP Status Page SaaS

> Run date: ___________
> Tester: ___________
> Environment: ___________
> Browser(s): ___________
> Mobile device: ___________

## How to Run
- Login: admin / admin123
- URL: http://localhost:8765
- Super Admin: same credentials (if user has is_super_admin flag)

---

## 1. Authentication

### 1.1 Login
- [ ] GET /users/login -- page loads, shows login form
- [ ] POST /users/login -- valid credentials redirect to /dashboard
- [ ] POST /users/login -- invalid credentials show error message
- [ ] POST /users/login -- empty fields show validation errors
- [ ] "Register" link visible and navigates to /register
- [ ] "Sign in with Google" button visible (OAuth)
- [ ] "Sign in with GitHub" button visible (OAuth)
- [ ] "Forgot Password?" link visible and navigates to /users/forgot-password
- [ ] Mobile: login form is responsive and usable on small screens

### 1.2 Registration
- [ ] GET /register -- page loads, shows registration form
- [ ] POST /register -- valid data creates user + organization, redirects to verify email
- [ ] POST /register -- duplicate email shows validation error
- [ ] POST /register -- password mismatch shows validation error
- [ ] POST /register -- missing required fields show validation errors
- [ ] POST /register -- organization name is required
- [ ] Mobile: registration form is responsive

### 1.3 Email Verification
- [ ] GET /verify-email/{token} -- valid token verifies email and auto-logs in
- [ ] GET /verify-email/{token} -- expired/invalid token shows error message
- [ ] GET /verify-email/{token} -- already verified token shows appropriate message

### 1.4 Forgot Password
- [ ] GET /users/forgot-password -- page loads, shows email input form
- [ ] POST /users/forgot-password -- valid email sends reset link, shows success message
- [ ] POST /users/forgot-password -- non-existent email still shows success (no enumeration)
- [ ] POST /users/forgot-password -- empty email shows validation error

### 1.5 Reset Password
- [ ] GET /users/reset-password/{token} -- valid token shows new password form
- [ ] GET /users/reset-password/{token} -- expired/invalid token shows error
- [ ] POST /users/reset-password/{token} -- sets new password and redirects to login
- [ ] POST /users/reset-password/{token} -- password mismatch shows validation error

### 1.6 Change Password (authenticated)
- [ ] GET /users/change-password -- page loads with current/new/confirm fields
- [ ] POST /users/change-password -- correct current password + valid new password succeeds
- [ ] POST /users/change-password -- wrong current password shows error
- [ ] POST /users/change-password -- new password mismatch shows error

### 1.7 OAuth
- [ ] GET /auth/google/redirect -- redirects to Google OAuth consent screen
- [ ] GET /auth/google/callback -- successful Google auth creates/links account and redirects
- [ ] GET /auth/github/redirect -- redirects to GitHub OAuth consent screen
- [ ] GET /auth/github/callback -- successful GitHub auth creates/links account and redirects
- [ ] OAuth with existing email links account rather than creating duplicate

### 1.8 Logout
- [ ] GET /users/logout -- logs out and redirects to /users/login
- [ ] After logout, accessing protected routes redirects to login

---

## 2. Onboarding

### 2.1 Step 1
- [ ] GET /onboarding/step1 -- page loads, shows first onboarding step
- [ ] POST /onboarding/step1 -- saves data, redirects to step2
- [ ] Mobile: step 1 form is responsive

### 2.2 Step 2
- [ ] GET /onboarding/step2 -- page loads, shows second onboarding step
- [ ] POST /onboarding/step2 -- saves data, redirects to step3
- [ ] Skipping step1 redirects back to step1

### 2.3 Step 3
- [ ] GET /onboarding/step3 -- page loads, shows third onboarding step
- [ ] POST /onboarding/step3 -- saves data, redirects to /onboarding/complete

### 2.4 Complete
- [ ] GET /onboarding/complete -- shows completion message with link to dashboard

---

## 3. Dashboard

- [ ] GET /dashboard -- page loads with admin layout and sidebar
- [ ] Summary KPI cards display: Total monitors, Up (green), Down (red), Degraded (yellow), Unknown (grey)
- [ ] KPI card counts match actual monitor data
- [ ] Active incidents count with severity badges shown
- [ ] Uptime line chart renders (Chart.js, last 24h uptime % per monitor)
- [ ] Response time bar chart renders (Chart.js, avg response time per monitor)
- [ ] Recent checks table populated with last 20 checks (monitor name, status, time)
- [ ] Recent alerts table populated with last 10 alert_logs entries
- [ ] Empty state: dashboard handles zero monitors gracefully
- [ ] Mobile: dashboard cards stack vertically, charts resize

---

## 4. Monitors

### 4.1 List
- [ ] GET /monitors -- list page loads with all monitors
- [ ] Monitors show name, type, status, last check time
- [ ] Status filter dropdown works (Up/Down/Degraded/Unknown)
- [ ] Type filter dropdown works (HTTP/Ping/Port/Heartbeat/SSL/Keyword/IXC/Zabbix/API)
- [ ] Active/inactive filter works
- [ ] Search by name/description works
- [ ] Pagination works when many monitors exist
- [ ] Empty state displays when no monitors match filters
- [ ] "Add Monitor" button visible and navigates to /monitors/add
- [ ] Mobile: monitor list is responsive, cards/table adapts

### 4.2 Create
- [ ] GET /monitors/add -- form loads with type selector
- [ ] HTTP monitor type: URL, expected status code, timeout fields show
- [ ] Ping monitor type: hostname/IP field shows
- [ ] Port monitor type: hostname, port number fields show
- [ ] Heartbeat monitor type: shows generated token/URL
- [ ] SSL monitor type: domain field shows
- [ ] Keyword monitor type: URL and keyword fields show
- [ ] IXC Service monitor type: integration selector, service ID fields show
- [ ] IXC Equipment monitor type: integration selector, equipment ID fields show
- [ ] Zabbix Host monitor type: integration selector, host ID fields show
- [ ] Zabbix Trigger monitor type: integration selector, trigger ID fields show
- [ ] API monitor type: URL, method, expected status, json_path, expected_value fields show
- [ ] Interval field accepts valid values
- [ ] POST /monitors/add -- valid data creates monitor and redirects to view
- [ ] POST /monitors/add -- missing required fields show validation errors
- [ ] Tooltips display on all form fields
- [ ] Mobile: add form is responsive

### 4.3 View
- [ ] GET /monitors/view/{id} -- detail page loads with monitor info
- [ ] Current status displayed prominently (with color)
- [ ] Uptime percentage shown
- [ ] Average response time shown
- [ ] Check history chart rendered
- [ ] Recent checks table populated
- [ ] Edit button navigates to /monitors/edit/{id}
- [ ] Delete button shows confirmation dialog
- [ ] Toggle (pause/resume) button works
- [ ] Test Connection button works (AJAX, shows result)
- [ ] Mobile: detail page is responsive

### 4.4 Edit
- [ ] GET /monitors/edit/{id} -- form loads pre-filled with current data
- [ ] All fields editable and retain values on reload
- [ ] POST /monitors/edit/{id} -- saves changes, redirects to view
- [ ] POST /monitors/edit/{id} -- validation errors shown for invalid data
- [ ] Cancel button returns to monitor view

### 4.5 Delete
- [ ] POST /monitors/delete/{id} -- deletes monitor after confirmation
- [ ] Redirects to /monitors after delete
- [ ] Cannot delete non-existent monitor (404 or error)

### 4.6 Toggle (Pause/Resume)
- [ ] POST /monitors/toggle/{id} -- toggles active state
- [ ] Paused monitor shows paused indicator in list
- [ ] Paused monitor is skipped during check runs

### 4.7 Test Connection
- [ ] POST /monitors/test-connection/{id} -- runs check immediately, returns result via AJAX
- [ ] Success result shown in green
- [ ] Failure result shown in red with error details

---

## 5. Incidents

### 5.1 List
- [ ] GET /incidents -- list page loads with all incidents
- [ ] Incidents show monitor name, status, severity, created time
- [ ] Active incidents highlighted differently from resolved
- [ ] Acknowledged badge shown for acknowledged incidents
- [ ] Filter by status works (open/resolved/acknowledged)
- [ ] Pagination works
- [ ] Empty state when no incidents
- [ ] Mobile: incident list is responsive

### 5.2 View
- [ ] GET /incidents/view/{id} -- detail page loads
- [ ] Incident timeline displayed (created, status changes, acknowledged, resolved)
- [ ] Associated monitor link works
- [ ] Acknowledgement badge shown if acknowledged
- [ ] "Acknowledge" button visible for unacknowledged open incidents (admin)
- [ ] "Resolve" button visible for open incidents
- [ ] Mobile: detail page is responsive

### 5.3 Edit
- [ ] GET /incidents/edit/{id} -- form loads
- [ ] POST /incidents/edit/{id} -- saves changes

### 5.4 Resolve
- [ ] POST /incidents/resolve/{id} -- resolves incident
- [ ] Resolved incident shows resolved timestamp
- [ ] Already-resolved incident cannot be resolved again

### 5.5 Acknowledge (Admin)
- [ ] POST /incidents/{id}/acknowledge-admin -- acknowledges incident
- [ ] Sets acknowledged_by, acknowledged_at, acknowledged_via = 'web'
- [ ] Already acknowledged incident shows error/message
- [ ] Notifies other recipients that incident was acknowledged

### 5.6 Acknowledge (Public Token)
- [ ] GET /incidents/acknowledge/{id}/{token} -- valid token acknowledges incident (no auth required)
- [ ] Invalid/expired token (>24h) shows error
- [ ] Already acknowledged incident shows appropriate message

---

## 6. Checks

### 6.1 List
- [ ] GET /checks -- list page loads with all checks
- [ ] Checks show monitor name, status, response time, timestamp
- [ ] Filter by status works
- [ ] Filter by monitor works
- [ ] Pagination works
- [ ] Empty state when no checks
- [ ] Mobile: checks list is responsive

### 6.2 View
- [ ] GET /checks/view/{id} -- detail page loads
- [ ] Check details displayed: status, response time, response code, error message
- [ ] Associated monitor link works
- [ ] Mobile: detail page is responsive

---

## 7. Integrations

### 7.1 List
- [ ] GET /integrations -- list page loads
- [ ] Integrations show name, type (with badge), status, last test time
- [ ] "Add Integration" button visible
- [ ] Empty state when no integrations
- [ ] Mobile: list is responsive

### 7.2 Create
- [ ] GET /integrations/add -- form loads
- [ ] Type selector shows options (IXC, Zabbix, REST API)
- [ ] Fields change dynamically based on type selection
- [ ] POST /integrations/add -- valid data creates integration, redirects
- [ ] POST /integrations/add -- validation errors for missing fields
- [ ] Tooltips on all form fields
- [ ] Mobile: form is responsive

### 7.3 View
- [ ] GET /integrations/view/{id} -- detail page loads
- [ ] Configuration details displayed (credentials masked)
- [ ] Last test result shown
- [ ] Edit and Delete buttons visible
- [ ] "Test Connection" button visible

### 7.4 Edit
- [ ] GET /integrations/edit/{id} -- form loads pre-filled
- [ ] POST /integrations/edit/{id} -- saves changes, redirects
- [ ] Validation errors shown for invalid data

### 7.5 Delete
- [ ] POST /integrations/delete/{id} -- deletes integration after confirmation
- [ ] Redirects to /integrations after delete

### 7.6 Test Connection
- [ ] POST /integrations/test/{id} -- AJAX call tests connection
- [ ] Success result displayed in green
- [ ] Failure result displayed in red with error details
- [ ] Loading indicator shown during test

---

## 8. Status Pages

### 8.1 List
- [ ] GET /status-pages -- list page loads
- [ ] Status pages show name, slug, monitor count, public URL
- [ ] "Add Status Page" button visible
- [ ] Empty state when no status pages
- [ ] Mobile: list is responsive

### 8.2 Create
- [ ] GET /status-pages/add -- form loads
- [ ] Name, slug, description, monitor selector fields shown
- [ ] Slug auto-generates from name (or manual entry)
- [ ] POST /status-pages/add -- valid data creates status page, redirects
- [ ] POST /status-pages/add -- duplicate slug shows validation error
- [ ] Mobile: form is responsive

### 8.3 Edit
- [ ] GET /status-pages/edit/{id} -- form loads pre-filled
- [ ] POST /status-pages/edit/{id} -- saves changes, redirects
- [ ] Validation errors shown for invalid data

### 8.4 View (Admin)
- [ ] GET /status-pages/view/{id} -- admin detail page loads
- [ ] Shows configuration, assigned monitors, public URL link

### 8.5 Delete
- [ ] POST /status-pages/delete/{id} -- deletes after confirmation
- [ ] Redirects to /status-pages after delete

### 8.6 Public Show
- [ ] GET /s/{slug} -- public status page loads (no auth required)
- [ ] Public layout used (not admin)
- [ ] All assigned monitors displayed with current status
- [ ] Status colors correct: green (up), red (down), yellow (degraded), grey (unknown)
- [ ] Active incidents shown
- [ ] Active maintenance windows shown
- [ ] Page loads fast (<2s)
- [ ] Mobile: public status page is fully responsive
- [ ] Non-existent slug returns 404

---

## 9. Maintenance Windows

### 9.1 List
- [ ] GET /maintenance-windows -- list page loads
- [ ] Shows name, start/end time, affected monitors, status (scheduled/active/completed)
- [ ] "Add Maintenance Window" button visible
- [ ] Empty state when no windows
- [ ] Mobile: list is responsive

### 9.2 Create
- [ ] GET /maintenance-windows/add -- form loads
- [ ] Name, description, start time, end time, monitor selector fields shown
- [ ] Date/time pickers work
- [ ] POST /maintenance-windows/add -- valid data creates window, redirects
- [ ] POST /maintenance-windows/add -- end before start shows validation error
- [ ] Mobile: form is responsive

### 9.3 Edit
- [ ] GET /maintenance-windows/edit/{id} -- form loads pre-filled
- [ ] POST /maintenance-windows/edit/{id} -- saves changes, redirects
- [ ] Validation errors shown for invalid data

### 9.4 Delete
- [ ] POST /maintenance-windows/delete/{id} -- deletes after confirmation
- [ ] Redirects to /maintenance-windows after delete

---

## 10. Subscribers

### 10.1 Admin List
- [ ] GET /subscribers -- admin list loads
- [ ] Shows email, status (verified/unverified), subscribed monitors, created date
- [ ] Toggle active/inactive button works
- [ ] Delete button works with confirmation
- [ ] Resend verification button works for unverified subscribers
- [ ] Pagination works
- [ ] Empty state when no subscribers
- [ ] Mobile: list is responsive

### 10.2 Admin View
- [ ] GET /subscribers/view/{id} -- detail page loads
- [ ] Shows email, status, subscriptions, notification history

### 10.3 Admin Toggle
- [ ] POST /subscribers/toggle/{id} -- toggles subscriber active state

### 10.4 Admin Delete
- [ ] POST /subscribers/delete/{id} -- deletes subscriber after confirmation

### 10.5 Admin Resend Verification
- [ ] POST /subscribers/resend-verification/{id} -- resends verification email

### 10.6 Public Subscribe
- [ ] GET /subscribers/subscribe -- public form loads (no auth required)
- [ ] POST /subscribers/subscribe -- valid email creates subscriber, sends verification
- [ ] POST /subscribers/subscribe -- duplicate email shows appropriate message
- [ ] POST /subscribers/subscribe -- invalid email shows validation error
- [ ] Mobile: subscribe form is responsive

### 10.7 Public Verify
- [ ] GET /subscribers/verify/{token} -- valid token verifies subscriber
- [ ] Invalid/expired token shows error

### 10.8 Public Unsubscribe
- [ ] GET /subscribers/unsubscribe/{token} -- valid token unsubscribes
- [ ] Invalid token shows error

---

## 11. Email Logs

### 11.1 List
- [ ] GET /email-logs -- list page loads
- [ ] Shows recipient, subject, status (sent/failed), sent date
- [ ] Pagination works
- [ ] Empty state when no logs
- [ ] Mobile: list is responsive

### 11.2 View
- [ ] GET /email-logs/view/{id} -- detail page loads
- [ ] Shows full email details: recipient, subject, body preview, status, error message
- [ ] Resend button visible for failed emails

### 11.3 Resend
- [ ] POST /email-logs/resend/{id} -- resends email, shows success/failure message

---

## 12. Users

### 12.1 List
- [ ] GET /users -- list page loads (requires owner/admin role)
- [ ] Shows name, email, role, status, last login
- [ ] "Add User" button visible
- [ ] Pagination works
- [ ] Empty state when only self exists
- [ ] Mobile: list is responsive

### 12.2 Create
- [ ] GET /users/add -- form loads
- [ ] Name, email, password, role selector fields shown
- [ ] Role options: admin, member, viewer
- [ ] POST /users/add -- valid data creates user, redirects
- [ ] POST /users/add -- duplicate email shows validation error
- [ ] POST /users/add -- password requirements enforced
- [ ] Mobile: form is responsive

### 12.3 View
- [ ] GET /users/view/{id} -- detail page loads
- [ ] Shows user info, role, last login, associated monitors

### 12.4 Edit
- [ ] GET /users/edit/{id} -- form loads pre-filled
- [ ] POST /users/edit/{id} -- saves changes, redirects
- [ ] Cannot change own role to lower (self-protection)
- [ ] Validation errors shown for invalid data

### 12.5 Delete
- [ ] POST /users/delete/{id} -- deletes user after confirmation
- [ ] Cannot delete self
- [ ] Redirects to /users after delete

---

## 13. Invitations

### 13.1 List
- [ ] GET /invitations -- list page loads (requires owner/admin role)
- [ ] Shows invitee email, role, status (pending/accepted/expired), sent date
- [ ] "Send Invitation" button visible
- [ ] Revoke button visible for pending invitations
- [ ] Empty state when no invitations
- [ ] Mobile: list is responsive

### 13.2 Send
- [ ] GET /invitations/send -- form loads
- [ ] POST /invitations/send -- valid email + role sends invitation email
- [ ] POST /invitations/send -- duplicate pending invitation shows error
- [ ] POST /invitations/send -- already-registered email shows appropriate message
- [ ] Mobile: form is responsive

### 13.3 Accept
- [ ] GET /invite/{token} -- valid token shows acceptance page/auto-accepts
- [ ] Invalid/expired token shows error
- [ ] Already-accepted invitation shows message

### 13.4 Revoke
- [ ] POST /invitations/revoke/{id} -- revokes pending invitation
- [ ] Cannot revoke already-accepted invitation

---

## 14. API Keys

### 14.1 List
- [ ] GET /api-keys -- list page loads (requires owner/admin role)
- [ ] Shows key name, prefix (masked), created date, last used
- [ ] "Create API Key" button visible
- [ ] Empty state when no keys
- [ ] Mobile: list is responsive

### 14.2 Create
- [ ] GET /api-keys/add -- form loads
- [ ] POST /api-keys/add -- creates key, shows full key ONCE (not stored in plain text)
- [ ] User prompted to copy key before dismissing
- [ ] POST /api-keys/add -- missing name shows validation error

### 14.3 Delete
- [ ] POST /api-keys/delete/{id} -- deletes key after confirmation
- [ ] Redirects to /api-keys after delete

---

## 15. Settings

### 15.1 General Tab
- [ ] GET /settings -- page loads with General tab active
- [ ] Site name field editable
- [ ] Language selector works (pt_BR, en, es)
- [ ] Timezone selector works
- [ ] POST /settings/save -- saves general settings, shows success flash
- [ ] Validation errors shown for invalid values

### 15.2 Email Tab
- [ ] Email tab loads with SMTP configuration fields
- [ ] SMTP Host, Port, Username, Password, Encryption, From Address fields shown
- [ ] "Test Email" button sends test email (AJAX)
- [ ] POST /settings/save -- saves email settings
- [ ] Test email success shows green confirmation
- [ ] Test email failure shows red error with details

### 15.3 Monitoring Tab
- [ ] Monitoring tab loads with check interval, timeout settings
- [ ] Default check interval field editable
- [ ] Default timeout field editable
- [ ] POST /settings/save -- saves monitoring settings

### 15.4 Notifications Tab
- [ ] Notifications tab loads with alert preferences
- [ ] Alert email enabled toggle
- [ ] POST /settings/save -- saves notification settings

### 15.5 Backup Tab
- [ ] Backup tab loads with FTP/SFTP configuration fields
- [ ] Backup enabled toggle
- [ ] FTP type selector (FTP/SFTP)
- [ ] Host, Port, Username, Password, Path, Passive mode fields shown
- [ ] "Test FTP Connection" button works (AJAX)
- [ ] POST /settings/save -- saves backup settings
- [ ] Test FTP success shows green confirmation
- [ ] Test FTP failure shows red error with details

### 15.6 Settings Reset
- [ ] POST /settings/reset -- resets settings to defaults after confirmation
- [ ] Redirects to /settings with success message

### 15.7 Cross-tab
- [ ] Switching between tabs preserves unsaved changes warning (or does not)
- [ ] All tabs accessible and load without errors
- [ ] Mobile: settings tabs switch to dropdown/accordion on small screens

---

## 16. Billing

### 16.1 Plans
- [ ] GET /billing or GET /billing/plans -- plans page loads (requires owner role)
- [ ] Free, Pro, Business plan cards displayed with features and pricing
- [ ] Current plan highlighted
- [ ] "Upgrade" / "Downgrade" / "Current" buttons shown appropriately
- [ ] Mobile: plan cards stack vertically

### 16.2 Checkout
- [ ] GET /billing/checkout/{planSlug} -- redirects to Stripe Checkout session
- [ ] Invalid plan slug shows error
- [ ] Already on selected plan shows appropriate message

### 16.3 Portal
- [ ] GET /billing/portal -- redirects to Stripe Customer Portal
- [ ] User can manage subscription, payment methods, invoices in portal

### 16.4 Success
- [ ] GET /billing/success -- success page loads after Stripe checkout
- [ ] Shows confirmation message and link to dashboard

### 16.5 Cancel
- [ ] GET /billing/cancel -- cancel page loads when user cancels checkout
- [ ] Shows message and link to return to plans

---

## 17. Organization Switcher

### 17.1 Select
- [ ] GET /organizations/select -- page loads with list of user's organizations
- [ ] Each org shows name, role, member count
- [ ] Click on org switches context

### 17.2 Switch
- [ ] POST /organizations/switch/{orgId} -- switches active organization
- [ ] Redirects to /dashboard after switch
- [ ] All data scoped to new organization after switch
- [ ] Cannot switch to org user does not belong to (403)

---

## 18. Public Status Page

### 18.1 Default Status
- [ ] GET /status -- public status page loads (no auth required)
- [ ] Public layout used
- [ ] All monitors displayed with current status
- [ ] Overall system status shown (all up / partial outage / major outage)
- [ ] Active incidents listed
- [ ] Subscribe link/form visible
- [ ] Mobile: fully responsive

### 18.2 Status History
- [ ] GET /status/history -- history page loads (no auth required)
- [ ] Historical uptime data displayed (daily/weekly/monthly)
- [ ] Past incidents listed with timeline
- [ ] Mobile: history page is responsive

---

## 19. Badges

### 19.1 Uptime Badge
- [ ] GET /badges/{token}/uptime.svg -- returns SVG badge (no auth required)
- [ ] Badge shows uptime percentage
- [ ] Valid token returns 200 with SVG content-type
- [ ] Invalid token returns error/default badge

### 19.2 Status Badge
- [ ] GET /badges/{token}/status.svg -- returns SVG badge
- [ ] Badge shows current status (up/down/degraded)
- [ ] Color matches status (green/red/yellow)

### 19.3 Response Time Badge
- [ ] GET /badges/{token}/response-time.svg -- returns SVG badge
- [ ] Badge shows average response time in ms

---

## 20. Heartbeat

- [ ] GET /heartbeat/{token} -- valid token records heartbeat ping, returns 200 JSON
- [ ] Invalid token returns 404
- [ ] Heartbeat updates monitor's last check timestamp
- [ ] Missing heartbeats beyond interval trigger incident

---

## 21. API Documentation

- [ ] GET /api/docs -- API documentation page loads
- [ ] All API v1 endpoints listed with methods, parameters, examples
- [ ] Authentication section explains API key usage
- [ ] Mobile: docs page is responsive

---

## 22. REST API v1

> All API requests require `Authorization: Bearer {api_key}` header.
> All responses are JSON. Base URL: http://localhost:8765/api/v1

### 22.1 Authentication
- [ ] Request without API key returns 401
- [ ] Request with invalid API key returns 401
- [ ] Request with valid API key returns expected data
- [ ] API key is scoped to the correct organization

### 22.2 Monitors
- [ ] GET /api/v1/monitors -- returns list of monitors (JSON)
- [ ] GET /api/v1/monitors -- supports pagination parameters
- [ ] POST /api/v1/monitors -- creates a new monitor (JSON body)
- [ ] POST /api/v1/monitors -- validation errors return 422 with details
- [ ] GET /api/v1/monitors/{id} -- returns single monitor
- [ ] GET /api/v1/monitors/{id} -- non-existent ID returns 404
- [ ] PUT /api/v1/monitors/{id} -- updates monitor
- [ ] PUT /api/v1/monitors/{id} -- validation errors return 422
- [ ] DELETE /api/v1/monitors/{id} -- deletes monitor, returns 204 or success JSON
- [ ] DELETE /api/v1/monitors/{id} -- non-existent ID returns 404
- [ ] GET /api/v1/monitors/{id}/checks -- returns checks for monitor
- [ ] POST /api/v1/monitors/{id}/pause -- pauses monitor
- [ ] POST /api/v1/monitors/{id}/pause -- already paused returns appropriate response
- [ ] POST /api/v1/monitors/{id}/resume -- resumes monitor
- [ ] POST /api/v1/monitors/{id}/resume -- already active returns appropriate response

### 22.3 Incidents
- [ ] GET /api/v1/incidents -- returns list of incidents
- [ ] POST /api/v1/incidents -- creates a new incident
- [ ] GET /api/v1/incidents/{id} -- returns single incident
- [ ] PUT /api/v1/incidents/{id} -- updates incident

### 22.4 Checks
- [ ] GET /api/v1/checks -- returns list of checks
- [ ] GET /api/v1/checks/{id} -- returns single check

### 22.5 Alert Rules
- [ ] GET /api/v1/alert-rules -- returns list of alert rules
- [ ] POST /api/v1/alert-rules -- creates a new alert rule
- [ ] GET /api/v1/alert-rules/{id} -- returns single alert rule
- [ ] PUT /api/v1/alert-rules/{id} -- updates alert rule
- [ ] DELETE /api/v1/alert-rules/{id} -- deletes alert rule

### 22.6 API Error Handling
- [ ] Malformed JSON body returns 400
- [ ] Accessing another organization's resource returns 404 (not 403, to avoid enumeration)
- [ ] Rate limiting returns 429 (if implemented)

---

## 23. Webhooks

### 23.1 Stripe Webhook
- [ ] POST /webhooks/stripe -- valid Stripe signature processes event
- [ ] POST /webhooks/stripe -- invalid signature returns 400
- [ ] Handles checkout.session.completed event (activates subscription)
- [ ] Handles customer.subscription.updated event
- [ ] Handles customer.subscription.deleted event (cancels subscription)
- [ ] Handles invoice.payment_failed event

---

## 24. Super Admin

> Requires is_super_admin flag on user account.

### 24.1 Dashboard
- [ ] GET /super-admin -- dashboard loads with platform-wide KPIs
- [ ] Shows total organizations, users, monitors, active incidents
- [ ] Super admin sidebar displayed (not regular admin sidebar)
- [ ] Mobile: responsive layout

### 24.2 Organizations
- [ ] GET /super-admin/organizations -- list loads with all organizations
- [ ] Shows org name, owner, plan, monitor count, created date
- [ ] Search/filter works
- [ ] Pagination works

### 24.3 Organization Detail
- [ ] GET /super-admin/organizations/{id} -- detail page loads
- [ ] Shows org info, members, monitors, subscription details
- [ ] "Impersonate" button visible

### 24.4 Impersonate
- [ ] POST /super-admin/organizations/{id}/impersonate -- switches context to org
- [ ] Banner shown indicating impersonation mode
- [ ] All pages show org's data while impersonating
- [ ] "Stop Impersonation" link visible in banner

### 24.5 Stop Impersonation
- [ ] GET /super-admin/organizations/stop-impersonation -- returns to super admin context
- [ ] Redirects to super admin dashboard

### 24.6 Users
- [ ] GET /super-admin/users -- list loads with all platform users
- [ ] Shows name, email, organization(s), role, last login
- [ ] Pagination works

### 24.7 User Detail
- [ ] GET /super-admin/users/{id} -- detail page loads
- [ ] Shows user info, organizations, activity

### 24.8 Revenue
- [ ] GET /super-admin/revenue -- revenue analytics page loads
- [ ] Shows MRR (Monthly Recurring Revenue)
- [ ] Shows plan distribution (free/pro/business)
- [ ] Revenue charts render

### 24.9 Platform Health
- [ ] GET /super-admin/health -- health page loads
- [ ] Shows system metrics (DB, Redis, queue, disk, memory)
- [ ] Shows background job status
- [ ] Shows recent errors/failures

### 24.10 Navigation
- [ ] "Back to Admin" link in super admin sidebar works
- [ ] Super Admin link visible in regular admin sidebar (only for super admins)

---

## 25. Home / Landing Page

- [ ] GET / -- redirects to /users/login (unauthenticated) or /dashboard (authenticated)

---

## 26. Cross-Cutting Concerns

### 26.1 CSRF Protection
- [ ] All POST forms include CSRF token
- [ ] Submitting form without CSRF token returns 403
- [ ] API routes (/api/v1/*) are exempt from CSRF
- [ ] Webhook routes (/webhooks/*) are exempt from CSRF

### 26.2 Authentication Redirects
- [ ] Accessing any admin page while unauthenticated redirects to /users/login
- [ ] After login, redirects to originally requested page (return URL)
- [ ] Public pages (/status, /s/{slug}, /badges/*, /heartbeat/*, /subscribers/subscribe) do not require auth

### 26.3 Authorization / Role Permissions
- [ ] Owner can access: all pages including Billing, Settings, Users, Invitations, API Keys
- [ ] Admin can access: all pages except Billing
- [ ] Member can access: Dashboard, Monitors, Checks, Incidents, Integrations, Status Pages, Maintenance
- [ ] Viewer can access: Dashboard, Monitors (read-only), Checks (read-only), Incidents (read-only)
- [ ] Attempting to access unauthorized page returns 403 or redirects with flash
- [ ] Super Admin pages return 403 for non-super-admin users

### 26.4 Multi-Tenancy / Organization Scoping
- [ ] All data queries are scoped to current organization
- [ ] User cannot access another organization's monitors, incidents, checks, etc.
- [ ] Switching organizations changes all displayed data
- [ ] API requests are scoped to the API key's organization

### 26.5 Flash Messages
- [ ] Success actions show green flash message
- [ ] Error actions show red flash message
- [ ] Warning actions show yellow flash message
- [ ] Flash messages auto-dismiss or are manually dismissable

### 26.6 404 / Error Pages
- [ ] Non-existent routes show 404 page
- [ ] Non-existent resource IDs show 404 page
- [ ] Server errors show 500 page (production mode)
- [ ] Error pages use appropriate layout

---

## 27. Internationalization (i18n)

- [ ] All UI strings wrapped in __() translation function
- [ ] Switching language in Settings reflects across all pages
- [ ] Tooltip translations load correctly for selected language
- [ ] Date/time formats respect locale settings
- [ ] Email templates use translated strings
- [ ] No hardcoded Portuguese/English strings visible in wrong language mode

---

## 28. Performance

- [ ] Dashboard loads in < 3 seconds
- [ ] Monitor list (100+ monitors) loads in < 3 seconds
- [ ] Checks list with pagination loads in < 2 seconds
- [ ] Public status page loads in < 2 seconds
- [ ] Badge SVGs return in < 500ms
- [ ] Heartbeat endpoint returns in < 200ms
- [ ] API v1 endpoints return in < 1 second
- [ ] No N+1 query issues visible in debug toolbar
- [ ] Charts render without blocking page load

---

## 29. Mobile Responsiveness

- [ ] Admin sidebar collapses to hamburger menu on mobile
- [ ] Sidebar opens/closes with toggle button
- [ ] Sidebar closes on overlay click
- [ ] Sidebar closes on Escape key
- [ ] Sidebar closes on nav item click (mobile)
- [ ] All tables scroll horizontally on small screens or switch to card layout
- [ ] All forms are single-column on mobile
- [ ] All buttons are tap-friendly (min 44px touch target)
- [ ] No horizontal scroll on any page (320px minimum width)
- [ ] Charts resize correctly on orientation change

---

## 30. Browser Compatibility

- [ ] Chrome (latest) -- all pages functional
- [ ] Firefox (latest) -- all pages functional
- [ ] Safari (latest) -- all pages functional
- [ ] Edge (latest) -- all pages functional
- [ ] iOS Safari -- all pages functional
- [ ] Android Chrome -- all pages functional

---

## Summary

| Section | Total Tests | Passed | Failed | Blocked |
|---------|------------|--------|--------|---------|
| 1. Authentication | 28 | | | |
| 2. Onboarding | 10 | | | |
| 3. Dashboard | 10 | | | |
| 4. Monitors | 33 | | | |
| 5. Incidents | 18 | | | |
| 6. Checks | 10 | | | |
| 7. Integrations | 17 | | | |
| 8. Status Pages | 18 | | | |
| 9. Maintenance Windows | 12 | | | |
| 10. Subscribers | 18 | | | |
| 11. Email Logs | 8 | | | |
| 12. Users | 14 | | | |
| 13. Invitations | 12 | | | |
| 14. API Keys | 8 | | | |
| 15. Settings | 22 | | | |
| 16. Billing | 11 | | | |
| 17. Org Switcher | 5 | | | |
| 18. Public Status | 9 | | | |
| 19. Badges | 8 | | | |
| 20. Heartbeat | 4 | | | |
| 21. API Docs | 3 | | | |
| 22. REST API v1 | 27 | | | |
| 23. Webhooks | 5 | | | |
| 24. Super Admin | 18 | | | |
| 25. Home | 1 | | | |
| 26. Cross-Cutting | 16 | | | |
| 27. i18n | 6 | | | |
| 28. Performance | 9 | | | |
| 29. Mobile | 10 | | | |
| 30. Browser Compat | 6 | | | |
| **TOTAL** | **~370** | | | |

---

## Notes / Issues Found

| # | Section | Severity | Description | Screenshot | Status |
|---|---------|----------|-------------|------------|--------|
| 1 | | | | | |
| 2 | | | | | |
| 3 | | | | | |
