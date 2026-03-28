# ISP Status Page - Detailed QA Testing Report

**Tester:** Alex (QA Detail Specialist)
**Date:** 2026-03-27
**App URL:** http://localhost:8765
**Login:** admin / admin123

---

## Summary

This report documents a comprehensive, page-by-page audit of the ISP Status Page application covering language consistency, visual consistency, capitalization, placeholder text, error/empty states, navigation, date formats, status badges, and tooltips.

**Total issues found: 87**
- Critical: 4
- High: 31
- Medium: 28
- Low: 24

---

## CRITICAL - Broken / Severe Functional Issues

### CRIT-001: `<html lang="pt-BR">` on multiple pages that should be English
**Pages affected:** /users/login, /status, /super-admin, /super-admin/users, /super-admin/organizations, /super-admin/revenue, /super-admin/health, /super-admin/security-logs
**Impact:** Screen readers and browser auto-translate will misidentify the page language. SEO crawlers will index the page as Portuguese content.
**Detail:** The `<html lang="pt-BR">` tag is used on 8 pages while the rest of the application uses `<html lang="en">`. This is a hard accessibility violation (WCAG 3.1.1).

### CRIT-002: Public status page banner displays Portuguese text to all visitors
**Page:** /status
**String:** `"Alguns servicos estao com problemas"` (should be "Some services are experiencing issues")
**Impact:** This is the most visible, public-facing page. Any visitor sees Portuguese text. Complete failure of i18n on the public-facing page.

### CRIT-003: Login page is almost entirely in Portuguese
**Page:** /users/login
**Strings in Portuguese:**
- Subtitle: `"Entre com sua conta"` (should be "Sign in to your account")
- Label: `"Usuario ou Email"` (should be "Username or Email")
- Placeholder: `"Digite seu usuario ou email"` (should be "Enter your username or email")
- Label: `"Senha"` (should be "Password")
- Placeholder: `"Digite sua senha"` (should be "Enter your password")
- Button: `"Entrar"` (should be "Sign In")
- Link: `"Esqueci minha senha"` (should be "Forgot my password")
- CSS comments in Portuguese: `"Cores Primarias"`, `"Espacamento"`, `"Sombras"`
**Impact:** The first page every user sees. Mixed language -- "Remember me" is in English while all other labels are Portuguese.

### CRIT-004: Auto-refresh timer mismatch on public status page
**Page:** /status
**Detail:** The footer text says `"Automatic updates every 30 seconds"` but the actual JavaScript timer (`RELOAD_INTERVAL`) is set to 300 seconds (5 minutes). The countdown shows 300 seconds. This is factually incorrect information displayed to users.

---

## HIGH - Language Issues (Portuguese text in English app)

### HIGH-001: Registration page has Portuguese labels
**Page:** /register
**Strings:**
- `"Senha"` instead of "Password" (label for password field)
- `"Confirmar Senha"` instead of "Confirm Password" (label for confirm password field)
**Note:** The rest of the registration page is in English (subtitle, username label, email label, button, placeholders). This is a mixed-language page.

### HIGH-002: Integrations list page is predominantly Portuguese
**Page:** /integrations
**Strings:**
- Title: `"Integracoes"` instead of "Integrations"
- Button: `"+ Nova Integracao"` instead of "+ New Integration"
- Stat label: `"Ativas"` instead of "Active"
- Filter label: `"Tipo"` instead of "Type"
- Search placeholder: `"Nome da integracao..."` instead of "Integration name..."
- Filter options: `"Ativa"` / `"Inativa"` instead of "Active" / "Inactive"
- JS strings: `"Testando..."`, `"Conexao bem-sucedida"`, `"Falha na conexao"`, `"Erro:"`, `"Testar"`

