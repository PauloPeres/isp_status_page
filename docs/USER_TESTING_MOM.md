# User Testing Report: "Maria the Baker"

**Tester Persona:** Maria, 52 years old, runs a small online bakery (mariabakery.com). Has never used monitoring software. Barely understands "uptime." Her son told her to use this tool.

**Date:** 2026-03-27
**Application:** ISP Status Page (http://localhost:8765)
**Method:** Page-by-page review via HTTP requests, evaluating from a non-technical user's perspective.

---

## Executive Summary

The ISP Status Page application is clearly built for ISP administrators and technical users. For a non-technical small business owner like Maria, the experience is intimidating and confusing in several areas. The biggest issues are: mixed Portuguese/English text throughout, heavy technical jargon with no plain-language explanations, and a "New Monitor" form that would baffle anyone who does not already know what HTTP methods, status codes, and TCP ports are.

That said, the visual layout is clean, the sidebar navigation uses helpful icons, and the billing page is one of the best-designed pages in the application.

---

## Page-by-Page Findings

### 1. Homepage (/) - When Not Logged In

**What I see:** Visiting the root URL redirects to the login page. There is no landing page, no welcome message, no explanation of what this tool does.

**What confuses me:** I just got sent to a login page with no context. What is "ISP Status"? Is this where I check if my bakery website is working? There is nothing telling me what this tool does or why I should care.

**Portuguese found:**
- The entire login page is `lang="pt-BR"`
- "Usuario ou Email" (label)
- "Senha" (label for password)
- "Digite seu usuario ou email" (placeholder)
- "Digite sua senha" (placeholder)
- "Entrar" (submit button, means "Enter/Login")
- "Esqueci minha senha" (forgot password link)

**Suggestions:**
- Add a simple landing page that explains in plain English: "Monitor your website and get alerts when it goes down."
- The login page mixes languages: "Remember me" is in English, but "Entrar" is Portuguese. Pick one language and stick with it.

---

### 2. Registration Page (/register)

**What I see:** A registration form with Username, Email, Password, and Terms checkbox. The page language is set to `lang="en"` but still has Portuguese labels.

**Portuguese found:**
- "Senha" (password label - should be "Password")
- "Confirmar Senha" (confirm password label - should be "Confirm Password")

**What confuses me:** The labels switch between English and Portuguese mid-form. "Username" and "Email" are in English, but then suddenly "Senha" and "Confirmar Senha" are in Portuguese. I would think I landed on a different website.

**What is good:** The form is simple and not overwhelming. "At least 8 characters" as a hint is helpful. Terms of Service checkbox is clear.

**Suggestions:**
- Translate "Senha" to "Password" and "Confirmar Senha" to "Confirm Password"
- Add a brief tagline under the "ISP Status" header explaining what this tool is for
- Consider renaming "Username" to something friendlier like "Choose a display name"

---

### 3. Dashboard (/dashboard)

**What I see:** Summary cards showing Total Monitors (4), Online (3), Offline (0), Degraded (0), Unknown (1). Two charts (Uptime Last 24h, Average Response Time). Tables for Recent Checks and Recent Alerts. Active Incidents (2) with "2 Major" severity.

**What confuses me:**
- "Degraded" -- what does that mean? Is it broken or not?
- "Unknown" -- is this bad? Should I be worried?
- "Average Response Time (ms)" -- what is "ms"? What number is good?
- The Recent Checks table shows a column with numbers like 0, 10, 100, 7 -- what are these? There is no column header visible for them. Are they response times? Points? Grades?
- "2 Major" active incidents -- "Major" sounds scary. What does "Major" vs "Minor" vs "Critical" actually mean in real terms?

**What is good:** The color coding (green for online, red for offline) is intuitive. The summary cards at the top give a quick overview. "System Online" in the sidebar footer is reassuring.

**Suggestions:**
- Add tooltips or small help text explaining what "Degraded" and "Unknown" mean
- Explain what response time means in plain language: "How fast your website responds (lower is better)"
- Label the numbers column in the Recent Checks table
- Consider adding a friendly message when everything is OK: "All your websites are running normally!"

---

### 4. Monitors List (/monitors)

**What I see:** A table with 5 monitors listed. Each has Status, Name, Type (HTTP/PING/PORT/API), Target, Last Check, Response Time, State, and Actions (View/Edit/Deactivate/Delete). There are filter options for Search, Type, Status, and State.

**What confuses me:**
- "PING" and "PORT" and "API" as monitor types -- I have no idea what these mean. I just want to check if my website is up.
- "Target" column shows things like "google.com:443" -- what does ":443" mean?
- "State" vs "Status" -- what is the difference? "Active" vs "Online" -- are they the same thing?
- The QA Test Monitor has a blank Target field -- is this broken?
- "Deactivate" vs "Delete" -- what is the difference? Will deactivating lose my data?

**What is good:** The "+ New Monitor" button is clearly visible. The color-coded status dots (green/red/gray) are easy to read. Filters are available but not forced on me.

**Suggestions:**
- Rename monitor types to friendlier names: "Website Check" instead of "HTTP", "Server Ping" instead of "PING"
- Explain the difference between State and Status somewhere
- Add a tooltip on "Deactivate" saying "Pauses monitoring without deleting your data"

---

### 5. Add New Monitor (/monitors/add)

**What I see:** A very long form with sections: Basic Information, Target Configuration, and Check Settings. The type dropdown offers: HTTP/HTTPS, Ping (ICMP), Port (TCP/UDP), Heartbeat, Keyword, SSL Certificate. Depending on the type, different fields appear.

**What confuses me (a LOT):**
- "HTTP/HTTPS" vs "Ping (ICMP)" vs "Port (TCP/UDP)" vs "Heartbeat" vs "Keyword" vs "SSL Certificate" -- I have absolutely no idea which one to pick for my bakery website. This is the moment I would give up and call my son.
- "Expected HTTP status code (e.g. 200=OK, 301=Redirect, 404=Not Found)" -- What? Why do I need to know a code number?
- "Method: GET/POST/PUT/DELETE/HEAD/OPTIONS/PATCH" -- These are meaningless to me.
- "Headers" field with a JSON placeholder -- this looks like programming code.
- "Request body for POST/PUT/PATCH" -- I do not know what any of this means.
- "Verify SSL Certificate" -- What is SSL? Is it important?
- "Follow Redirects" -- What is a redirect?
- "Packet count" and "Max packet loss percentage" -- This sounds like something from a science textbook.
- "Check interval: minimum 10s" -- 10 seconds? 10 minutes? The "s" is ambiguous.
- "Timeout: Maximum wait time" -- Wait time for what?
- For Heartbeat: "Expected interval" and "Grace period" in seconds -- Very technical.

**What is good:** The help text under each field is a nice touch. The placeholder "e.g. Main Website" in the name field is helpful. The form does show/hide fields based on type selection.

**Suggestions:**
- Add a "Quick Setup" wizard that asks: "What do you want to monitor? [My Website] [My Email Server] [Something Else]" and auto-fills the technical fields
- For the most common case (monitoring a website), just ask for the URL and set sensible defaults for everything else
- Replace "HTTP/HTTPS" with "Website" as the label
- Hide advanced fields (Headers, Body, etc.) behind an "Advanced Options" toggle
- Change "Check interval" help text to "How often we check your site (e.g., every 30 seconds)"
- Add a visual recommendation like "Recommended for most websites: HTTP/HTTPS with defaults"

---

### 6. Checks Page (/checks)

**What I see:** Statistics showing Total Checks (4,309), Success (3,318), Failures (991), Success Rate, and Average Time. A table of individual check results.

**What confuses me:**
- 991 failures sounds terrifying! Is my stuff broken? (Actually, these are from the demo monitors, but a user would not know that.)
- "Success Rate" as a percentage -- is 77% good or bad?
- What is a "check" exactly? Is it the same as a "test"?

**Suggestions:**
- Add context: "A check is a test we run to see if your website is responding"
- Color-code the success rate (green for good, yellow for warning, red for bad) -- this may already happen via CSS
- Consider showing checks only for the user's own monitors, not all demo data

---

### 7. Incidents Page (/incidents)

**What I see:** A list of incidents with Status, Incident title, Monitor, Severity, Started time, Duration, and Actions. There are 2 active incidents and 1 resolved.

**Portuguese found:**
- `title="Auto-criado"` (tooltip on auto-generated badge) -- should be "Auto-created"
- `title="Aguardando reconhecimento"` (tooltip) -- should be "Awaiting acknowledgment"
- "Nao reconhecido" (badge text) -- should be "Not acknowledged"

**What confuses me:**
- "Investigating" as a status -- who is investigating? Me? The system?
- Severity levels: "Critical", "Major", "Minor", "Maintenance" -- what is the real difference?
- The robot emoji badge next to "Auto-criado" -- does that mean a robot created this incident? What robot?
- "Duration: In progress" -- how long has it been going? This tells me nothing.

**Suggestions:**
- Translate all Portuguese tooltip text to English
- Add plain-language severity explanations: "Major = Some users may be affected"
- Explain auto-created incidents: "This incident was automatically created when the monitor detected a problem"
- Show actual elapsed time instead of just "In progress"

---

### 8. Integrations Page (/integrations)

**What I see:** An empty page with a prompt to add integrations. It mentions "IXC, Zabbix, or REST APIs."

**Portuguese found:**
- Page heading says "Integracoes" (should be "Integrations")
- Button says "Nova Integracao" (should be "New Integration")
- JavaScript has "Testando..." (should be "Testing...")
- JavaScript has "Testar" (should be "Test")

**What confuses me:**
- "IXC" and "Zabbix" -- What are these? They sound like alien species.
- "REST APIs" -- What is a REST API?
- Why would I need to "integrate" anything? I just want to know if my website is up.

**Suggestions:**
- Translate all Portuguese text to English
- Replace jargon with plain descriptions: "Connect to other tools to get alerts through Slack, email, or text message"
- Consider hiding this page for non-technical users or adding an explanation of what integrations are for

---

### 9. Status Pages (/status-pages)

**What I see:** A table with one status page called "Testing" with slug "Hello". Columns: Name, Slug, Custom Domain, Status, Password, Created, Actions.

**What confuses me:**
- "Slug" -- What on earth is a slug? (It is a URL-friendly name, but I do not know that.)
- "Custom Domain" -- Does this mean I can use my own web address?
- Why would a status page need a password?

**Suggestions:**
- Rename "Slug" to "URL Name" or "Page Address"
- Add help text explaining what a status page is: "A public page your customers can visit to see if your services are running"

---

### 10. Maintenance Windows (/maintenance-windows)

**What I see:** An empty page with the message "No maintenance windows scheduled" and a button to "Schedule Maintenance."

**What confuses me:**
- "Maintenance window" -- is this like scheduled downtime? The term is not intuitive for a non-technical person.

**What is good:** The empty state message is clear, and the call-to-action button is obvious.

**Suggestions:**
- Add a subtitle like: "Plan ahead by telling your customers when your website will be temporarily offline for updates"

---

### 11. Subscribers Page (/subscribers)

**What I see:** Empty page with "No subscribers yet." Has filter options and stat cards.

**What confuses me:** Who are subscribers? Are these people who pay me? Or people who get notifications?

**Suggestions:**
- Add explanatory text: "People who have signed up to receive notifications when your services have issues"

---

### 12. Settings Page (/settings)

**What I see:** Five tabs: General, Email, Monitoring, Notifications, Backup. Each tab has a form.

**What confuses me:**
- **General tab:** "Status page cache time in seconds" -- What is a cache? What should I set this to?
- **General tab:** "Site Logo URL" -- I need a URL for my logo? Can I just upload an image?
- **Email tab:** "SMTP Host", "SMTP Port", "SMTP Encryption (TLS/SSL)" -- This is extremely technical. I do not know what SMTP is.
- **Monitoring tab:** "Check retention days" -- retention of what?
- **Notifications tab:** "Alert throttle minutes" -- what is throttling?
- **Backup tab:** "FTP", "SFTP", "FTP Passive Mode" -- I have no idea what any of this means.

**What is good:** Tabs are well organized. Having a "Restore Defaults" button is reassuring. The "Send Test Email" feature is practical.

**Suggestions:**
- Add help text for ALL settings, not just some
- Replace "SMTP" with "Email Server Settings" and add a setup wizard or link to guides for common providers (Gmail, Outlook, etc.)
- Replace "Cache" with "How often to refresh the page (in seconds)"
- Allow image upload instead of requiring a URL for the logo
- Group "scary" settings (FTP backup, SMTP) under an "Advanced" section

---

### 13. Billing Page (/billing)

**What I see:** Three plans displayed as cards: Free ($0), Pro ($15/mo), Business ($45/mo, current plan). Monthly/Yearly toggle with "Save 20%" badge. Feature lists for each plan. Current usage summary at the bottom.

**What confuses me:**
- "API access (1,000 req/hr)" -- What is API access? What is req/hr?
- "SSL monitoring" -- What is SSL?
- "30 second check interval" vs "5 minute check interval" -- which is better? (Lower is better, but that is not obvious.)
- "Data retention" -- retention of what data?

**What is good:** This is actually one of the best pages. The plan comparison is clear, the current plan is highlighted, the monthly/yearly toggle is intuitive, and the usage summary at the bottom is helpful. The "Most Popular" badge on Pro is a nice touch.

**Suggestions:**
- Replace "API access (1,000 req/hr)" with "Developer API access" or just "API access" -- non-technical users do not need to know req/hr
- Explain check interval in plain terms: "We check your site every X" rather than just a number
- Add a comparison table or "which plan is right for me?" guidance

---

### 14. Public Status Page (/status)

**What I see:** A public-facing page showing system status. Has a header with "System Status", service cards for each monitor with uptime percentages, and a subscribe section.

**Portuguese found:**
- Page is `lang="pt-BR"`
- `<meta name="description" content="Pagina de status em tempo real dos servicos de internet">`
- "Alguns servicos estao com problemas" (status banner, means "Some services are having problems")
- JavaScript uses `toLocaleString('pt-BR')`

**What confuses me:** If this is my public-facing page that my bakery customers see, why is it in Portuguese when the rest of the admin is (mostly) in English?

**Suggestions:**
- Make the language configurable in settings, or follow the admin interface language
- The meta description should match the configured language
- Translate "Alguns servicos estao com problemas" to "Some services are experiencing issues"

---

### 15. Error/404 Page

**What I see:** When visiting a nonexistent URL (like /users/register), I get a basic error page with "Not Found" and "The requested address was not found on this server."

**Portuguese found:**
- The back link says "Voltar" (should be "Go Back" or "Back")

**Suggestions:**
- Translate "Voltar" to "Go Back"
- Add a link to the dashboard or homepage, not just browser history

---

### 16. API Keys Page (/api-keys)

**What I see:** A table of API keys with Name, Key Prefix, Permissions, Status, Last Used, Created, and Actions.

**What confuses me:**
- "API Key" -- What is an API? Why would I need a key for it?
- "Key Prefix" -- What does this mean?
- "Revoke" -- Does this delete it? Disable it? What happens to things using this key?

**Suggestions:**
- This page probably should not be shown to non-technical users, or should be under an "Advanced" section
- Add explanation text: "API keys allow other software to connect to your monitoring data"

---

### 17. Invitations Page (/invitations)

**What I see:** A form to invite team members by email and role, with a list of sent invitations (currently empty).

**What is good:** This is actually quite clear and straightforward. "Send New Invitation" form with email and role fields is simple.

**Suggestions:**
- Add a brief explanation of what each role (Admin, User, Viewer) can do

---

## Summary of Portuguese Text Found (Should Be English)

| Page | Portuguese Text | Should Be |
|------|----------------|-----------|
| Login page | "Usuario ou Email" | "Username or Email" |
| Login page | "Senha" | "Password" |
| Login page | "Digite seu usuario ou email" | "Enter your username or email" |
| Login page | "Digite sua senha" | "Enter your password" |
| Login page | "Entrar" | "Login" or "Sign In" |
| Login page | "Esqueci minha senha" | "Forgot my password" |
| Login page | `lang="pt-BR"` | `lang="en"` |
| Register page | "Senha" | "Password" |
| Register page | "Confirmar Senha" | "Confirm Password" |
| Incidents page | "Auto-criado" (tooltip) | "Auto-created" |
| Incidents page | "Aguardando reconhecimento" (tooltip) | "Awaiting acknowledgment" |
| Incidents page | "Nao reconhecido" (badge) | "Not acknowledged" |
| Integrations page | "Integracoes" (heading) | "Integrations" |
| Integrations page | "Nova Integracao" (button) | "New Integration" |
| Integrations page | "Testando..." (JS) | "Testing..." |
| Integrations page | "Testar" (JS button) | "Test" |
| Public status page | "Alguns servicos estao com problemas" | "Some services are experiencing issues" |
| Public status page | "Pagina de status em tempo real dos servicos" (meta) | "Real-time service status page" |
| Public status page | `lang="pt-BR"` | `lang="en"` |
| Public status page | `toLocaleString('pt-BR')` | Should use configured locale |
| Error/404 page | "Voltar" | "Go Back" |
| Super Admin page | `lang="pt-BR"` | `lang="en"` |

---

## Top 10 Jargon Terms That Need Plain-Language Alternatives

1. **SMTP** -- "Email server settings"
2. **HTTP/HTTPS** -- "Website"
3. **Ping (ICMP)** -- "Server ping test"
4. **Port (TCP/UDP)** -- "Network port check"
5. **SSL Certificate** -- "Security certificate"
6. **API** -- "Software connection" or just hide from non-technical users
7. **Slug** -- "URL name" or "Page address"
8. **Cache** -- "Refresh time"
9. **Throttle** -- "Minimum wait between alerts"
10. **FTP/SFTP** -- "Remote file transfer" or "Backup server"

---

## Overall Scores (Maria's Perspective)

| Category | Score (1-5) | Notes |
|----------|-------------|-------|
| First impression | 2/5 | No landing page, dropped straight into Portuguese login |
| Registration | 3/5 | Simple form but mixed languages |
| Dashboard | 3/5 | Good visual layout, confusing numbers |
| Adding a monitor | 1/5 | Extremely technical, would give up without help |
| Navigation | 4/5 | Sidebar is clear, icons help, well organized |
| Settings | 2/5 | Very technical (SMTP, FTP, cache) |
| Billing | 4/5 | Clear pricing, good comparison layout |
| Language consistency | 1/5 | Chaotic mix of Portuguese and English |
| Overall usability | 2/5 | Built for ISP engineers, not small business owners |

---

## Top Recommendations (Priority Order)

1. **Fix language consistency** -- Pick English as the default and translate ALL remaining Portuguese strings. The login page, register page, public status page, integrations page, incidents tooltips, and error page all have Portuguese text mixed in.

2. **Add a "Quick Setup" wizard for monitors** -- For the most common use case (checking if a website is up), just ask for the URL and set all defaults automatically. Hide HTTP methods, headers, status codes, etc. behind an "Advanced" toggle.

3. **Add a landing/welcome page** -- Before login, explain what this tool does in one sentence. After first login, offer a guided setup.

4. **Replace technical jargon** -- Use plain language throughout, or at minimum add help tooltips explaining terms like SMTP, SSL, cache, API, slug, etc.

5. **Add contextual help** -- On the dashboard, explain what the numbers mean. What is a good response time? What does "Degraded" mean? A small (?) icon with a tooltip would help enormously.

---

*Report prepared from the perspective of a non-technical user who just wants to know if her bakery website is working.*
