# Production Deployment

This guide deploys MoneyFlow AI to an Ubuntu server with Docker Compose, PostgreSQL, Redis, internal Docker Nginx, and host Nginx handling HTTPS through Certbot.

## Production Architecture

Traffic flow:

1. Browser connects to host Nginx on `https://your-domain.com`.
2. Host Nginx terminates TLS and proxies to `127.0.0.1:8088`.
3. Docker Nginx serves static files and forwards PHP requests to the PHP-FPM app container.
4. The app container connects to PostgreSQL and Redis on a private Docker network.
5. The queue container runs `php artisan queue:work redis`.

Security boundaries:

- PostgreSQL is not published to the host.
- Redis is not published to the host.
- Docker Nginx is published only on `127.0.0.1`.
- Host Nginx is the only public HTTP/HTTPS entrypoint.
- `APP_DEBUG` must stay `false` in production.

## Files

- `docker/php/Dockerfile`: builds PHP-FPM app image, installs PHP extensions, installs Composer dependencies, and builds Inertia/Vite assets with npm.
- `docker/nginx/default.conf`: internal Docker Nginx config for Laravel public assets and PHP-FPM forwarding.
- `docker-compose.prod.yml`: production Compose stack for app, web, queue, PostgreSQL, and Redis.
- `.env.production.example`: production environment template.

## Server Prerequisites

Install Docker, the Docker Compose plugin, host Nginx, and Certbot on Ubuntu:

```bash
sudo apt update
sudo apt install -y ca-certificates curl gnupg nginx certbot python3-certbot-nginx
```

Install Docker Engine from Docker's official Ubuntu repository, then confirm:

```bash
docker --version
docker compose version
```

Configure the firewall so only SSH and web traffic are public:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

Point your domain DNS `A` record to the server before requesting SSL.

## Prepare Production Environment

Copy the environment template:

```bash
cp .env.production.example .env.production
```

Edit `.env.production` and replace every placeholder:

- `APP_URL`
- `APP_KEY`
- `APP_INTERNAL_PORT`
- `DB_PASSWORD`
- `REDIS_PASSWORD`
- mail settings
- `SESSION_DOMAIN`

Generate a Laravel app key on a trusted machine or after the image builds:

```bash
php artisan key:generate --show
```

Keep these production values:

```dotenv
APP_ENV=production
APP_DEBUG=false
DB_HOST=postgres
REDIS_HOST=redis
SESSION_SECURE_COOKIE=true
```

Do not commit `.env.production`.

## Build And Start Docker Stack

Build images:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml build
```

Always pass `--env-file .env.production` with this Compose file. Compose uses those values for required PostgreSQL and Redis substitutions before containers are created.

Start containers:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml up -d
```

Run Laravel deployment commands:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan db:seed --class=SuperAdminSeeder --force
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan storage:link
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan optimize
```

Check containers:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml ps
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f web app queue
```

Confirm PostgreSQL and Redis are not exposed publicly:

```bash
sudo ss -tulpn | grep -E '5432|6379' || true
```

The only Docker web binding should be loopback:

```bash
sudo ss -tulpn | grep 8088
```

Expected binding:

```text
127.0.0.1:8088
```

## Host Nginx Reverse Proxy

Create `/etc/nginx/sites-available/moneyflow-ai`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name moneyflow.example.com;

    client_max_body_size 10M;

    location / {
        proxy_pass http://127.0.0.1:8088;
        proxy_http_version 1.1;

        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/moneyflow-ai /etc/nginx/sites-enabled/moneyflow-ai
sudo nginx -t
sudo systemctl reload nginx
```

## SSL With Certbot

Request and install the certificate:

```bash
sudo certbot --nginx -d moneyflow.example.com
```

Choose the redirect-to-HTTPS option when Certbot asks.

After Certbot updates the server block, add or confirm these headers in the HTTPS server block:

```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
```

Reload Nginx:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Certbot installs automatic renewal timers. Verify:

```bash
systemctl list-timers | grep certbot
sudo certbot renew --dry-run
```

## Updating The App

Pull the latest code and rebuild:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml build --pull
docker compose --env-file .env.production -f docker-compose.prod.yml up -d
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan optimize
```

Restart the queue worker after deployment:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml restart queue
```

## Backups

Create PostgreSQL backups from the private database container:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec postgres sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' > moneyflow-$(date +%F).sql
```

Also back up Docker volumes:

- `moneyflow_pgdata`
- `moneyflow_storage`
- `moneyflow_redis` if queued/cache data must survive a restore

Store backups outside the server or in encrypted object storage.

## Production Security Checklist

- `APP_DEBUG=false`.
- Strong `APP_KEY`.
- Strong PostgreSQL password.
- Strong Redis password.
- PostgreSQL has no `ports` mapping in Compose.
- Redis has no `ports` mapping in Compose.
- Docker web port is bound to `127.0.0.1`.
- Host Nginx handles HTTPS and redirects HTTP to HTTPS.
- Certbot renewal is working.
- Hidden files are denied by Docker Nginx.
- Sensitive files such as `.env`, logs, SQL dumps, and SQLite files are denied by Docker Nginx.
- Server firewall exposes only SSH, HTTP, and HTTPS.
- Queue worker is running.
- Database backups are scheduled and restore-tested.

## Troubleshooting

Check app logs:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f app
```

Check queue logs:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f queue
```

Check host Nginx:

```bash
sudo nginx -t
sudo journalctl -u nginx -f
```

Clear and rebuild Laravel caches:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan optimize:clear
docker compose --env-file .env.production -f docker-compose.prod.yml exec app php artisan optimize
```
