# TimescaleDB Evaluation for ISP Status Page

> Created: 2026-03-27 | TASK-DB-012

## 1. What Is TimescaleDB?

TimescaleDB is a PostgreSQL extension purpose-built for time-series data. It runs as a native extension inside PostgreSQL 14-16, so it is fully compatible with standard SQL, existing tools, and all PostgreSQL ecosystem libraries (psycopg2, PDO, CakePHP ORM). There is no separate server process -- it enhances the PostgreSQL instance in place.

Key primitives:

- **Hypertables** -- transparent partitioning of a regular table by a time column. The user writes to a single table name; TimescaleDB manages chunk creation, pruning, and lifecycle.
- **Continuous aggregates** -- materialized views that refresh incrementally. Only new data is re-aggregated on each refresh, making them much cheaper than full-table rollup queries.
- **Compression** -- native columnar compression on older chunks with 10-20x storage savings, queryable in place without decompression.
- **Retention policies** -- declarative rules that automatically drop or compress chunks older than a threshold.

## 2. How TimescaleDB Would Replace Our Manual Approach

The current codebase implements several custom services to manage the high-volume `monitor_checks` table. TimescaleDB replaces each of them with a built-in primitive.

### 2.1 Hypertables Replace TASK-DB-006 (Partitioning)

**Current approach:** TASK-DB-006 proposes weekly range partitions on `checked_at`, a `ManagePartitionsCommand` for creating future partitions and dropping expired ones, plus a migration to BIGSERIAL PK.

**TimescaleDB equivalent:**

```sql
SELECT create_hypertable('monitor_checks', 'checked_at',
       chunk_time_interval => INTERVAL '1 week');
```

One SQL statement replaces the entire partition management command. Chunk creation, naming, and metadata are automatic. Queries that filter on `checked_at` benefit from chunk exclusion (equivalent to partition pruning) with zero application code.

### 2.2 Continuous Aggregates Replace TASK-DB-004 (Rollup Table)

**Current approach:** `monitor_checks_rollup` table, `ChecksAggregationService`, `AggregateChecksCommand` run via hourly cron, manual tracking of which periods have been aggregated.

**TimescaleDB equivalent:**

```sql
-- 5-minute aggregate
CREATE MATERIALIZED VIEW monitor_checks_5min
WITH (timescaledb.continuous) AS
SELECT
    time_bucket('5 minutes', checked_at) AS bucket,
    monitor_id,
    organization_id,
    count(*) AS check_count,
    count(*) FILTER (WHERE status = 'success') AS success_count,
    count(*) FILTER (WHERE status = 'failure') AS failure_count,
    avg(response_time) AS avg_response_time,
    min(response_time) AS min_response_time,
    max(response_time) AS max_response_time
FROM monitor_checks
GROUP BY bucket, monitor_id, organization_id;

-- Refresh policy: keep it up to date within 10 minutes
SELECT add_continuous_aggregate_policy('monitor_checks_5min',
    start_offset => INTERVAL '1 day',
    end_offset   => INTERVAL '10 minutes',
    schedule_interval => INTERVAL '5 minutes');
```

Repeat for 1-hour and 1-day buckets. The `AggregateChecksCommand` and `ChecksAggregationService` become unnecessary.

### 2.3 Retention Policies Replace TASK-DB-005 (Cleanup)

**Current approach:** `CleanupCommand` with per-org retention logic, batched 10K-row deletes, dependency on aggregation having completed first.

**TimescaleDB equivalent:**

```sql
-- Drop raw data older than 30 days (entire chunks, instant)
SELECT add_retention_policy('monitor_checks', INTERVAL '30 days');

-- Compress data older than 7 days (10-20x storage savings)
SELECT add_compression_policy('monitor_checks', INTERVAL '7 days');
```

Chunk drops are metadata-only operations (no row-by-row DELETE), so they complete in milliseconds regardless of data volume. Per-org retention with different plan tiers would still need application logic, but the underlying delete is far cheaper.

### 2.4 Compression Provides 10-20x Storage Savings

TimescaleDB's native columnar compression typically achieves 10-20x compression on time-series data. For our workload:

| Metric | Without compression | With compression |
|--------|-------------------|-----------------|
| Raw storage/month (target scale) | ~260 GB | ~15-25 GB |
| Query performance on compressed data | N/A | Comparable (transparent decompression) |
| Compression overhead | N/A | Background job, configurable schedule |

Compressed chunks remain fully queryable via standard SQL. Writes to compressed chunks trigger a small transparent decompression/recompression cycle, but since we only write recent data (which stays uncompressed), this is not a concern.

## 3. Pros

- **Simpler codebase.** Remove `ChecksAggregationService`, `AggregateChecksCommand`, `ManagePartitionsCommand`, and the manual partition/retention logic in `CleanupCommand`. Estimated removal of 500-800 lines of application code.
- **Better query performance.** Chunk exclusion prunes irrelevant time ranges automatically. Compressed chunks reduce I/O. The query planner is time-series-aware.
- **Real-time continuous aggregates.** With `timescaledb.materialized_only = false`, continuous aggregates serve a union of materialized data and real-time data from recent unprocessed rows. Dashboards always show up-to-the-second metrics without waiting for the next aggregation cycle.
- **Automatic chunk management.** No cron job to create future partitions or drop old ones. TimescaleDB handles this internally.
- **Built-in compression.** 10-20x storage savings with no application changes. Compressed data is still queryable.
- **Battle-tested at scale.** TimescaleDB is used in production by organizations handling billions of rows per day. It is well-documented and actively maintained.

## 4. Cons

- **External dependency.** TimescaleDB must be installed as a PostgreSQL extension. It is not bundled with vanilla PostgreSQL. Package installation is straightforward on most Linux distributions but adds a dependency to track.
- **Docker image change.** The current `postgres:16` Docker image must be replaced with `timescale/timescaledb:latest-pg16` (or `timescaledb-ha` for high availability). This is a one-line change in `docker-compose.yml` but affects the infrastructure baseline.
- **Slightly more complex development setup.** Every developer needs the TimescaleDB extension. The Docker image handles this, but local/native PostgreSQL installations need the extension installed separately.
- **PostgreSQL lock-in.** TimescaleDB only works with PostgreSQL. However, the project already uses PostgreSQL 16 as its primary database, so this is not a new constraint. SQLite (used for tests) would not support TimescaleDB features -- test strategy would need adjustment.
- **License considerations.** TimescaleDB Community Edition is source-available under the Timescale License (TSL). It is free to use but not OSI-approved open source. The Apache 2.0 licensed subset covers core hypertable functionality but not continuous aggregates or compression. Evaluate license compatibility with deployment requirements.
- **Learning curve.** The team needs to understand hypertable concepts, chunk intervals, continuous aggregate refresh policies, and compression settings. Misconfigured chunk intervals can degrade performance.

## 5. Migration Path

### Step 1: Install TimescaleDB Extension

```yaml
# docker-compose.yml
services:
  db:
    image: timescale/timescaledb:latest-pg16
    # ... rest of config unchanged
```

```sql
-- Run once after container starts
CREATE EXTENSION IF NOT EXISTS timescaledb;
```

### Step 2: Convert monitor_checks to Hypertable

```sql
-- Requires table to be empty or use migrate_data => true
SELECT create_hypertable('monitor_checks', 'checked_at',
       chunk_time_interval => INTERVAL '1 week',
       migrate_data => true);
```

Note: `migrate_data => true` rewrites existing data into chunks. For tables with millions of rows, schedule during a maintenance window (takes minutes, not hours).

### Step 3: Create Continuous Aggregates