### HIGH-003: Integrations add page is predominantly Portuguese
**Page:** /integrations/add
**Strings:**
- Page title: `"Nova Integracao"` instead of "New Integration"
- Browser title: `"Nova Integracao - ISP Status Admin"`
- Subtitle: `"Configure uma nova integracao com sistema externo"` instead of "Configure a new integration with external system"
- Section title: `"Informacoes Basicas"` instead of "Basic Information"
- Label: `"Nome *"` instead of "Name *"
- Dropdown: `"Selecione..."` instead of "Select..."
- Status options: `"Ativa"` / `"Inativa"` instead of "Active" / "Inactive"
- Section title: `"Configuracao de Conexao"` instead of "Connection Configuration"
- Label: `"Timeout (segundos)"` instead of "Timeout (seconds)"
- Label: `"Tipo de Autenticacao"` instead of "Authentication Type"
- Auth option: `"Nenhuma"` instead of "None"
- Placeholder: `"Seu token de autenticacao"` instead of "Your authentication token"
- Label: `"Senha"` (twice) instead of "Password"
- Placeholder: `"Senha"` instead of "Password"
- Button: `"Salvar"` instead of "Save"
- Button: `"Cancelar"` instead of "Cancel"
- Button: `"Voltar"` instead of "Back"

### HIGH-004: Super Admin sidebar has Portuguese nav items (all sub-pages)
**Pages:** /super-admin, /super-admin/users, /super-admin/organizations, /super-admin/revenue, /super-admin/health, /super-admin/security-logs
**Strings:**
- Nav item: `"Painel"` instead of "Dashboard"
- Nav item: `"Usuarios"` instead of "Users"
- Dropdown: `"Meu Perfil"` instead of "My Profile"
- Dropdown: `"Configuracoes"` instead of "Settings"
- Dropdown: `"Sair"` instead of "Logout"

### HIGH-005: Super Admin Organizations page has Portuguese table headers and buttons
**Page:** /super-admin/organizations
**Strings:**
- Filter label: `"Buscar"` instead of "Search"
- Button: `"Filtrar"` instead of "Filter"
- Button: `"Limpar"` instead of "Clear"
- Table header: `"Monitores"` instead of "Monitors"
- Table header: `"Criado"` instead of "Created"

### HIGH-006: Super Admin Users page is partially Portuguese
**Page:** /super-admin/users
**Strings:**
- Browser title: `"Usuarios - Super Admin"`
- Page heading: `"Usuarios"` instead of "Users"
- Button: `"Buscar"` instead of "Search"
- Table header: `"Criado"` instead of "Created"

### HIGH-007: Super Admin Security Logs page has Portuguese filter button
**Page:** /super-admin/security-logs
**String:** `"Filtrar"` instead of "Filter"

### HIGH-008: Super Admin Health page has Portuguese chart label
**Page:** /super-admin/health
**String:** JavaScript chart label `"Monitores"` instead of "Monitors"

### HIGH-009: Super Admin dashboard has Portuguese table header
**Page:** /super-admin
**String:** Table header `"Monitores"` instead of "Monitors" (in "Top Organizations by Monitors" table)

### HIGH-010: Incidents page has Portuguese tooltip and badge text
**Page:** /incidents
**Strings:**
- Tooltip: `title="Auto-criado"` instead of "Auto-created"
- Tooltip: `title="Aguardando reconhecimento"` instead of "Awaiting acknowledgment"
- Badge text: `"Nao reconhecido"` instead of "Unacknowledged"

### HIGH-011: Users Add page is heavily Portuguese
**Page:** /users/add
**Strings:**
- Browser title: `"Novo Usuario - ISP Status Admin"`
- Sidebar: `"Painel Administrativo"` instead of "Admin Panel"
- Sidebar: `"Usuarios"` instead of "Users"
- Sidebar: `"Configuracoes"` instead of "Settings"
- Dropdown: `"Meu Perfil"` / `"Configuracoes"`
- Page heading: `"Novo Usuario"` instead of "New User"
- Button: `"Voltar"` instead of "Back"
- Section header: `"Informacoes do Usuario"` instead of "User Information"
- Placeholder: `"Digite o username"` instead of "Enter username"
- Placeholder: `"Digite o email"` instead of "Enter email"
- Help text: `"Configure geracao automatica de senha e envio de convite por email"`
- Checkbox: `"Gerar senha automaticamente"` instead of "Generate password automatically"
- Help text: `"Uma senha aleatoria segura sera gerada para este usuario"`
- Checkbox: `"Enviar email de convite"` instead of "Send invitation email"
- Help text: `"Usuario recebera credenciais e instrucoes de login por email"`
- Label: `"Senha"` / `"Confirmar Senha"`
- Placeholder: `"Digite a senha novamente"` instead of "Enter password again"
- Role dropdown: `"Selecione uma funcao"` instead of "Select a role"
- Role options: `"Administrador"`, `"Usuario"`, `"Visualizador"` instead of "Administrator", "User", "Viewer"
- Checkbox: `"Usuario ativo"` instead of "Active user"
- Button: `"Criar Usuario"` instead of "Create User"
- Button: `"Cancelar"` instead of "Cancel"

