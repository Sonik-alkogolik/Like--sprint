# Deployment Notes (Stage 8)

## Production variables
- Backend template: `backend/.env.production.example`
- Frontend template: `client/.env.production.example`

## Nginx
- Draft config: `docs/nginx.like-sprint.conf`
- Frontend is served from `/var/www/Like-sprint/public`.
- API traffic is proxied to Laravel app (`php-fpm`).

## Recommended release flow
1. `git pull origin main`
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force`
4. `php artisan optimize:clear`
5. `npm ci` and `npm run build` in `client/`
6. copy `client/dist` to `public/`
7. `php artisan queue:restart`
8. health check `/api/health`

## Scripts
- Linux deploy script: `scripts/deploy/deploy_server.sh`
- Windows local deploy smoke: `scripts/deploy/local_deploy_smoke.ps1`

## Rollback baseline
1. Keep previous release as backup folder.
2. Re-point `public` assets to previous build.
3. If migration is incompatible, run DB restore from backup snapshot.
4. Restart php-fpm + queue workers and verify `/api/health`.