```sql
-- 5-minute buckets
CREATE MATERIALIZED VIEW monitor_checks_5min
WITH (timescaledb.continuous) AS
SELECT
    time_bucket('5 minutes', checked_at) AS bucket,
    monitor_id, organization_id,
    count(*) AS check_count,
    count(*) FILTER (WHERE status = 'success') AS success_count,
    count(*) FILTER (WHERE status = 'failure') AS failure_count,
    avg(response_time) AS avg_response_time,
    min(response_time) AS min_response_time,
    max(response_time) AS max_response_time
FROM monitor_checks
GROUP BY bucket, monitor_id, organization_id;

-- 1-hour buckets
CREATE MATERIALIZED VIEW monitor_checks_1hour
WITH (timescaledb.continuous) AS
SELECT
    time_bucket('1 hour', checked_at) AS bucket,
    monitor_id, organization_id,
    count(*) AS check_count,
    count(*) FILTER (WHERE status = 'success') AS success_count,
    count(*) FILTER (WHERE status = 'failure') AS failure_count,
    avg(response_time) AS avg_response_time,
    min(response_time) AS min_response_time,
    max(response_time) AS max_response_time
FROM monitor_checks
GROUP BY bucket, monitor_id, organization_id;

-- 1-day buckets
CREATE MATERIALIZED VIEW monitor_checks_1day
WITH (timescaledb.continuous) AS
SELECT
    time_bucket('1 day', checked_at) AS bucket,
    monitor_id, organization_id,
    count(*) AS check_count,
    count(*) FILTER (WHERE status = 'success') AS success_count,
    count(*) FILTER (WHERE status = 'failure') AS failure_count,
    avg(response_time) AS avg_response_time,
    min(response_time) AS min_response_time,
    max(response_time) AS max_response_time
FROM monitor_checks
GROUP BY bucket, monitor_id, organization_id;

-- Refresh policies
SELECT add_continuous_aggregate_policy('monitor_checks_5min',
    start_offset => INTERVAL '1 day', end_offset => INTERVAL '10 minutes',
    schedule_interval => INTERVAL '5 minutes');

SELECT add_continuous_aggregate_policy('monitor_checks_1hour',
    start_offset => INTERVAL '7 days', end_offset => INTERVAL '1 hour',
    schedule_interval => INTERVAL '1 hour');

SELECT add_continuous_aggregate_policy('monitor_checks_1day',
    start_offset => INTERVAL '30 days', end_offset => INTERVAL '1 day',
    schedule_interval => INTERVAL '1 day');
```

### Step 4: Add Retention and Compression Policies

```sql
-- Compress chunks older than 7 days
ALTER TABLE monitor_checks SET (
    timescaledb.compress,
    timescaledb.compress_segmentby = 'monitor_id, organization_id',
    timescaledb.compress_orderby = 'checked_at DESC'
);

SELECT add_compression_policy('monitor_checks', INTERVAL '7 days');

-- Drop raw data older than 90 days (continuous aggregates retain historical summaries)
SELECT add_retention_policy('monitor_checks', INTERVAL '90 days');
```

### Step 5: Remove Manual Aggregation Code

After verifying continuous aggregates produce correct results:

1. Update `DashboardController`, `BadgeService`, and `ChecksController` to query continuous aggregate views instead of `monitor_checks_rollup`.
2. Remove `ChecksAggregationService` and `AggregateChecksCommand`.
3. Remove `ManagePartitionsCommand` (if implemented).
4. Simplify `CleanupCommand` to only handle per-org tier overrides (TimescaleDB handles the default policy).
5. Drop the `monitor_checks_rollup` table once all consumers have been migrated.

## 6. Recommendation

**Evaluate TimescaleDB after the platform reaches 1M+ rows/day in monitor_checks.**

At the current small/medium scale, the manual approach (composite indexes, rollup table, batched cleanup, batch inserts) works well and avoids adding an external dependency. The code is straightforward, testable with SQLite, and does not require specialized infrastructure.

Once the platform reaches 1M+ rows/day (approximately 700+ monitors checking every minute), the operational overhead of manual partitioning, aggregation cron jobs, and cleanup batching will become significant. At that point, TimescaleDB provides a clear return on investment:

- Eliminates 3-4 background commands and services
- Provides automatic compression (10-20x storage savings)
- Offers real-time continuous aggregates with no cron dependency
- Scales to billions of rows with minimal configuration

**Suggested trigger criteria for adoption:**

| Metric | Threshold |
|--------|-----------|
| Daily row volume | > 1M rows/day |
| Storage growth | > 50 GB/month raw |
| Aggregation cron duration | > 5 minutes per run |
| Partition management complexity | > 50 active partitions |

When any two of these thresholds are crossed, schedule a proof-of-concept migration using the path described in Section 5.