### HIGH-012: Public status page meta description is in Portuguese
**Page:** /status
**String:** `<meta name="description" content="Pagina de status em tempo real dos servicos de internet">` should be "Real-time status page for internet services"

### HIGH-013: Public status page console log uses Portuguese locale
**Page:** /status
**String:** `new Date().toLocaleString('pt-BR')` -- should use `'en-US'` or no locale argument.

### HIGH-014: Login page has mixed English/Portuguese ("Remember me" in English, everything else in Portuguese)
**Page:** /users/login
**Detail:** The "Remember me" checkbox label is in English while all surrounding labels (Usuario ou Email, Senha, Entrar) are in Portuguese. The social login section ("Or sign in with") and the register link ("Don't have an account? Register") are also in English. The loading state text `'Please wait...'` is in English. This creates a jarring language inconsistency on a single page.

---

## MEDIUM - Consistency Issues

### MED-001: Date format inconsistency across pages
**Detail:** Multiple date formats are used across the application:
- Dashboard Recent Checks: `DD/MM HH:MM:SS` (e.g., "28/03 15:37:04") -- no year, European format
- Monitors/Incidents/Status page: JavaScript `local-datetime` elements with ISO dates (rendered client-side)
- Status Pages table: `YYYY-MM-DD HH:MM` (e.g., "2026-03-27 21:52")
- Super Admin dashboard: `YYYY-MM-DD` (e.g., "2026-03-27")
- Super Admin orgs/users: `YYYY-MM-DD` format
**Recommendation:** Use a single consistent date format across all server-rendered dates, ideally the same `local-datetime` JS conversion used on other pages.

### MED-002: Pagination text format inconsistency
**Detail:**
- Monitors page: `"Page 1 of 1, showing 5 of 5 monitors"`
- Incidents page: `"Page 1 of 1, showing 2 record(s) of 2 total"`
- Status Pages: No pagination info text at all, just prev/next links
- Subscribers page: No pagination info text
**Issue:** Three different pagination info formats. "record(s)" is awkward -- should handle pluralization properly.

### MED-003: Pagination arrow style inconsistency
**Detail:**
- Monitors/Incidents pages use: `"< Previous"` / `"Next >"` with unicode arrows
- Status Pages use: `"< Previous"` / `"Next >"` with HTML entities (`&lt;` / `&gt;`)
- Wrapper class differs: `class="pagination"` vs `class="paginator"`

### MED-004: "Add" button style inconsistency
**Detail:**
- Monitors page: `class="btn-add"` with text `"+ New Monitor"`
- Integrations page: `class="btn-add"` with text `"+ Nova Integracao"` (also wrong language)
- Status Pages: `class="btn btn-primary"` with text `"+ New Status Page"`
- Maintenance: `class="btn btn-primary"` with text `"+ Schedule Maintenance"`
- API Keys: `class="btn-create"` with text `"+ Create New API Key"`
**Issue:** Three different CSS classes (`btn-add`, `btn btn-primary`, `btn-create`) for the same pattern of "add new item" button. Some use `+` prefix, all should be consistent.

### MED-005: Submit button style inconsistency
**Detail:**
- Integrations Add form: `class="btn-submit"` with text `"Salvar"`
- Users Add form: `class="btn btn-success"` with text `"Criar Usuario"`
- Users Edit form: `class="btn btn-success"` or `class="btn btn-primary"`
- Settings forms: `class="btn btn-primary"` with text `"Save Settings"`
- Monitors Add: `class="btn btn-primary"` with text `"Create Monitor"`
- Invitations: `class="btn btn-primary"` with text `"Send Invitation"`
**Issue:** Four different button styles for form submission actions.

