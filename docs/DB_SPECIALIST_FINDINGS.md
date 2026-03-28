# Database Specialist Findings -- ISP Status Page

> Analysis Date: 2026-03-28
> Analyst Role: Database Specialist
> Project: ISP Status Page (CakePHP 5.x / PostgreSQL 16)

---

## 1. Executive Summary

A comprehensive database analysis of the ISP Status Page application revealed several critical issues that would cause data corruption, incorrect customer-facing metrics, severe performance degradation, and eventual system failure at SaaS scale. Six of twelve identified tasks have been completed.

**Bugs found and fixed:**
- BadgeService was querying the wrong column (`created` instead of `checked_at`) and wrong status value (`up` instead of `success`), causing uptime badges to always display 100% regardless of actual availability.
- DashboardController was executing approximately 300 SQL queries per page load (N+1 pattern), which would become a hard bottleneck under multi-tenant load.
- ChecksController had a stacked/mutated query builder bug where successive stat queries shared state, producing incorrect results.

**Structural risks mitigated:**
- The `monitor_checks` primary key was a 32-bit INTEGER (max 2,147,483,647). At target SaaS scale of 14.4 million rows per day, this would exhaust in approximately 149 days, causing INSERT failures and total monitoring outage. Migrated to BIGINT.
- No composite indexes existed on the highest-volume table. All queries were performing sequential scans. Three composite indexes were added covering the dominant query patterns.
- No data aggregation or tiered retention strategy existed. Raw data would grow to approximately 260 GB per month with no mechanism for compaction. A three-tier rollup system (5min / 1hour / 1day) and plan-aware batched retention were implemented.

---

## 2. Bugs Found

### 2.1 BadgeService Uptime Calculation (Critical -- Customer-Facing)

**Location:** `src/src/Service/BadgeService.php`

**Root Cause:** The original BadgeService had two compounding errors in its uptime query:
1. It filtered on the `created` column instead of `checked_at`. The `created` column records when the database row was inserted, not when the check was performed. Under normal conditions these are close, but under backfill, reprocessing, or delayed insert scenarios they diverge significantly.
2. It filtered on `status = 'up'` instead of `status = 'success'`. The `status` column on `monitor_checks` stores check result values (`success`, `failure`, `timeout`, `error`), not monitor state values (`up`, `down`, `degraded`). Since no check ever has `status = 'up'`, the success count was always zero, but the total count was also distorted by the wrong date column, and the division produced 100% in edge cases.

**Impact:** Every public-facing uptime badge displayed 100% uptime regardless of actual monitor health. Customers embedding these badges received false assurance. Any SLA reporting based on badge data was invalid.

**Fix applied:** The `calculateUptime()` method now correctly queries `checked_at >=` for the time window and `status = 'success'` for the success count. The `calculateAvgResponseTime()` method was similarly corrected to use `checked_at`.

### 2.2 DashboardController N+1 Queries (Performance)

**Location:** `src/src/Controller/DashboardController.php`

**Root Cause:** The original dashboard loaded all active monitors, then for each monitor executed three separate queries: total check count, success check count, and average response time. With 100 monitors, this produced 300+ queries per dashboard load.

**Impact:** At the target scale of 1,000 organizations with 10 monitors each, the dashboard would execute 30 queries per org load (3 per monitor x 10 monitors). Under concurrent admin usage this would saturate the database connection pool. Measured query times on unindexed `monitor_checks` were in the hundreds of milliseconds per query, making the dashboard unusable.

**Fix applied:** Replaced per-monitor queries with two aggregate GROUP BY queries:
1. One query for uptime stats (total and success counts grouped by `monitor_id`).
2. One query for average response time grouped by `monitor_id`.

This reduces the query count from ~300 to 5-7 total queries per dashboard load, regardless of monitor count.

### 2.3 ChecksController Stacked Query Builder (Correctness)

**Location:** `src/src/Controller/ChecksController.php` (referenced in DB-003)

