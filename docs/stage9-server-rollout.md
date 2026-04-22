# Stage 9: Server Rollout Runbook

## Целевой путь
`/var/www/Like-sprint`

## Подготовка перед первым выкладом
1. Скопировать `.env` из `backend/.env.production.example`.
2. Скопировать `client/.env.production` из `client/.env.production.example`.
3. Проверить Nginx-конфиг из `docs/nginx.like-sprint.conf`.
4. Подготовить backup БД.
5. Выполнить preflight локально:
   - `pwsh scripts/deploy/server_rollout_dryrun.ps1 -Strict`

## Первая выкладка (пошагово)
1. `cd /var/www/Like-sprint`
2. `bash scripts/deploy/deploy_server.sh`

## Контроль после выкладки
- Проверить веб вход для ролей `performer/advertiser/admin`.
- Проверить создание задания и submit отчёта.
- Проверить очередь уведомлений в admin разделе.

## Rollback (оперативно)
1. Восстановить предыдущий frontend build в `public/`.
2. При необходимости откатить БД из backup.
3. Перезапустить php-fpm и queue workers.
4. Повторно проверить `/api/health`.
