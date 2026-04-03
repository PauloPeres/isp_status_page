#!/bin/bash
set -e

echo "=========================================="
echo "ISP Status Page - Starting..."
echo "=========================================="

# Check if vendor directory exists
if [ ! -d /var/www/html/vendor ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Wait for PostgreSQL to be ready (if DATABASE_URL points to postgres)
if echo "$DATABASE_URL" | grep -q "^postgres"; then
    echo "Waiting for PostgreSQL to be ready..."
    # Extract host and port from DATABASE_URL (postgres://user:pass@host:port/dbname)
    PG_HOST=$(echo "$DATABASE_URL" | sed -E 's|^postgres(ql)?://[^@]+@([^:]+):([0-9]+)/.*|\2|')
    PG_PORT=$(echo "$DATABASE_URL" | sed -E 's|^postgres(ql)?://[^@]+@([^:]+):([0-9]+)/.*|\3|')
    PG_USER=$(echo "$DATABASE_URL" | sed -E 's|^postgres(ql)?://([^:]+):.*|\2|')
    PG_DB=$(echo "$DATABASE_URL" | sed -E 's|^postgres(ql)?://[^/]+/(.+)$|\2|')

    RETRIES=30
    until pg_isready -h "$PG_HOST" -p "$PG_PORT" -U "$PG_USER" -d "$PG_DB" > /dev/null 2>&1; do
        RETRIES=$((RETRIES - 1))
        if [ "$RETRIES" -le 0 ]; then
            echo "ERROR: PostgreSQL is not ready after 30 attempts. Exiting."
            exit 1
        fi
        echo "PostgreSQL is not ready yet. Waiting... ($RETRIES attempts remaining)"
        sleep 1
    done
    echo "PostgreSQL is ready!"
fi

# Always run migrations to ensure schema is up to date
echo "Running database migrations..."
if [ -d /var/www/html/config/Migrations ]; then
    bin/cake migrations migrate || echo "Migrations failed"
else
    echo "No migrations found. Skipping."
fi

# Check if we need to run seeds (only on first init)
if echo "$DATABASE_URL" | grep -q "^postgres"; then
    # PostgreSQL: count user tables (exclude system tables)
    PG_PASS=$(echo "$DATABASE_URL" | sed -E 's|^postgres(ql)?://[^:]+:([^@]+)@.*|\2|')
    TABLES_COUNT=$(PGPASSWORD="$PG_PASS" psql -h "$PG_HOST" -p "$PG_PORT" -U "$PG_USER" -d "$PG_DB" -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE';" 2>/dev/null | tr -d ' ' || echo "0")
else
    # SQLite fallback
    if [ -f /var/www/html/database.db ]; then
        TABLES_COUNT=$(sqlite3 /var/www/html/database.db "SELECT COUNT(*) FROM sqlite_master WHERE type='table';" 2>/dev/null || echo "0")
    else
        TABLES_COUNT=0
    fi
fi

if [ "$TABLES_COUNT" -lt "5" ]; then
    echo "Database appears empty (found $TABLES_COUNT tables). Running seeds..."
    if [ -d /var/www/html/config/Seeds ]; then
        bin/cake migrations seed || echo "Seeds failed"
        # Ensure admin user has an organization membership
        bin/cake migrations seed --seed AdminOrgSeed || echo "AdminOrgSeed failed"
    fi
else
    echo "Database already seeded (found $TABLES_COUNT tables). Skipping seeds."
fi

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/tmp
chown -R www-data:www-data /var/www/html/logs
chown www-data:www-data /var/www/html
chmod -R 777 /var/www/html/tmp
chmod -R 777 /var/www/html/logs

# Configure and start cron if enabled
if [ "${ENABLE_CRON:-false}" = "true" ]; then
    echo "Configuring cron for monitor checks..."

    # Create wrapper script for monitor checks
    cat > /usr/local/bin/monitor-check-cron.sh <<'CRONEOF'
#!/bin/bash
export DATABASE_URL="${DATABASE_URL}"
export REDIS_URL="${REDIS_URL}"
export CACHE_DRIVER="${CACHE_DRIVER}"
export SESSION_DRIVER="${SESSION_DRIVER}"
cd /var/www/html
bin/cake monitor_check
CRONEOF

    # Re-write with actual env vars expanded
    # Uses scheduler --once to push due checks to the queue (Redis lock prevents duplicates)
    cat > /usr/local/bin/monitor-check-cron.sh <<EOF
#!/bin/bash
export DATABASE_URL="${DATABASE_URL}"
export REDIS_URL="${REDIS_URL}"
export CACHE_DRIVER="${CACHE_DRIVER}"
export SESSION_DRIVER="${SESSION_DRIVER}"
cd /var/www/html
bin/cake scheduler --once
EOF

    chmod +x /usr/local/bin/monitor-check-cron.sh

    # Create wrapper script for monthly credit grants
    cat > /usr/local/bin/grant-monthly-credits-cron.sh <<EOF
#!/bin/bash
export DATABASE_URL="${DATABASE_URL}"
export REDIS_URL="${REDIS_URL}"
export CACHE_DRIVER="${CACHE_DRIVER}"
export SESSION_DRIVER="${SESSION_DRIVER}"
cd /var/www/html
bin/cake grant_monthly_credits
EOF

    chmod +x /usr/local/bin/grant-monthly-credits-cron.sh

    # Create wrapper script for scheduled reports (P4-010)
    cat > /usr/local/bin/send-scheduled-reports-cron.sh <<EOF
#!/bin/bash
export DATABASE_URL="${DATABASE_URL}"
export REDIS_URL="${REDIS_URL}"
export CACHE_DRIVER="${CACHE_DRIVER}"
export SESSION_DRIVER="${SESSION_DRIVER}"
cd /var/www/html
bin/cake send_scheduled_reports
EOF

    chmod +x /usr/local/bin/send-scheduled-reports-cron.sh

    # Create wrapper script for escalation checks
    cat > /usr/local/bin/escalation-check-cron.sh <<EOF
#!/bin/bash
export DATABASE_URL="${DATABASE_URL}"
export REDIS_URL="${REDIS_URL}"
export CACHE_DRIVER="${CACHE_DRIVER}"
export SESSION_DRIVER="${SESSION_DRIVER}"
cd /var/www/html
bin/cake escalation_check
EOF

    chmod +x /usr/local/bin/escalation-check-cron.sh

    # Create wrapper script for cleanup
    cat > /usr/local/bin/cleanup-cron.sh <<EOF
#!/bin/bash
export DATABASE_URL="${DATABASE_URL}"
export REDIS_URL="${REDIS_URL}"
export CACHE_DRIVER="${CACHE_DRIVER}"
export SESSION_DRIVER="${SESSION_DRIVER}"
cd /var/www/html
bin/cake cleanup
EOF

    chmod +x /usr/local/bin/cleanup-cron.sh

    # Create wrapper script for backup
    cat > /usr/local/bin/backup-cron.sh <<EOF
#!/bin/bash
export DATABASE_URL="${DATABASE_URL}"
export REDIS_URL="${REDIS_URL}"
export CACHE_DRIVER="${CACHE_DRIVER}"
export SESSION_DRIVER="${SESSION_DRIVER}"
cd /var/www/html
bin/cake backup
EOF

    chmod +x /usr/local/bin/backup-cron.sh

    # Create cron job file
    cat > /etc/cron.d/isp-status-cron <<EOF
# ISP Status Page - Scheduler Safety Fallback
# Runs every minute as a fallback in case the scheduler daemon container dies
# The scheduler daemon is the primary mechanism; this is belt-and-suspenders
* * * * * www-data /usr/local/bin/monitor-check-cron.sh >> /var/www/html/logs/cron.log 2>&1

# ISP Status Page - Escalation Check Cron
# Runs every minute to process escalation policies for unacknowledged incidents
* * * * * www-data /usr/local/bin/escalation-check-cron.sh >> /var/www/html/logs/escalation.log 2>&1

# ISP Status Page - Scheduled Reports (P4-010)
# Runs every hour to process due scheduled email reports
0 * * * * www-data /usr/local/bin/send-scheduled-reports-cron.sh >> /var/www/html/logs/scheduled-reports.log 2>&1

# ISP Status Page - Monthly Credit Grant
# Runs at midnight on the 1st of each month to grant notification credits
0 0 1 * * www-data /usr/local/bin/grant-monthly-credits-cron.sh >> /var/www/html/logs/credit-grants.log 2>&1

# ISP Status Page - Cleanup
# Runs daily at 3 AM to clean up old data
0 3 * * * www-data /usr/local/bin/cleanup-cron.sh >> /var/www/html/logs/cleanup.log 2>&1

# ISP Status Page - Backup
# Runs daily at 2 AM to create database backups
0 2 * * * www-data /usr/local/bin/backup-cron.sh >> /var/www/html/logs/backup.log 2>&1

EOF

    # Set correct permissions and ownership
    chmod 0644 /etc/cron.d/isp-status-cron
    chown root:root /etc/cron.d/isp-status-cron

    # Start cron daemon in background
    echo "Starting cron daemon..."
    cron

    echo "Cron configured - monitor checks will run every minute"
    echo "  Logs: /var/www/html/logs/cron.log"
else
    echo "Cron disabled (set ENABLE_CRON=true to enable)"
fi

echo "=========================================="
echo "ISP Status Page is ready!"
echo "Access: http://localhost:8765"
echo "=========================================="

# Execute the main command (apache2-foreground)
exec "$@"