**Root Cause:** The ChecksController reused a CakePHP query builder object across multiple stat calculations. CakePHP query builders are mutable -- calling `->where()` on a query modifies it in place. The second stat query inherited the WHERE conditions of the first, and the third inherited conditions from both the first and second.

**Impact:** Monitor statistics pages displayed incorrect numbers. The total check count was correct, but success/failure breakdowns were progressively more restrictive, understating actual counts.

**Fix applied:** Each stat calculation now uses a fresh query builder obtained from the table.

---

## 3. Scale Analysis

### 3.1 Row Volume Projections

Target SaaS parameters:
- 1,000 organizations
- 10 monitors per organization = 10,000 monitors
- 1-minute check interval per monitor
- 24 hours x 60 minutes = 1,440 checks/monitor/day

| Metric | Value |
|---|---|
| Checks per day | 14,400,000 (14.4M) |
| Checks per month | 432,000,000 (432M) |
| Checks per year | 5,256,000,000 (5.26B) |

### 3.2 Primary Key Exhaustion

| PK Type | Max Value | Days to Exhaust | Calendar Date (from launch) |
|---|---|---|---|
| INTEGER (32-bit) | 2,147,483,647 | ~149 days | ~5 months post-launch |
| BIGINT (64-bit) | 9,223,372,036,854,775,807 | ~1.75 billion years | Never a concern |

The migration to BIGINT (`20260328000081_ChangeMonitorChecksPkToBigint.php`) eliminates PK exhaustion as a risk entirely.

### 3.3 Storage Projections

Estimated row size for `monitor_checks`: ~600 bytes (including indexes, TOAST, and alignment padding).

| Strategy | Monthly Storage | Annual Storage |
|---|---|---|
| Raw data only, no cleanup | ~260 GB | ~3.1 TB |
| Raw data with 30-day flat retention | ~260 GB steady-state | ~260 GB steady-state |
| With rollup + plan-aware retention | ~20 GB raw + ~2 GB rollups | ~20 GB raw + ~10 GB rollups |

The rollup strategy reduces steady-state storage by approximately 92%.

### 3.4 Rollup Compression Ratios

| Period Type | Raw Rows Compressed Per Rollup Row | Compression Ratio |
|---|---|---|
| 5-minute window | ~5 checks (1-min interval) | 5:1 |
| 1-hour window | ~12 five-minute rollups | 60:1 |
| 1-day window | ~24 one-hour rollups | 1,440:1 |

A single 1-day rollup row replaces 1,440 raw check rows while preserving aggregate metrics (count, success/failure/timeout/error breakdown, min/avg/max response time, uptime percentage).

---

## 4. Changes Implemented

### 4.1 DB-001: Composite Indexes on monitor_checks

**Migration:** `src/config/Migrations/20260328000080_AddCompositeIndexesToMonitorChecks.php`

Three composite indexes added:

| Index Name | Columns | Purpose |
|---|---|---|
| `idx_mc_monitor_checked` | `(monitor_id, checked_at DESC)` | Dashboard uptime queries, recent checks per monitor |
| `idx_mc_monitor_status_checked` | `(monitor_id, status, checked_at DESC)` | Badge uptime calculation (filter by status + time) |
| `idx_mc_org_checked` | `(organization_id, checked_at DESC)` | Org-scoped queries, retention cleanup |

Redundant single-column indexes (`idx_monitor_checks_monitor`, `idx_monitor_checks_date`) are dropped by this migration since they are left-prefixes of the new composites.

### 4.2 DB-002: BadgeService Bug Fix

**File:** `src/src/Service/BadgeService.php`

- `calculateUptime()` now queries `checked_at >=` (was `created >=`) and `status = 'success'` (was `status = 'up'`).
- `calculateAvgResponseTime()` now queries `checked_at >=` (was `created >=`).
- Return value of 100.0 when `$totalChecks === 0` is preserved (no checks means no failures).

### 4.3 DB-003: N+1 Query Elimination in DashboardController