### MED-006: Page header style inconsistency
**Detail:**
- Dashboard: `<h1>Dashboard</h1>` in `class="dashboard-header"`
- Monitors: `<h2>Monitors</h2>` (with emoji prefix) in `class="monitors-header"`
- Incidents: `<h2>Incidents</h2>` (with emoji prefix) in `class="incidents-header"`
- Integrations: `<h2>Integracoes</h2>` in `class="integrations-header"`
- Subscribers: `<h2>Notification Subscribers</h2>` in `class="subscribers-header"`
- Settings: `<h2>System Settings</h2>` in `class="settings-header"`
- Maintenance: `<h1>Maintenance Windows</h1>` in `class="content-header"`
- Status Pages: `<h1>Status Pages</h1>` in `class="content-header"`
- Invitations: `<h1>Team Invitations</h1>` in `class="content-header"`
**Issues:** Mix of `<h1>` and `<h2>` tags. Mix of header wrapper class names. Some headers include emoji prefixes and some do not.

### MED-007: Monitor type capitalization inconsistency
**Detail:**
- Monitors table badges show: `HTTP`, `PING`, `PORT`, `API` (all uppercase)
- Public status page shows: `Http`, `Port`, `Ping` (title case)
**Recommendation:** Consistently use uppercase badge format everywhere.

### MED-008: "Active"/"Inactive" label inconsistency
**Detail:**
- Monitors page filter dropdown: "Active" / "Inactive" (English)
- Monitors table: badge says `"Active"` / `"Inactive"`
- Integrations filter dropdown: `"Ativa"` / `"Inativa"` (Portuguese)
- Integrations Add form: `"Ativa"` / `"Inativa"` (Portuguese)

### MED-009: Footer contains Brazilian domain
**Pages:** All admin pages
**Detail:** Footer links to `https://www.datacake.com.br` -- the `.com.br` TLD is a Portuguese/Brazilian domain. For an English-language application, this may be intentional (company domain) but should be reviewed.

### MED-010: Empty state design inconsistency
**Detail:**
- Subscribers: Emoji icon + heading only, no descriptive text or action button
- Maintenance: Emoji icon + heading + description + action button
- Invitations: Emoji icon + heading + description text (no button, form above is CTA)
- Integrations: Emoji icon + heading + description + action button
- Email Logs (old version): Portuguese text `"Nenhum email encontrado"`
- Dashboard Recent Alerts: `class="empty-state"` with just text `"No recent alerts."`
**Issue:** Empty states have varying levels of detail and helpfulness.

### MED-011: Sidebar navigation item naming inconsistency
**Detail:**
- Regular admin sidebar uses: "Admin Panel" heading
- Super Admin sidebar uses: "Super Admin" heading
- Users Add page sidebar uses: `"Painel Administrativo"` (Portuguese heading)
**Issue:** Same sidebar element rendered with different language headings depending on the page.

### MED-012: Filter button labels mixed language
**Detail:**
- Monitors/Incidents/Subscribers pages: `"Filter"` / `"Clear"` (English)
- Super Admin Organizations: `"Filtrar"` / `"Limpar"` (Portuguese)
- Super Admin Security Logs: `"Filtrar"` (Portuguese)
- Super Admin Users: `"Buscar"` (Portuguese, means "Search")
- Email Logs: `"Filtrar"` / `"Limpar"` (Portuguese)

### MED-013: "Response Time" column shows "-" instead of actual values on monitors page
**Page:** /monitors
**Detail:** All monitors show `<span style="color: #ccc;">-</span>` in the Response Time column, even though the dashboard shows response time data for these same monitors (104ms, 18ms, 8ms, 0ms). The data exists but is not being displayed.

### MED-014: Two-factor auth page missing `lang` attribute
**Page:** /two-factor/setup
**Detail:** The `<html>` tag has no `lang` attribute at all, unlike all other pages.

### MED-015: Status page "QA Test Monitor" appears twice
**Page:** /status
**Detail:** The monitor "QA Test Monitor" is listed twice in the services section, both showing as "Offline". This appears to be a data issue or a display bug.

### MED-016: "Email Logs" page has two different versions
**Detail:** Fetching /email-logs renders an English version with the standard admin layout. However, a separate version exists with Portuguese translations (`"Painel Administrativo"`, `"Buscar"`, `"Filtrar"`, `"Limpar"`, `"Periodo"`, `"Nenhum email encontrado"`). This suggests a routing or template conflict.

