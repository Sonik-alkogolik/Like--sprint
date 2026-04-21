# Stage 9: Server Rollout Runbook

## Целевой путь
`/var/www/Like-sprint`

## Подготовка перед первым выкладом
1. Скопировать `.env` из `backend/.env.production.example`.
2. Скопировать `client/.env.production` из `client/.env.production.example`.
3. Проверить Nginx-конфиг из `docs/nginx.like-sprint.conf`.
4. Подготовить backup БД.

## Первая выкладка (пошагово)
1. `cd /var/www/Like-sprint`
2. `git pull origin main`
3. `cd backend && composer install --no-dev --optimize-autoloader`
4. `php artisan migrate --force`
5. `php artisan optimize:clear`
6. `cd ../client && npm ci && npm run build`
7. `cd .. && rm -f public/index.html && rm -rf public/assets`
8. `cp client/dist/index.html public/ && cp -r client/dist/assets public/`
9. `cd backend && php artisan queue:restart`
10. `curl -fsS http://127.0.0.1/api/health`

## Контроль после выкладки
- Проверить веб вход для ролей `performer/advertiser/admin`.
- Проверить создание задания и submit отчёта.
- Проверить очередь уведомлений в admin разделе.

## Rollback (оперативно)
1. Восстановить предыдущий frontend build в `public/`.
2. При необходимости откатить БД из backup.
3. Перезапустить php-fpm и queue workers.
4. Повторно проверить `/api/health`.