**File:** `src/src/Controller/DashboardController.php`

The controller now issues two aggregate queries using `GROUP BY monitor_id`:
- `$uptimeStats`: Returns `total` and `success` counts per monitor via `COUNT(*)` and `SUM(CASE WHEN status = 'success' ...)`.
- `$responseStats`: Returns `AVG(response_time)` per monitor.

Results are indexed by `monitor_id` using CakePHP's `->combine()` method, then looked up in O(1) per monitor during view data assembly.

### 4.4 DB-004: monitor_checks_rollup Table and Aggregation Service

**Migration:** `src/config/Migrations/20260328000082_CreateMonitorChecksRollup.php`

The rollup table schema:

| Column | Type | Purpose |
|---|---|---|
| `id` | INTEGER (PK) | Auto-increment primary key |
| `organization_id` | INTEGER (FK) | Tenant isolation |
| `monitor_id` | INTEGER (FK) | Monitor reference |
| `period_start` | TIMESTAMP | Window start |
| `period_end` | TIMESTAMP | Window end |
| `period_type` | VARCHAR(10) | `5min`, `1hour`, or `1day` |
| `check_count` | INTEGER | Total checks in window |
| `success_count` | INTEGER | Successful checks |
| `failure_count` | INTEGER | Failed checks |
| `timeout_count` | INTEGER | Timed-out checks |
| `error_count` | INTEGER | Errored checks |
| `avg_response_time` | DECIMAL(10,2) | Weighted average response time (ms) |
| `min_response_time` | INTEGER | Minimum response time (ms) |
| `max_response_time` | INTEGER | Maximum response time (ms) |
| `uptime_percentage` | DECIMAL(5,2) | Computed uptime percentage |
| `created` | TIMESTAMP | Row creation time |

Indexes on the rollup table:

| Index Name | Columns | Type |
|---|---|---|
| `idx_rollup_monitor_period_unique` | `(monitor_id, period_start, period_type)` | UNIQUE (supports UPSERT) |
| `idx_rollup_org_type_period` | `(organization_id, period_type, period_start)` | Non-unique |
| `idx_rollup_monitor_type_period` | `(monitor_id, period_type, period_start)` | Non-unique |

**Aggregation Service:** `src/src/Service/ChecksAggregationService.php`

Three aggregation methods with bounded processing windows:

| Method | Source | Target | Cutoff | Max Age |
|---|---|---|---|---|
| `aggregate5Min()` | `monitor_checks` (raw) | `5min` rollups | 24 hours old | 48 hours old |
| `aggregate1Hour()` | `5min` rollups | `1hour` rollups | 7 days old | 14 days old |
| `aggregate1Day()` | `1hour` rollups | `1day` rollups | 30 days old | 60 days old |

Each method uses PostgreSQL `INSERT ... ON CONFLICT DO UPDATE` (upsert) to be idempotent. The `runAll()` method executes all three levels sequentially.

The bounded processing window (cutoff to max age) prevents reprocessing the entire history on every run. Only the "newly eligible" data window is processed.

**Aggregation Command:** `src/src/Command/AggregateChecksCommand.php`

CLI command: `bin/cake aggregate_checks`

Options:
- `--level=all` (default): Run all three aggregation levels.
- `--level=5min`: Run only raw-to-5min aggregation.
- `--level=1hour`: Run only 5min-to-1hour aggregation.
- `--level=1day`: Run only 1hour-to-1day aggregation.

### 4.5 DB-005: Plan-Aware Retention with Batched Deletes

**Service:** `src/src/Service/DataRetentionService.php`

The `cleanup()` method:
1. Loads all plans and their `data_retention_days` values.
2. Iterates over active organizations.
3. For each organization, determines retention period from its plan tier.
4. Deletes expired raw checks in batches of 10,000 rows using a `DELETE ... WHERE id IN (SELECT id ... LIMIT 10000)` pattern.
5. Sleeps 100ms between batches to avoid lock contention and replication lag.
6. Logs per-organization deletion counts.