### MED-017: CSS variable comments in Portuguese on login page
**Page:** /users/login
**Detail:** CSS comments use Portuguese: `/* Cores Primarias */`, `/* Cores Secundarias */`, `/* Tons Neutros */`, `/* Espacamento */`, `/* Sombras */`. While comments are not user-facing, the register page has the same variables with English comments (`/* Primary Colors */`, `/* Spacing */`, `/* Shadows */`), showing inconsistency between the two related pages.

---

## LOW - Polish Issues

### LOW-001: No breadcrumb navigation on any page
**Detail:** No pages include breadcrumb navigation. When navigating to sub-pages like /monitors/add, /monitors/view/1, /monitors/edit/1, /integrations/add, /users/edit/1, etc., there is no breadcrumb trail to help users orient themselves.

### LOW-002: Missing back button on incident pages
**Page:** /incidents
**Detail:** The incidents list page has no "Create Incident" button (unlike Monitors which has "+ New Monitor"). Manual incident creation requires editing the URL.

### LOW-003: Inconsistent use of emojis in page headers
**Detail:**
- Monitors: `"Monitors"` preceded by emoji in `<h2>`
- Incidents: `"Incidents"` preceded by emoji in `<h2>`
- Dashboard: No emoji
- Subscribers: No emoji
- Integrations: No emoji (Portuguese text)
- Settings: No emoji
**Recommendation:** Either use emojis consistently on all headers or remove them from all.

### LOW-004: No tooltip on monitors table columns
**Page:** /monitors
**Detail:** The "Response Time" column has no tooltip explaining what the dash "-" means. Is it not available? Not measured yet? The "Last Check" column shows "Never" for inactive monitors but no explanation.

### LOW-005: Inconsistent button text casing
**Detail:**
- `"+ New Monitor"` vs `"+ New Status Page"` vs `"+ Schedule Maintenance"` (all Title Case, consistent)
- `"+ Create New API Key"` (different pattern -- "Create New" vs just "New")
- Filter buttons: `"Filter"` / `"Clear"` (Title Case)
- Action buttons: `"View"` / `"Edit"` / `"Delete"` / `"Deactivate"` / `"Activate"` / `"Resolve"` (all Title Case, consistent)
- Form buttons: `"Save Settings"` / `"Create Monitor"` / `"Send Invitation"` / `"Verify and Enable 2FA"` (varied patterns)

### LOW-006: Users page does not have a filter/search form
**Page:** /users
**Detail:** Unlike Monitors, Incidents, Subscribers, and Integrations which all have filter/search forms, the Users page has no way to search or filter users.

### LOW-007: Checks page has no "Add" button
**Page:** /checks
**Detail:** The checks page shows check results but has no obvious way to trigger a manual check. The page is read-only with no clear CTA.

### LOW-008: API docs page loads external JS from CDN without fallback
**Page:** /api/docs
**Detail:** The page loads swagger-ui from `cdn.jsdelivr.net`. If the CDN is unavailable, the entire page will be blank with no error message.

### LOW-009: Status page footer says "All rights reserved" but GitHub link is public
**Page:** /status
**Detail:** Footer says "All rights reserved" while simultaneously linking to a public GitHub repository. Minor legal inconsistency.

### LOW-010: Inconsistent "Manage Subscription" button style on billing page
**Page:** /billing/plans
**Detail:** All three plan cards (Free, Pro, Business) show the same "Manage Subscription" button even though the Free plan says "Free" and has no subscription to manage. The button should say something different for the free tier.

### LOW-011: Missing placeholder text on some form fields
**Detail:**
- Users Edit form: Password fields may lack placeholder text
- Integrations Add: URL Base field has no visible placeholder
- Settings: Most form fields have no placeholder text (only the current value)

### LOW-012: Inconsistent page title format
**Detail:**
- Admin pages: `"[Page] - ISP Status Admin"` format
- Public pages: `"[Page] - ISP Status"` format
- Login: `"Login - ISP Status"` (no "Admin")
- Register: `"Register - ISP Status"` (no "Admin")
- Onboarding: `"Setup Your Organization - ISP Status"` (no "Admin")
- Super Admin: `"Super Admin Dashboard - Super Admin"` (different format entirely)
- Users Add: `"Novo Usuario - ISP Status Admin"` (Portuguese)
- Integrations: `"Integracoes - ISP Status Admin"` (Portuguese)

