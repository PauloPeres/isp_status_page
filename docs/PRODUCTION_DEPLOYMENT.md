# Production Deployment Guide

This guide covers deploying ISP Status Page to a production environment using Docker Compose.

## Architecture

```
                    ┌─────────────┐
    HTTPS (443) ───>│   Nginx /   │
                    │  Traefik    │──> App Container (port 8765)
                    └─────────────┘
                          │
              ┌───────────┼───────────┐
              │           │           │
        ┌─────────┐ ┌─────────┐ ┌─────────┐
        │  PHP    │ │ Postgres│ │  Redis  │
        │  App    │ │   16    │ │    7    │
        │  :80    │ │  :5432  │ │  :6379  │
        └─────────┘ └─────────┘ └─────────┘
```

## Prerequisites

- Linux server (Ubuntu 22.04+ recommended)
- Docker 24+ and Docker Compose v2
- Domain name with DNS configured
- 2GB+ RAM, 20GB+ disk

## Step 1: Clone and Configure

```bash
git clone https://github.com/PauloPeres/isp_status_page.git
cd isp_status_page
cp .env.example .env
```

Edit `.env` with production values:

```env
# App
APP_URL=https://status.yourdomain.com
APP_DEBUG=false
SECURITY_SALT=<random-64-char-string>

# Database
POSTGRES_PASSWORD=<strong-random-password>

# Redis
REDIS_PASSWORD=<strong-random-password>

# Stripe Billing
STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# OAuth (optional)
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
MICROSOFT_CLIENT_ID=...
MICROSOFT_CLIENT_SECRET=...

# SMTP
SMTP_HOST=smtp.yourdomain.com
SMTP_PORT=587
SMTP_USERNAME=noreply@yourdomain.com
SMTP_PASSWORD=<smtp-password>
SMTP_ENCRYPTION=tls
SMTP_FROM_EMAIL=noreply@yourdomain.com
SMTP_FROM_NAME=ISP Status

# Twilio SMS/WhatsApp (optional)
TWILIO_SID=...
TWILIO_AUTH_TOKEN=...
TWILIO_FROM_NUMBER=+1...
```

Generate a secure salt:
```bash
openssl rand -hex 32
```

## Step 2: Build Frontend

```bash
cd frontend
npm ci
npx ng build --configuration production
cd ..

# Copy SPA to PHP webroot
mkdir -p src/webroot/app
cp -r frontend/dist/frontend/browser/* src/webroot/app/
```

## Step 3: Start with Docker Compose

```bash
# Build and start in production mode
docker compose -f docker-compose.yml up -d --build

# Or use the Makefile
make prod
```

The app starts on port 8765 by default.

## Step 4: Run Migrations

```bash
docker compose exec app bin/cake migrations migrate --no-lock
docker compose exec app bin/cake migrations seed --seed DatabaseSeed
```

## Step 5: Create Super Admin

```bash
docker compose exec app bin/cake create_admin \
  --username admin \
  --email admin@yourdomain.com \
  --password '<strong-password>'
```

## Step 6: SSL with Reverse Proxy

### Option A: Nginx

```nginx
server {
    listen 80;
    server_name status.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name status.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/status.yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/status.yourdomain.com/privkey.pem;

    # Security headers
    add_header Strict-Transport-Security "max-age=63072000" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        proxy_pass http://127.0.0.1:8765;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # SSE requires no buffering
    location /api/v2/events/ {
        proxy_pass http://127.0.0.1:8765;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_buffering off;
        proxy_cache off;
        proxy_read_timeout 86400s;
    }
}
```

Get SSL certificate with Let's Encrypt:
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d status.yourdomain.com
```

### Option B: Traefik (Docker-native)

Add labels to the app service in `docker-compose.yml`:
```yaml
labels:
  - "traefik.enable=true"
  - "traefik.http.routers.ispstatus.rule=Host(`status.yourdomain.com`)"
  - "traefik.http.routers.ispstatus.tls.certresolver=letsencrypt"
  - "traefik.http.services.ispstatus.loadbalancer.server.port=80"