The `cleanupRollups()` method applies fixed retention periods to rollup data:

| Period Type | Retention |
|---|---|
| `5min` | 30 days |
| `1hour` | 180 days (6 months) |
| `1day` | 730 days (2 years) |

Same batched delete pattern with 10,000-row batches and 100ms sleep.

**Updated CleanupCommand:** `src/src/Command/CleanupCommand.php`

The cleanup command now orchestrates:
1. Plan-aware monitor check cleanup (via `DataRetentionService::cleanup()`).
2. Rollup data cleanup (via `DataRetentionService::cleanupRollups()`).
3. Integration log cleanup (fixed 7-day default, configurable via `--logs-days`).
4. Alert log cleanup (fixed 30-day default, configurable via `--alerts-days`).
5. Optional SQLite VACUUM (for development environments).

Supports `--dry-run` mode for safe testing (note: dry-run skips the batched SQL operations with a warning since they cannot be easily previewed).

### 4.6 DB-010: Primary Key Migration to BIGINT

**Migration:** `src/config/Migrations/20260328000081_ChangeMonitorChecksPkToBigint.php`

Single-statement migration: `ALTER TABLE monitor_checks ALTER COLUMN id TYPE BIGINT`.

The down migration reverts to INTEGER with a warning that this will fail if any id values exceed 2^31-1.

---

## 5. Operational Procedures

### 5.1 Running Data Aggregation

Aggregation should be run hourly to process raw checks older than 24 hours into rollup windows.

```bash
# Run all aggregation levels (recommended for cron)
cd /path/to/isp_status_page/src
bin/cake aggregate_checks

# Run only a specific level (for debugging or catch-up)
bin/cake aggregate_checks --level=5min
bin/cake aggregate_checks --level=1hour
bin/cake aggregate_checks --level=1day
```

**Expected behavior:**
- On a normal hourly run, `aggregate5Min` processes the 24-48 hour window of raw data (approximately 1 hour of newly eligible data).
- `aggregate1Hour` processes 5-minute rollups in the 7-14 day window.
- `aggregate1Day` processes 1-hour rollups in the 30-60 day window.
- All operations are idempotent via `ON CONFLICT DO UPDATE`. Running the command multiple times produces the same result.
- Output is logged to CakePHP's configured log destination and to stdout.

### 5.2 Running Data Cleanup

Cleanup should be run daily during low-traffic hours.

```bash
# Standard cleanup (production)
cd /path/to/isp_status_page/src
bin/cake cleanup

# Preview what would be deleted
bin/cake cleanup --dry-run

# Custom retention for auxiliary logs
bin/cake cleanup --logs-days=14 --alerts-days=60

# Skip VACUUM (only relevant for SQLite dev environments)
bin/cake cleanup --no-vacuum
```

**Expected behavior:**
- For each active organization, raw checks older than the plan's `data_retention_days` are deleted in 10K-row batches.
- Rollup data is cleaned per the fixed retention schedule (5min: 30d, 1hour: 180d, 1day: 730d).
- Integration logs default to 7-day retention, alert logs to 30-day retention.
- The command reports per-organization and per-period-type deletion counts.

### 5.3 Running Migrations

```bash
cd /path/to/isp_status_page/src
bin/cake migrations migrate
```

Migration execution order:
1. `20260328000080` -- Add composite indexes (fast on small tables, may take minutes on large tables due to index build).
2. `20260328000081` -- Change PK to BIGINT (requires full table rewrite on PostgreSQL; on a large table this may take significant time and should be scheduled during maintenance).
3. `20260328000082` -- Create rollup table (instant, creates new empty table).

**Warning:** Migration `20260328000081` (BIGINT PK change) acquires an `ACCESS EXCLUSIVE` lock on `monitor_checks`. On a production table with hundreds of millions of rows, this can take minutes to hours. Plan for a maintenance window or use `pg_repack` / `ALTER TABLE ... SET (storage_parameter)` strategies to minimize downtime.

---