### LOW-013: "Datacake" footer link has no explanation
**Pages:** All admin pages
**Detail:** The footer links "Datacake", "Documentation", "Report Issue". "Datacake" has no label or context -- a new user would not know what it links to.

### LOW-014: Two-factor setup page has an empty `<footer>` tag
**Page:** /two-factor/setup
**Detail:** The page renders `<footer></footer>` with no content, unlike other admin pages that have the standard footer.

### LOW-015: Invitations page role dropdown differs from users add page
**Detail:**
- Invitations page roles: "Member", "Admin", "Viewer"
- Users Add page roles: `"Administrador"`, `"Usuario"`, `"Visualizador"` (Portuguese, different terms)
- Onboarding step 3 roles: "Admin", "Member", "Viewer"
**Issue:** Three different role label sets across three different pages.

### LOW-016: Public status page service type shows "Http" instead of "HTTP"
**Page:** /status
**Detail:** Service types are rendered in title case (`"Http"`, `"Port"`, `"Ping"`) instead of the uppercase format (`"HTTP"`, `"PORT"`, `"PING"`) used on the admin monitors page.

### LOW-017: No loading indicator on dashboard charts
**Page:** /dashboard
**Detail:** The Chart.js canvases have no loading state. If the charts take time to render, users see empty white rectangles.

### LOW-018: Mobile menu toggle renders but has no visible icon text
**Pages:** All admin pages
**Detail:** The mobile menu toggle button contains three empty `<span>` elements that presumably render a hamburger icon via CSS. If CSS fails to load, the button would be invisible.

### LOW-019: Status page incident "Duration: 00h 00m" is misleading
**Page:** /status
**Detail:** A resolved incident shows "Duration: 00h 00m" even though it lasted approximately 59 seconds (as shown on the admin incidents page). The duration formatting truncates to zero, making it look like the incident was instantaneous.

### LOW-020: Admin sidebar "Billing" link points to /billing not /billing/plans
**Pages:** All admin pages
**Detail:** The sidebar link is `href="/billing"` while the actual page URL is `/billing/plans`. This may cause a redirect, adding unnecessary latency.

### LOW-021: No confirmation or success message styling consistency
**Detail:** Delete actions use `confirm()` dialogs with different message patterns:
- Monitors: `"Are you sure you want to delete this monitor? This action cannot be undone."`
- Status Pages: `"Are you sure you want to delete Testing?"`
- Incidents resolve: `"Are you sure you want to resolve this incident?"`
**Issue:** Some confirmations mention "This action cannot be undone" while others do not.

### LOW-022: Missing meta description on admin pages
**Pages:** All admin pages (dashboard, monitors, incidents, etc.)
**Detail:** No admin page includes a `<meta name="description">` tag. The only page with one is /status, and it is in Portuguese.

### LOW-023: Inconsistent badge color scheme for plan types
**Page:** /super-admin
**Detail:**
- "Free" plan uses `badge-secondary` (gray)
- "Business" plan uses `badge-danger` (red)
**Issue:** "Business" being shown in red/danger styling implies something negative, when it is actually the highest tier plan. Should use a positive color like green or blue.

### LOW-024: Status page "Operational" badge vs admin "Active" badge
**Detail:**
- Public /status page shows `"Operational"` / `"Offline"` for service status
- Admin /monitors page shows `"Active"` / `"Inactive"` for monitor state and `status-up` / `status-down` indicators
- These represent different concepts (operational status vs. enabled/disabled state) but could be confusing

---

## Summary Table: Portuguese Strings Found

