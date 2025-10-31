#!/bin/bash
set -e

echo "=========================================="
echo "ISP Status Page - Starting..."
echo "=========================================="

# Wait for a moment to ensure everything is ready
sleep 2

# Check if database file exists, if not create it
if [ ! -f /var/www/html/database.db ]; then
    echo "Creating database file..."
    touch /var/www/html/database.db
    chown www-data:www-data /var/www/html/database.db
    chmod 666 /var/www/html/database.db
fi

# Check if vendor directory exists
if [ ! -d /var/www/html/vendor ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Always run migrations to ensure schema is up to date
echo "Running database migrations..."
if [ -d /var/www/html/config/Migrations ]; then
    bin/cake migrations migrate || echo "Migrations failed"
else
    echo "No migrations found. Skipping."
fi

# Check if we need to run seeds (only on first init)
TABLES_COUNT=$(sqlite3 /var/www/html/database.db "SELECT COUNT(*) FROM sqlite_master WHERE type='table';" 2>/dev/null || echo "0")

if [ "$TABLES_COUNT" -lt "5" ]; then
    echo "Database appears empty. Running seeds..."
    if [ -d /var/www/html/config/Seeds ]; then
        bin/cake migrations seed || echo "Seeds failed"
    fi
else
    echo "Database already seeded (found $TABLES_COUNT tables). Skipping seeds."
fi

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/tmp
chown -R www-data:www-data /var/www/html/logs
chmod -R 777 /var/www/html/tmp
chmod -R 777 /var/www/html/logs

# Configure and start cron if enabled
if [ "${ENABLE_CRON:-false}" = "true" ]; then
    echo "Configuring cron for monitor checks..."

    # Create cron job file
    cat > /etc/cron.d/isp-status-cron <<EOF
# ISP Status Page - Monitor Check Cron
# Runs every minute to check all monitors
* * * * * www-data cd /var/www/html && bin/cake monitor_check >> /var/www/html/logs/cron.log 2>&1

# Empty line required at end of cron file
EOF

    # Set correct permissions
    chmod 0644 /etc/cron.d/isp-status-cron

    # Register the cron job
    crontab /etc/cron.d/isp-status-cron

    # Start cron daemon in background
    echo "Starting cron daemon..."
    cron

    echo "✓ Cron configured - monitor checks will run every minute"
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