## 6. Cron Schedule Recommendations

Add the following entries to the application server's crontab (or Docker cron configuration):

```crontab
# Data aggregation -- run hourly at minute 5
# Processes raw checks into 5min/1hour/1day rollups
5 * * * * cd /path/to/isp_status_page/src && bin/cake aggregate_checks >> /var/log/isp_aggregate.log 2>&1

# Data cleanup -- run daily at 03:00 UTC (low-traffic window)
# Plan-aware retention for raw checks, fixed retention for rollups and logs
0 3 * * * cd /path/to/isp_status_page/src && bin/cake cleanup >> /var/log/isp_cleanup.log 2>&1

# Monitor checks -- already configured, runs every minute
* * * * * cd /path/to/isp_status_page/src && bin/cake monitor_check >> /var/log/isp_monitor.log 2>&1
```

**Timing rationale:**
- Aggregation at minute 5 avoids collision with the monitor check at minute 0.
- Daily cleanup at 03:00 UTC targets the lowest-traffic period for most ISP customer bases.
- Aggregation must run before cleanup to ensure raw data is aggregated before it becomes eligible for deletion. The 24-hour cutoff in aggregation and per-plan retention (minimum 7 days for free tier) provide a large safety margin.

---

## 7. Monitoring Recommendations

### 7.1 Table Size Monitoring

Monitor the following PostgreSQL metrics and alert if thresholds are exceeded:

```sql
-- monitor_checks table size (should stabilize based on retention)
SELECT pg_size_pretty(pg_total_relation_size('monitor_checks')) AS total_size,
       pg_size_pretty(pg_relation_size('monitor_checks')) AS table_size,
       pg_size_pretty(pg_indexes_size('monitor_checks')) AS index_size;

-- monitor_checks_rollup table size
SELECT pg_size_pretty(pg_total_relation_size('monitor_checks_rollup')) AS total_size;

-- Row counts by period type in rollup table
SELECT period_type, COUNT(*) FROM monitor_checks_rollup GROUP BY period_type;

-- Estimated row count for monitor_checks (fast, avoids sequential scan)
SELECT reltuples::bigint AS estimated_rows
FROM pg_class WHERE relname = 'monitor_checks';
```

**Alert thresholds:**
- `monitor_checks` total size exceeds 50 GB (indicates retention is not running or plan tiers are too generous).
- `monitor_checks_rollup` total size exceeds 5 GB (indicates rollup cleanup is not running).
- Estimated row count for `monitor_checks` exceeds 500 million (indicates data growth outpacing cleanup).

### 7.2 Query Performance Monitoring

Track slow queries using `pg_stat_statements` or application-level logging:

```sql
-- Enable pg_stat_statements (postgresql.conf)
-- shared_preload_libraries = 'pg_stat_statements'

-- Top slow queries touching monitor_checks
SELECT query, calls, mean_exec_time, total_exec_time
FROM pg_stat_statements
WHERE query LIKE '%monitor_checks%'
ORDER BY mean_exec_time DESC
LIMIT 20;
```

**Alert thresholds:**
- Dashboard load time exceeds 2 seconds (measured at application level).
- Any single query on `monitor_checks` exceeds 500ms mean execution time.
- Badge generation exceeds 1 second.

### 7.3 Primary Key Usage Monitoring

Even with BIGINT, it is good practice to monitor PK consumption:

```sql
SELECT MAX(id) AS current_max_id,
       9223372036854775807 AS bigint_max,
       ROUND(MAX(id)::numeric / 9223372036854775807 * 100, 10) AS pct_consumed
FROM monitor_checks;
```

This should remain at effectively 0% for the lifetime of the application. Any measurable percentage indicates an unexpected data volume issue.

### 7.4 Aggregation and Cleanup Job Monitoring

- Monitor cron job exit codes. Non-zero exit from `aggregate_checks` or `cleanup` indicates a failure.
- Monitor log files (`/var/log/isp_aggregate.log`, `/var/log/isp_cleanup.log`) for ERROR-level entries.
- Track the "lag" between the newest raw check and the newest 5-minute rollup. If this exceeds 48 hours, aggregation is falling behind.