```

## Step 7: Stripe Webhook

Set up a Stripe webhook endpoint at:
```
https://status.yourdomain.com/api/v2/billing/webhook
```

Events to listen for:
- `checkout.session.completed`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.payment_succeeded`
- `invoice.payment_failed`

## Step 8: OAuth Configuration

### Google OAuth
1. Go to [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Create OAuth 2.0 Client ID
3. Authorized redirect URI: `https://status.yourdomain.com/api/v2/auth/oauth/google/callback`

### Microsoft OAuth
1. Go to [Azure Portal](https://portal.azure.com/#view/Microsoft_AAD_RegisteredApps)
2. Register an application
3. Redirect URI: `https://status.yourdomain.com/api/v2/auth/oauth/microsoft/callback`

## Monitoring & Health

### Health check endpoint
```bash
curl https://status.yourdomain.com/api/v2/super-admin/health
```

### View logs
```bash
docker compose logs -f app
docker compose logs -f postgres
```

### Database backup
```bash
# Manual backup
docker compose exec postgres pg_dump -U isp isp_status > backup_$(date +%Y%m%d).sql

# The app also has a built-in backup command (runs on cron):
docker compose exec app bin/cake backup
```

## Updating

```bash
git pull origin main

# Rebuild frontend
cd frontend && npm ci && npx ng build --configuration production && cd ..
cp -r frontend/dist/frontend/browser/* src/webroot/app/

# Rebuild and restart
docker compose up -d --build

# Run any new migrations
docker compose exec app bin/cake migrations migrate --no-lock
```

## Environment Variables Reference

| Variable | Required | Description |
|----------|----------|-------------|
| `APP_URL` | Yes | Full public URL (https://...) |
| `APP_DEBUG` | No | Set to `false` in production |
| `SECURITY_SALT` | Yes | Random 64-char hex string |
| `POSTGRES_PASSWORD` | Yes | Database password |
| `REDIS_PASSWORD` | Yes | Cache/session password |
| `STRIPE_PUBLIC_KEY` | Yes | Stripe publishable key |
| `STRIPE_SECRET_KEY` | Yes | Stripe secret key |
| `STRIPE_WEBHOOK_SECRET` | Yes | Stripe webhook signing secret |
| `SMTP_HOST` | Yes | SMTP server hostname |
| `SMTP_PORT` | Yes | SMTP port (587 for TLS) |
| `SMTP_USERNAME` | Yes | SMTP auth username |
| `SMTP_PASSWORD` | Yes | SMTP auth password |
| `SMTP_FROM_EMAIL` | Yes | Sender email address |
| `GOOGLE_CLIENT_ID` | No | Google OAuth client ID |
| `GOOGLE_CLIENT_SECRET` | No | Google OAuth client secret |
| `MICROSOFT_CLIENT_ID` | No | Microsoft OAuth client ID |
| `MICROSOFT_CLIENT_SECRET` | No | Microsoft OAuth client secret |
| `TWILIO_SID` | No | Twilio account SID (for SMS) |
| `TWILIO_AUTH_TOKEN` | No | Twilio auth token |
| `TWILIO_FROM_NUMBER` | No | Twilio phone number |

## Troubleshooting

| Issue | Solution |
|-------|----------|
| 502 Bad Gateway | Check `docker compose ps` — app might be starting |
| Database connection error | Verify `POSTGRES_PASSWORD` matches in `.env` |
| Redis connection error | Verify `REDIS_PASSWORD` matches in `.env` |
| OAuth redirect mismatch | Check redirect URIs match `APP_URL` exactly |
| Stripe webhooks failing | Verify `STRIPE_WEBHOOK_SECRET` and URL is reachable |
| Emails not sending | Test with super admin panel → Settings → Test Email |
| Cron not running | Check `docker compose exec app crontab -l` |
| SSE not working | Ensure Nginx has `proxy_buffering off` for `/api/v2/events/` |