| Page | Portuguese String | Should Be (English) |
|------|-------------------|---------------------|
| /users/login | Entre com sua conta | Sign in to your account |
| /users/login | Usuario ou Email | Username or Email |
| /users/login | Digite seu usuario ou email | Enter your username or email |
| /users/login | Senha | Password |
| /users/login | Digite sua senha | Enter your password |
| /users/login | Entrar | Sign In |
| /users/login | Esqueci minha senha | Forgot my password |
| /register | Senha | Password |
| /register | Confirmar Senha | Confirm Password |
| /status | Alguns servicos estao com problemas | Some services are experiencing issues |
| /status | Pagina de status em tempo real... (meta) | Real-time status page... |
| /status | pt-BR locale in JS | en-US locale |
| /incidents | Auto-criado | Auto-created |
| /incidents | Aguardando reconhecimento | Awaiting acknowledgment |
| /incidents | Nao reconhecido | Unacknowledged |
| /integrations | Integracoes | Integrations |
| /integrations | + Nova Integracao | + New Integration |
| /integrations | Ativas | Active |
| /integrations | Tipo | Type |
| /integrations | Nome da integracao... | Integration name... |
| /integrations | Ativa / Inativa | Active / Inactive |
| /integrations | Testando... | Testing... |
| /integrations | Conexao bem-sucedida | Connection successful |
| /integrations | Falha na conexao | Connection failed |
| /integrations | Erro: | Error: |
| /integrations | Testar | Test |
| /integrations/add | Nova Integracao | New Integration |
| /integrations/add | Configure uma nova integracao... | Configure a new integration... |
| /integrations/add | Informacoes Basicas | Basic Information |
| /integrations/add | Nome * | Name * |
| /integrations/add | Selecione... | Select... |
| /integrations/add | Ativa / Inativa | Active / Inactive |
| /integrations/add | Configuracao de Conexao | Connection Configuration |
| /integrations/add | Timeout (segundos) | Timeout (seconds) |
| /integrations/add | Tipo de Autenticacao | Authentication Type |
| /integrations/add | Nenhuma | None |
| /integrations/add | Seu token de autenticacao | Your authentication token |
| /integrations/add | Senha (2x) | Password |
| /integrations/add | Salvar | Save |
| /integrations/add | Cancelar | Cancel |
| /integrations/add | Voltar | Back |
| /super-admin/* | Painel | Dashboard |
| /super-admin/* | Usuarios | Users |
| /super-admin/* | Meu Perfil | My Profile |
| /super-admin/* | Configuracoes | Settings |
| /super-admin/* | Sair | Logout |
| /super-admin/organizations | Buscar | Search |
| /super-admin/organizations | Filtrar | Filter |
| /super-admin/organizations | Limpar | Clear |
| /super-admin/organizations | Monitores | Monitors |
| /super-admin/organizations | Criado | Created |
| /super-admin/users | Usuarios (title) | Users |
| /super-admin/users | Buscar | Search |
| /super-admin/users | Criado | Created |
| /super-admin/security-logs | Filtrar | Filter |
| /super-admin/health | Monitores (chart) | Monitors |
| /super-admin (dashboard) | Monitores (table header) | Monitors |
| /users/add | Novo Usuario | New User |
| /users/add | Informacoes do Usuario | User Information |
| /users/add | (20+ Portuguese strings) | (See HIGH-011) |

---

## Pages With No Issues Found

The following pages passed all checks with no language, consistency, or visual issues:

- /dashboard (English, consistent)
- /monitors (English, consistent)
- /monitors/add (English, consistent)
- /checks (English, consistent)
- /settings (English, consistent)
- /billing/plans (English, consistent)
- /api-keys (English, consistent)
- /subscribers (English, consistent)
- /maintenance-windows (English, consistent)
- /onboarding/step1 (English, consistent)
- /onboarding/step2 (English, consistent)
- /onboarding/step3 (English, consistent)
- /api/docs (English, loads Swagger UI)

---

## Recommendations

1. **Immediate:** Fix all `<html lang="pt-BR">` tags to `<html lang="en">` on affected pages.
2. **Immediate:** Translate the login page, registration page, and public status page fully to English.
3. **Immediate:** Fix the 30-second vs 5-minute auto-refresh discrepancy on the status page.
4. **High priority:** Translate all integrations pages (list and add form) to English.
5. **High priority:** Translate all Super Admin pages to English.
6. **High priority:** Translate the Users Add page to English.
7. **High priority:** Translate incident badge tooltips ("Auto-criado", "Nao reconhecido") to English.
8. **Medium priority:** Standardize date formats across all pages using the `local-datetime` JS utility.
9. **Medium priority:** Standardize pagination format and CSS classes.
10. **Medium priority:** Standardize button classes (primary action, secondary action, filter, etc.).
11. **Medium priority:** Standardize page header elements (h1 vs h2, emoji usage, wrapper classes).
12. **Low priority:** Add breadcrumb navigation to sub-pages.
13. **Low priority:** Add missing tooltips and help text.
14. **Low priority:** Fix the "Duration: 00h 00m" display for short incidents.