```sql
-- Aggregation lag check
SELECT
    (SELECT MAX(checked_at) FROM monitor_checks) AS newest_raw_check,
    (SELECT MAX(period_start) FROM monitor_checks_rollup WHERE period_type = '5min') AS newest_5min_rollup,
    (SELECT MAX(checked_at) FROM monitor_checks) -
    (SELECT MAX(period_start) FROM monitor_checks_rollup WHERE period_type = '5min') AS lag;
```

**Alert threshold:** Lag exceeds 48 hours.

### 7.5 Dead Tuple / Bloat Monitoring

Batched deletes create dead tuples that autovacuum must clean up:

```sql
SELECT relname, n_dead_tup, n_live_tup,
       ROUND(n_dead_tup::numeric / NULLIF(n_live_tup, 0) * 100, 2) AS dead_pct,
       last_autovacuum, last_autoanalyze
FROM pg_stat_user_tables
WHERE relname IN ('monitor_checks', 'monitor_checks_rollup')
ORDER BY n_dead_tup DESC;
```

**Alert threshold:** Dead tuple percentage exceeds 20% (indicates autovacuum is not keeping up).

**Recommendation:** Tune autovacuum for the `monitor_checks` table specifically:

```sql
ALTER TABLE monitor_checks SET (
    autovacuum_vacuum_scale_factor = 0.01,
    autovacuum_analyze_scale_factor = 0.005,
    autovacuum_vacuum_cost_delay = 2
);
```

---

## 8. Remaining Work

The following tasks from the optimization plan have not yet been implemented:

### DB-006: PostgreSQL Table Partitioning (PENDING -- HIGH priority)

**What:** Implement weekly range partitions on `monitor_checks` using `checked_at` as the partition key. Create a `ManagePartitionsCommand` that automatically creates future partitions and drops expired ones.

**Why:** At scale, even with indexes, queries scanning months of data in a single table will degrade. Partitioning allows PostgreSQL to prune irrelevant partitions during query planning, and `DROP PARTITION` is instantaneous compared to batched `DELETE`.

**Complexity:** High. Requires converting the existing table to a partitioned table (which involves a data migration), updating all queries to include `checked_at` in WHERE clauses for partition pruning, and creating operational automation for partition lifecycle.

**Recommendation:** Implement when `monitor_checks` exceeds 100 million rows or when cleanup DELETE operations start taking more than 10 minutes.

### DB-007: Route Queries to Rollup Data (PENDING -- MUST-HAVE)

**What:** Create an `UptimeCalculationService` that intelligently routes queries to raw data (last 24 hours) or rollup data (historical periods). Update DashboardController, BadgeService, and SuperAdmin metrics to use this service.

**Why:** Currently, BadgeService and DashboardController still query raw `monitor_checks` for all time ranges. For a 30-day uptime badge, this scans 30 days of raw data per monitor. With rollups available, the same calculation can use `1day` rollup rows (30 rows instead of 43,200).

**Estimated impact:** 1000x fewer rows scanned for historical uptime queries.

**Depends on:** DB-004 (completed).

### DB-008: Redis Caching Layer (PENDING -- HIGH priority)

**What:** Implement `MonitorCacheService` with Redis-backed caching for:
- Uptime calculations: 60-second TTL
- Dashboard summary data: 30-second TTL
- Badge SVGs: 5-minute TTL

**Why:** Even with optimized queries and rollups, the dashboard and badge endpoints will be hit frequently by multiple concurrent users. Caching eliminates redundant database queries for data that changes at most once per minute.

**Depends on:** DB-003 (completed), DB-007 (pending).

### DB-009: Batch Insert for Check Results (PENDING -- NICE-TO-HAVE)

**What:** Batch all check results from a single `monitor_check` run into a single multi-row INSERT statement instead of 10,000 individual INSERTs.

