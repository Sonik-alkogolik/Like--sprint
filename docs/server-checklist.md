# Server Checklist (Stage 8)

## OS / runtime
- Ubuntu 22.04+ (or compatible Linux distro)
- PHP 8.3 + php-fpm
- Composer 2.x
- Node.js 20+ and npm
- PostgreSQL 15+
- Redis 7+
- Nginx

## Access
- SSH key auth configured
- deploy user has access to `/var/www/Like-sprint`
- repository cloned from `origin/main`

## Backend
- `backend/.env` created from production template
- DB credentials and app key configured
- `php artisan migrate --force` tested
- queue worker service configured (systemd/supervisor)

## Frontend
- `client/.env.production` created
- `npm ci && npm run build` tested
- build publish to `public/` verified

## Nginx / domain
- domain DNS points to server
- `docs/nginx.like-sprint.conf` applied and adapted
- `nginx -t` passes
- TLS configured (certbot/manual)

## Release verification
- `/api/health` returns `{"status":"ok"}`
- login/register flow works
- one e2e smoke scenario completed
- rollback procedure prepared and documented
