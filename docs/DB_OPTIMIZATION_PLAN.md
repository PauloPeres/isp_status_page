# Database Optimization Plan — monitor_checks at Scale

> Created: 2026-03-28

## Problem

The monitor_checks table is the highest-volume table in the system. At SaaS scale (1000 orgs × 10 monitors × 1min checks), it generates 14.4M rows/day and 432M rows/month. Current implementation has: no composite indexes, N+1 queries (300 per dashboard load), broken badge uptime calculation, 32-bit PK that exhausts in 149 days, flat 30-day cleanup ignoring plan tiers, and no data aggregation strategy.

## Tasks

### TASK-DB-001: Add Composite Indexes
- **Status:** COMPLETED
- **Priority:** MUST-HAVE (immediate)
- **Description:** Add (monitor_id, checked_at DESC), (monitor_id, status, checked_at DESC), (organization_id, checked_at DESC). Drop redundant single-column indexes.

### TASK-DB-002: Fix BadgeService Bug
- **Status:** COMPLETED
- **Priority:** MUST-HAVE (bug fix)
- **Description:** BadgeService queries `created` instead of `checked_at` and `status='up'` instead of `status='success'`. Uptime always shows 100%.

### TASK-DB-003: Eliminate N+1 Queries
- **Status:** PENDING
- **Priority:** MUST-HAVE
- **Description:** DashboardController runs 300 queries per load (3 per monitor). Replace with single GROUP BY aggregates. Fix ChecksController stats bug (mutated query builder). Fix MonitorsController memory issue.

### TASK-DB-004: Create monitor_checks_rollup Table
- **Status:** PENDING
- **Priority:** MUST-HAVE
- **Description:** Rollup table with 5min/1hour/1day aggregations. ChecksAggregationService + AggregateChecksCommand for hourly cron. Replaces raw data for historical queries.

### TASK-DB-005: Plan-Aware Retention with Batched Deletes
- **Status:** PENDING
- **Priority:** MUST-HAVE
- **Description:** Rewrite CleanupCommand: per-org retention based on plan tier, batched 10K-row deletes, only delete already-aggregated data.
- **Depends on:** DB-004

### TASK-DB-006: PostgreSQL Table Partitioning
- **Status:** PENDING
- **Priority:** HIGH (defer for small/medium scale)
- **Description:** Weekly range partitions on checked_at. ManagePartitionsCommand for creating future/dropping expired partitions. BIGSERIAL PK.

### TASK-DB-007: Optimize Queries to Use Rollup Data
- **Status:** PENDING
- **Priority:** MUST-HAVE
- **Description:** UptimeCalculationService that routes queries to raw (last 24h) vs rollup (historical). Update Dashboard, Badges, SuperAdmin metrics.
- **Depends on:** DB-004

### TASK-DB-008: Redis Caching Layer
- **Status:** PENDING
- **Priority:** HIGH
- **Description:** Cache uptime (60s TTL), dashboard summary (30s TTL), badge SVGs (5min TTL). MonitorCacheService for key management.
- **Depends on:** DB-003, DB-007

### TASK-DB-009: Batch Insert for Check Results
- **Status:** PENDING
- **Priority:** NICE-TO-HAVE
- **Description:** Batch all check results into single INSERT. Reduces 10K inserts/cycle to 1.

### TASK-DB-010: PK Migration to BIGINT
- **Status:** COMPLETED
- **Priority:** MUST-HAVE
- **Description:** Change id from SERIAL (32-bit, max 2.1B) to BIGSERIAL (64-bit). Current PK exhausts in ~149 days at target scale.

### TASK-DB-011: Separate Error Details Table
- **Status:** PENDING
- **Priority:** NICE-TO-HAVE
- **Description:** Move error_message and details TEXT columns to companion table. Reduces main table heap by ~30%.

### TASK-DB-012: TimescaleDB Evaluation
- **Status:** PENDING
- **Priority:** NICE-TO-HAVE (future)
- **Description:** Evaluate TimescaleDB for automatic partitioning, continuous aggregates, and compression.

## Impact Summary

| Metric | Current | After Optimization |
|--------|---------|-------------------|
| Dashboard queries/load | ~300 (N+1) | 3-5 (aggregate) |
| Badge uptime accuracy | Broken | Correct |
| PK exhaustion | 149 days | Never (BIGINT) |
| Storage/month at scale | ~260 GB raw | ~20 GB raw + ~2 GB rollups |
| Cleanup performance | Single massive DELETE | Partition DROP or batched |

## Execution Order

Phase 1 (immediate): DB-001, DB-002, DB-010
Phase 2 (week 2): DB-003
Phase 3 (weeks 2-3): DB-004, DB-005, DB-007
Phase 4 (week 3): DB-008, DB-009
Phase 5 (week 4-5): DB-006
Phase 6 (future): DB-011, DB-012