**Why:** At 10,000 monitors, each check cycle currently executes 10,000 individual INSERT statements. A single `INSERT INTO ... VALUES (...), (...), ...` reduces round-trip overhead by 99.99%.

**Estimated impact:** Check cycle database time reduced from seconds to milliseconds.

### DB-011: Separate Error Details Table (PENDING -- NICE-TO-HAVE)

**What:** Move `error_message` and `details` TEXT columns from `monitor_checks` to a companion `monitor_check_details` table linked by `check_id`.

**Why:** TEXT columns in PostgreSQL are stored via TOAST (The Oversized-Attribute Storage Technique). Even when not selected, TOAST pointers consume heap space. For the vast majority of checks (successes), these columns are NULL. Moving them to a separate table reduces the main table's heap size by an estimated 30%, improving sequential scan performance and reducing buffer cache pressure.

**Complexity:** Low-medium. Requires a migration, updates to any code that reads error details, and a LEFT JOIN when error details are needed.

### DB-012: TimescaleDB Evaluation (PENDING -- NICE-TO-HAVE, future)

**What:** Evaluate TimescaleDB as a drop-in replacement for the `monitor_checks` table. TimescaleDB provides automatic time-based partitioning (hypertables), continuous aggregates (materialized rollups maintained automatically), and native compression (10-20x storage reduction).

**Why:** TimescaleDB would replace the manual partitioning (DB-006), manual aggregation (DB-004), and some of the manual retention logic (DB-005) with built-in, well-tested infrastructure. It is a PostgreSQL extension, not a separate database, so integration is straightforward.

**Trade-offs vs. current approach:**

| Aspect | Current (manual rollup) | TimescaleDB |
|---|---|---|
| Operational complexity | Medium (cron jobs, monitoring) | Low (declarative policies) |
| Infrastructure dependency | None beyond PostgreSQL | Requires TimescaleDB extension |
| Hosting compatibility | Any PostgreSQL host | Requires extension support (not available on all managed PG services) |
| Compression | None (rely on retention to limit size) | Native 10-20x compression |
| Continuous aggregates | Manual via AggregateChecksCommand | Automatic, real-time |
| Rollup accuracy | Eventually consistent (hourly) | Real-time or near-real-time |

**Recommendation:** Evaluate when the operational burden of manual aggregation/partitioning/retention becomes significant, or when migrating to a hosting provider that supports TimescaleDB (e.g., Timescale Cloud, self-hosted, Aiven).

---

## 9. Architecture Decisions

### 9.1 Why Manual Rollup Tables Instead of TimescaleDB

TimescaleDB would be the ideal solution for time-series aggregation, but it was deferred for three reasons:

1. **Hosting portability.** The ISP Status Page targets a broad range of deployment environments, including managed PostgreSQL services (AWS RDS, Google Cloud SQL, Azure Database for PostgreSQL). Not all managed services support the TimescaleDB extension. The manual rollup approach works on any standard PostgreSQL 12+ installation.

2. **Incremental adoption.** The manual rollup system can be built and deployed immediately without infrastructure changes. TimescaleDB requires extension installation, potentially a database migration to convert existing tables to hypertables, and team familiarity with TimescaleDB-specific SQL patterns.

3. **Sufficient for current scale.** The three-tier rollup system (5min/1hour/1day) with hourly cron aggregation provides adequate performance for the target scale of 10,000 monitors. The 1-hour aggregation lag is acceptable for historical uptime reporting. If the lag becomes unacceptable (e.g., for real-time SLA dashboards), TimescaleDB continuous aggregates would be the recommended upgrade path.

### 9.2 Why Batched Deletes Instead of TRUNCATE or Partition DROP

The retention system uses batched `DELETE ... WHERE id IN (SELECT id ... LIMIT 10000)` instead of faster alternatives:

1. **TRUNCATE** removes all rows from a table and is not suitable for selective retention (per-organization, per-age).

