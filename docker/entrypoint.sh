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

# Check if migrations have been run
TABLES_COUNT=$(sqlite3 /var/www/html/database.db "SELECT COUNT(*) FROM sqlite_master WHERE type='table';" 2>/dev/null || echo "0")

if [ "$TABLES_COUNT" -lt "2" ]; then
    echo "Database appears empty. Running migrations..."

    # Check if migrations exist
    if [ -d /var/www/html/config/Migrations ]; then
        bin/cake migrations migrate --no-interaction || echo "Migrations not yet created or failed"
    else
        echo "No migrations found. Will need to create them."
    fi

    # Run seeds if they exist
    if [ -d /var/www/html/config/Seeds ]; then
        bin/cake migrations seed --no-interaction || echo "Seeds not yet created or failed"
    fi
else
    echo "Database already initialized (found $TABLES_COUNT tables)"
fi

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/tmp
chown -R www-data:www-data /var/www/html/logs
chmod -R 777 /var/www/html/tmp
chmod -R 777 /var/www/html/logs

# Start cron daemon in background if crontab exists
if [ -f /etc/cron.d/isp-status-cron ]; then
    echo "Starting cron daemon..."
    cron
fi

echo "=========================================="
echo "ISP Status Page is ready!"
echo "Access: http://localhost:8765"
echo "=========================================="

# Execute the main command (apache2-foreground)
exec "$@"
