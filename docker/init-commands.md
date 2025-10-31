# ISP Status Page - Docker Initialization Commands

## Container Startup Sequence (Automatic)

When the container starts, the `entrypoint.sh` automatically runs:

### 1. Database Setup
```bash
# Creates database.db if it doesn't exist
touch /var/www/html/database.db
chown www-data:www-data /var/www/html/database.db
chmod 666 /var/www/html/database.db
```

### 2. Composer Dependencies
```bash
# Installs dependencies if vendor/ doesn't exist
composer install --no-interaction --prefer-dist
```

### 3. Database Migrations (Automatic!)
```bash
# Checks if tables exist, if not runs migrations
bin/cake migrations migrate --no-interaction
```

### 4. Database Seeds (Automatic!)
```bash
# Seeds the database with initial data
bin/cake migrations seed --no-interaction
```

### 5. Permissions
```bash
# Sets correct permissions
chown -R www-data:www-data /var/www/html/tmp
chown -R www-data:www-data /var/www/html/logs
chmod -R 777 /var/www/html/tmp
chmod -R 777 /var/www/html/logs
```

### 6. Cron Setup (if ENABLE_CRON=true)
```bash
# Configures monitor check cron job (every minute)
* * * * * www-data cd /var/www/html && bin/cake monitor_check >> logs/cron.log 2>&1
```

---

## Manual Commands (if needed)

### Run Migrations Manually
```bash
docker-compose exec app bin/cake migrations migrate
```

### Run Seeds Manually
```bash
docker-compose exec app bin/cake migrations seed
```

### Rollback Migration
```bash
docker-compose exec app bin/cake migrations rollback
```

### Check Migration Status
```bash
docker-compose exec app bin/cake migrations status
```

### Clear Cache
```bash
docker-compose exec app bin/cake cache clear_all
```

### Run Monitor Check Manually
```bash
docker-compose exec app bin/cake monitor_check -v
```

### Create Admin User (if needed)
```bash
docker-compose exec app bin/cake console
# Then run: $usersTable = \Cake\ORM\TableRegistry::getTableLocator()->get('Users');
```

---

## Fresh Start (Reset Everything)

```bash
# Stop containers
docker-compose down

# Remove database
rm -f src/database.db

# Remove vendor (optional)
rm -rf src/vendor

# Rebuild and start
docker-compose up --build -d

# Watch logs
docker-compose logs -f
```

---

## Useful Monitoring Commands

### View Cron Logs
```bash
docker-compose exec app tail -f logs/cron.log
```

### View Application Logs
```bash
docker-compose exec app tail -f logs/error.log
```

### Check Database
```bash
docker-compose exec app sqlite3 database.db
# Then: SELECT * FROM monitors;
```

### Test Database Connection
```bash
docker-compose exec app bin/cake console
# Then: $conn = \Cake\Datasource\ConnectionManager::get('default'); $conn->connect();
```

---

## Environment Variables

Set these in `.env` or `docker-compose.yml`:

- `DATABASE_URL` - Database connection (default: sqlite:///var/www/html/database.db)
- `APP_DEBUG` - Enable debug mode (default: true)
- `SECURITY_SALT` - Security salt for encryption
- `ENABLE_CRON` - Enable automatic monitor checks (default: true)

---

## Troubleshooting

### Database not found
```bash
# Check if database exists
docker-compose exec app ls -lh database.db

# Check permissions
docker-compose exec app stat database.db
```

### Migrations not running
```bash
# Check migrations exist
docker-compose exec app ls -lh config/Migrations/

# Run manually
docker-compose exec app bin/cake migrations migrate
```

### Cron not working
```bash
# Check cron is running
docker-compose exec app ps aux | grep cron

# Check cron logs
docker-compose exec app cat logs/cron.log

# Check cron configuration
docker-compose exec app cat /etc/cron.d/isp-status-cron
```

### Permission issues
```bash
# Fix permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/tmp
docker-compose exec app chown -R www-data:www-data /var/www/html/logs
docker-compose exec app chmod 666 /var/www/html/database.db
```