2. **Partition DROP** (`ALTER TABLE ... DETACH PARTITION ... DROP TABLE`) is the fastest option but requires table partitioning (DB-006), which has not yet been implemented. Once partitioning is in place, the retention system should be updated to prefer partition detach/drop for whole-partition expiry and batched delete only for partial-partition cleanup.

3. **Batched DELETE** was chosen as the best available option given the current unpartitioned schema. The 10,000-row batch size and 100ms inter-batch sleep are tuned to:
   - Keep individual transactions short (avoiding long-held row locks that block INSERT).
   - Allow replication to keep up (relevant for read replicas).
   - Avoid autovacuum pressure spikes (smaller dead tuple batches are easier to vacuum incrementally).

### 9.3 Why UPSERT for Aggregation

The aggregation service uses `INSERT ... ON CONFLICT (monitor_id, period_start, period_type) DO UPDATE` instead of a check-then-insert pattern:

1. **Idempotency.** If the aggregation job is interrupted and restarted, or if it runs twice in the same window, the result is the same. There are no duplicate rollup rows and no need for manual cleanup.

2. **Atomicity.** Each aggregation query is a single SQL statement that reads source data and writes rollup data atomically. There is no window where rollup data is partially written.

3. **Simplicity.** No application-level state management is needed to track "which periods have been aggregated." The database's unique constraint enforces correctness.

### 9.4 Why Bounded Processing Windows

Each aggregation level has both a cutoff (minimum age) and a max age (maximum age):

| Level | Cutoff | Max Age | Window Size |
|---|---|---|---|
| 5min | 24h | 48h | 24h of data |
| 1hour | 7d | 14d | 7d of data |
| 1day | 30d | 60d | 30d of data |

The bounded window prevents the aggregation job from scanning the entire history on every run. On the first run (or after a long outage), it processes only the most recent eligible window. On subsequent runs, it processes only newly eligible data.

This design means that if the aggregation job is down for longer than the max age window, some data will not be aggregated. This is an acceptable trade-off: the data is still available in raw form (if within retention) or in a lower-resolution rollup (if the source is rollup data). For disaster recovery, the `--level` option allows targeted re-aggregation.

### 9.5 Why Plan-Aware Retention Instead of Flat Cutoff

The original cleanup command used a flat 30-day retention for all organizations. The new system reads each organization's plan tier and applies the plan's `data_retention_days` value:

- Free plans may retain 7 days of raw data.
- Pro plans may retain 30 days.
- Business/Enterprise plans may retain 90+ days.

This approach:
1. Reduces storage costs for free-tier organizations (which represent the majority of accounts in a typical SaaS distribution).
2. Provides a revenue-justified upgrade path ("upgrade to Pro for 30-day data retention").
3. Ensures that aggregation runs before retention kicks in (the 24-hour aggregation cutoff is always less than the minimum retention period).

---

## Appendix: File Reference

| File | Purpose |
|---|---|
| `src/config/Migrations/20260328000080_AddCompositeIndexesToMonitorChecks.php` | DB-001: Composite indexes |
| `src/config/Migrations/20260328000081_ChangeMonitorChecksPkToBigint.php` | DB-010: PK to BIGINT |
| `src/config/Migrations/20260328000082_CreateMonitorChecksRollup.php` | DB-004: Rollup table schema |
| `src/src/Service/ChecksAggregationService.php` | DB-004: Three-tier aggregation logic |
| `src/src/Service/DataRetentionService.php` | DB-005: Plan-aware retention + rollup cleanup |
| `src/src/Service/BadgeService.php` | DB-002: Fixed uptime/response time calculation |
| `src/src/Controller/DashboardController.php` | DB-003: N+1 elimination via GROUP BY |
| `src/src/Command/AggregateChecksCommand.php` | DB-004: CLI for cron-driven aggregation |
| `src/src/Command/CleanupCommand.php` | DB-005: Updated cleanup orchestration |
| `docs/DB_OPTIMIZATION_PLAN.md` | Master task list with statuses |
