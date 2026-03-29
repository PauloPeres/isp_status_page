# Angular + Ionic Migration Plan

> Created: 2026-03-29
> Status: PLANNING

## Overview

Transform the CakePHP monolith into a modern SPA architecture:
- **CakePHP** → pure API backend (REST + SSE) + landing/SEO pages
- **Angular 19 + Ionic 8** → frontend SPA + native mobile via Capacitor
- **Coexistence** during migration — both old PHP and new Angular work simultaneously

## Architecture Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| API Version | v2 (JWT) alongside v1 (API keys) | SPA needs user identity, v1 is machine-to-machine |
| Auth | JWT (15min access + 7d refresh in httpOnly cookie) | Standard for SPAs, firebase/php-jwt already installed |
| State Management | Angular Signals (not NgRx) | Less boilerplate, server-driven state |
| Real-time | SSE (Server-Sent Events) | Simpler than WebSocket, works over HTTP |
| Repo Structure | Monorepo (frontend/ in same repo) | Shared OpenAPI spec, single CI/CD |
| Build Output | frontend/dist → src/webroot/app/ | CakePHP serves SPA at /app/* |

## What Stays in CakePHP (Server-Rendered)

- Landing page (`/`)
- Public status page (`/status`, `/s/{slug}`)
- Registration + email verification
- Password reset
- Badges, widgets, RSS feed
- API documentation
- Webhooks

## What Moves to Angular

ALL admin functionality (~20 feature areas, ~110 API endpoints)

## Task Breakdown — 54 Tasks

### Phase 1: Backend API Foundation (6 tasks)
- TASK-NG-001: JWT Authentication Service + Middleware -- COMPLETED (2026-03-29)
- TASK-NG-002: API v2 Base Controller + Auth Endpoints (login, refresh, logout, me, switch-org) -- COMPLETED (2026-03-29)
- TASK-NG-003: Dashboard API (summary, uptime, response-times, recent) -- COMPLETED (2026-03-29)
- TASK-NG-004: Monitors API v2 (CRUD + bulk + import) -- COMPLETED (2026-03-29)
- TASK-NG-005: Incidents API v2 (CRUD + acknowledge + timeline) -- COMPLETED (2026-03-29)
- TASK-NG-006: Integrations API v2 (CRUD + test)

### Phase 1b: More API Endpoints (8 tasks)
- TASK-NG-007: Alert Rules + Escalation Policies API
- TASK-NG-008: SLA API (CRUD + report + export)
- TASK-NG-009: Settings + Billing API
- TASK-NG-010: Team Management API (Users + Invitations + API Keys)
- TASK-NG-011: Reports + Scheduled Reports API
- TASK-NG-012: Maintenance Windows + Status Pages API
- TASK-NG-013: 2FA + Activity Log + Organizations API
- TASK-NG-014: Super Admin API (7 controllers)

### Phase 1c: Infrastructure (2 tasks)
- TASK-NG-015: SSE Real-Time Events Endpoint
- TASK-NG-016: OpenAPI v2 Specification

### Phase 2: Angular Project Setup (7 tasks)
- TASK-NG-020: Angular + Ionic + Capacitor initialization -- COMPLETED
- TASK-NG-021: Core Module (auth service, JWT interceptor, guards) -- COMPLETED (2026-03-29)
- TASK-NG-022: API Service + TypeScript models -- COMPLETED (2026-03-29)
- TASK-NG-023: Design System / Ionic Theme (Indigo palette, fonts) -- COMPLETED (2026-03-29)
- TASK-NG-024: App Shell (layout, sidebar, navbar) -- COMPLETED (2026-03-29)
- TASK-NG-025: Auth Module (login + 2FA verify pages) -- COMPLETED (2026-03-29)
- TASK-NG-026: Routing Configuration (lazy-loaded feature routes) -- COMPLETED (2026-03-29)

### Phase 3: Feature Modules (19 tasks)
- TASK-NG-030: Dashboard -- COMPLETED (2026-03-29)
- TASK-NG-031: Monitors (largest — dynamic forms for 9 monitor types) -- COMPLETED (2026-03-29)
- TASK-NG-032: Incidents + Timeline -- COMPLETED (2026-03-29)
- TASK-NG-033: Checks -- COMPLETED (2026-03-29)
- TASK-NG-034: Integrations
- TASK-NG-035: Alert Rules
- TASK-NG-036: Escalation Policies
- TASK-NG-037: SLA
- TASK-NG-038: Settings
- TASK-NG-039: Billing + Credits
- TASK-NG-040: Team (Users + Invitations)
- TASK-NG-041: API Keys
- TASK-NG-042: Reports
- TASK-NG-043: Scheduled Reports
- TASK-NG-044: Maintenance Windows
- TASK-NG-045: Status Pages Management
- TASK-NG-046: Two-Factor Auth
- TASK-NG-047: Activity Log
- TASK-NG-048: Super Admin (largest — 9 sub-pages)

### Phase 4: Integration + Cutover (5 tasks)
- TASK-NG-050: CakePHP catch-all route for /app/*
- TASK-NG-051: CORS Configuration
- TASK-NG-052: Build + Deploy Pipeline
- TASK-NG-053: Capacitor Native Build (iOS + Android)
- TASK-NG-054: Admin URL Redirects

## Parallelization

Sprint 1: 2 agents (JWT + Angular init)
Sprint 2: 3 agents (API base + Core module + Design)
Sprint 3: 7 agents (API endpoints + Layout)
Sprint 4: 3 agents (SSE + OpenAPI + CORS)
Sprint 5: 11 agents (all feature modules — max parallel)
Sprint 6: 3 agents (build + native + cutover)

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | CakePHP 5.x + PHP 8.4 |
| Database | PostgreSQL 16 |
| Cache/Queue | Redis 7 |
| Frontend | Angular 19 + Ionic 8 |
| Mobile | Capacitor |
| State | Angular Signals |
| Charts | ng2-charts (Chart.js wrapper) |
| Icons | Ionicons + Lucide |
| Auth | JWT (firebase/php-jwt) |
| Real-time | SSE (Server-Sent Events) |
| CI/CD | Docker multi-stage build |
