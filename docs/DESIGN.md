# Design System — KeepUp

Design system and visual standards for the KeepUp uptime monitoring platform.

> **Brand:** KeepUp | **Domain:** usekeeup.com | **Palette:** Option C — Bold Navy + Electric Blue

## Color Philosophy

**Why Navy + Electric Blue?**
- Navy (#1A2332) communicates institutional reliability — the "always watching" foundation
- Electric Blue (#2979FF) signals cutting-edge technology and precision
- Status colors (green/red/yellow) have maximum semantic clarity — zero confusion with brand colors
- Dark-first design is native for monitoring dashboards (NOC operators, on-call engineers at 3AM)
- Premium positioning: KeepUp looks like a tool you can trust with your infrastructure

**What changed from the old Indigo (#6366F1)?**
- Indigo felt like a "design tool" or "project management app" — not authoritative enough for infrastructure monitoring
- The new Electric Blue is bolder, more distinctive, and better differentiated from competitors
- Navy surfaces give the app a command-center feel that resonates with operations teams

---

## Index
- [Colors](#colors)
- [Typography](#typography)
- [Spacing](#spacing)
- [Buttons](#buttons)
- [Cards](#cards)
- [Filters](#filters)
- [Tables](#tables)
- [Badges](#badges)
- [Pagination](#pagination)
- [Responsiveness](#responsiveness)

---

## Colors

### Brand Colors (Navy + Electric Blue)
```css
Electric Blue (Primary):  #2979FF   /* Buttons, links, CTAs, interactive elements */
Electric Hover:           #2962FF   /* Hover/pressed state */
Electric Light:           #448AFF   /* Highlights, secondary accent */
Spark:                    #82B1FF   /* Subtle highlights, hover glows */
Command Navy:             #1A2332   /* Sidebar, headers, primary surfaces */
Abyss:                    #0F1923   /* Deep backgrounds, overlays */
```

### Status Colors (Semantic — NEVER use for branding)
```css
Sentinel Green (Up):      #00E676   /* Uptime indicators, "all operational" */
Breach Red (Down):        #FF1744   /* Downtime, critical alerts */
Flux Yellow (Degraded):   #FFEA00   /* Degraded performance, latency warnings */
```

### Neutral Scale
```css
Platinum (Light BG):      #F8F9FB
Cloud:                    #F1F5F9
Mist:                     #E2E8F0
Fog:                      #CBD5E1
Pewter:                   #6B7280
Graphite:                 #4B5563
Steel:                    #2C3E50
Navy:                     #1A2332
Abyss:                    #0F1923
```

### Borders
```css
Border Default:           #E2E8F0
Border Light:             #F1F5F9
Border Input:             #CBD5E1
```

### Hover States
```css
Electric Hover:           #2962FF
Green Hover:              #00C853
Red Hover:                #D50000
Yellow Hover:             #FFD600
Gray Hover:               #4B5563
Background Hover:         #F1F5F9
```

### Shadows
```css
Brand Shadow:      0 4px 14px rgba(41, 121, 255, 0.25)   /* Electric Blue glow */
Success Shadow:    0 4px 14px rgba(0, 230, 118, 0.25)     /* Green status glow */
Error Shadow:      0 4px 14px rgba(255, 23, 68, 0.25)     /* Red alert glow */
Card Shadow:       0 1px 3px rgba(0, 0, 0, 0.05)          /* Subtle card shadow */
```

---

## Typography

### Fonts
```css
Display (Headings):    'DM Sans', system-ui, sans-serif
Body (Text):           'Plus Jakarta Sans', system-ui, sans-serif
Code (Monospace):      'JetBrains Mono', 'Fira Code', monospace
```

### Font Sizes
```css
12px - Labels, badges, small buttons
13px - Secondary descriptions, helper text
14px - Default body text
16px - Section titles
18px - Subtitles
28px - Statistics values
```

### Font Weights
```css
400 - Normal (body text)
500 - Medium (buttons, important links)
600 - Semibold (labels, card titles)
700 - Bold (statistics values, headings)
```

---

## Spacing

### Spacing System (4px base)
```css
4px  - Minimal gap between inline elements
8px  - Gap between buttons, badges
12px - Internal padding of small elements
16px - Gap between cards, card padding
20px - Filter padding
24px - Margin between sections
32px - Header margin bottom
```

---

## Buttons

### IMPORTANT RULE: NEVER use icons in buttons
All buttons must use TEXT ONLY for visual consistency.

### Action Buttons (Tables)
```css
.btn-action {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}
```

#### Color Variations
```css
View:            background: #2979FF; color: white;    /* Electric Blue */
View hover:      background: #2962FF;
Edit:            background: #FFEA00; color: #0F1923;  /* Flux Yellow */
Edit hover:      background: #FFD600;
Resolve:         background: #00E676; color: #0F1923;  /* Sentinel Green */
Resolve hover:   background: #00C853;
Toggle:          background: #82B1FF; color: #0F1923;  /* Spark */
Toggle hover:    background: #448AFF;
Delete:          background: #FF1744; color: white;     /* Breach Red */
Delete hover:    background: #D50000;
```

### Primary Buttons
```css
.btn-primary {
    background: #2979FF;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
}
.btn-primary:hover {
    background: #2962FF;
}
```

---

## Cards

### Card Base
```css
.card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
```

### Statistics Cards
```css
.stat-value.success  { color: #00E676; }
.stat-value.error    { color: #FF1744; }
.stat-value.info     { color: #2979FF; }
.stat-value.warning  { color: #FFEA00; }
```

---

## Filters

### Filter Container
```css
.filters-card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
}
```

---

## Tables

### Headers
```css
.table-container th {
    background: #F8F9FB;
    font-size: 13px;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
}
```

### Row Hover
```css
.table-container tbody tr:hover {
    background: #F1F5F9;
}
```

---

## Badges

```css
.badge-success    { background: #E8F5E9; color: #00C853; }
.badge-danger     { background: #FFEBEE; color: #D50000; }
.badge-warning    { background: #FFFDE7; color: #FFD600; }
.badge-info       { background: #E3F2FD; color: #2962FF; }
.badge-secondary  { background: #F1F5F9; color: #6B7280; }
```

---

## Pagination

```css
.pagination a:hover {
    border-color: #2979FF;
    color: #2979FF;
}
.pagination .active {
    background: #2979FF;
    color: white;
    border-color: #2979FF;
}
```

---

## Status Indicators

```css
.status-up      { background: #00E676; box-shadow: 0 0 8px rgba(0, 230, 118, 0.4); }
.status-down    { background: #FF1744; box-shadow: 0 0 8px rgba(255, 23, 68, 0.4); }
.status-degraded { background: #FFEA00; box-shadow: 0 0 8px rgba(255, 234, 0, 0.4); }
.status-unknown { background: #6B7280; }
```

---

## Border Radius

```css
4px  - Small (action buttons, inputs, small badges)
6px  - Medium (primary buttons, inputs)
8px  - Large (cards, containers)
12px - Extra large (badges)
50%  - Circular (status indicators, avatars)
```

---

## Responsiveness

### Breakpoints
```css
640px  - Mobile (phones)
768px  - Tablet
992px  - Small desktop
1200px - Large desktop
```

---

## Dark Mode

KeepUp uses a dark-first design philosophy for the monitoring dashboard.

### Dark Mode Colors
```css
Background:     #0F1923   (Abyss)
Surfaces:       #1A2332   (Command Navy)
Elevated:       #2C3E50   (Steel)
Text Primary:   #E8EDF2
Text Secondary: #9CA3AF
Primary:        #448AFF   (Electric Blue, slightly brighter for dark BG)
Primary Hover:  #82B1FF   (Spark)
```

Status colors remain the same — they are already designed for maximum contrast on dark surfaces.

---

## Accessibility

### Contrast Ratios (WCAG 2.1)
| Combination | Ratio | AA | AAA |
|---|---|---|---|
| #2979FF on white | 3.8:1 | Large text | - |
| #2962FF on white | 4.6:1 | All text | Large text |
| #1A2332 on white | 14.8:1 | All text | All text |
| White on #1A2332 | 14.8:1 | All text | All text |
| #00E676 on #1A2332 | 9.2:1 | All text | All text |
| #FF1744 on #1A2332 | 4.5:1 | All text | Large text |

**Note:** Use #2962FF (darker Electric Blue) for small body text on white backgrounds. Use #2979FF for buttons and large elements where the lower contrast ratio is acceptable.

---

## Consistency Rules

1. **NEVER use icons in buttons** — text only
2. **ALWAYS use standardized colors:**
   - View: Electric Blue (#2979FF)
   - Edit: Flux Yellow (#FFEA00)
   - Resolve: Sentinel Green (#00E676)
   - Toggle: Spark (#82B1FF)
   - Delete: Breach Red (#FF1744)
3. **ALWAYS use 4px-multiple spacing**
4. **ALWAYS use consistent border-radius**
5. **ALWAYS implement hover states**
6. **ALWAYS make UI responsive** (mobile-first, breakpoint at 768px)
7. **NEVER use brand color for status indicators** — green/red/yellow are reserved for semantic meaning

---

**Last updated**: 2026-04-01
**Version**: 3.0.0 — KeepUp rebrand (Navy + Electric Blue)
