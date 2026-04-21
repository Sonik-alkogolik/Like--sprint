# Stage 10: Operations and Stabilization

## Цель
После выкладки обеспечить наблюдаемость, быстрый smoke и понятный rollback.

## Ежедневный check-list
1. Проверить `GET /api/health`.
2. Проверить свежие `fraud_events` и `audit_logs`.
3. Проверить, что queue workers активны.
4. Проверить, что последняя сборка frontend доступна из `public/`.

## Post-deploy smoke
- Запустить `scripts/deploy/post_deploy_smoke.ps1` локально против целевого URL.
- Минимальные критерии:
  - `/api/health` = 200;
  - `/` = 200;
  - `/api/admin/notifications/stats` отвечает при admin токене.

## Алерты (минимум)
- `api.health` не отвечает > 1 мин.
- очередь `notification_events.pending` растёт без снижения > 15 мин.
- рост `fraud_events` high severity > порога.

## Rollback policy
1. Откат frontend ассетов.
2. При критическом инциденте откат БД из backup snapshot.
3. Фикс в отдельной ветке и hotfix deploy по `deploy_server.sh`.
