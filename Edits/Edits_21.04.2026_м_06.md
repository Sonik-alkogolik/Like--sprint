# Edits_21.04.2026_м_06

## Что сделано
- Реализован дополнительный Этап 10 (операционная стабилизация после деплоя):
  - добавлен operations runbook:
    - `docs/stage10-operations.md`
  - добавлен post-deploy smoke script:
    - `scripts/deploy/post_deploy_smoke.ps1`
  - обновлён `docs/README.md` с ссылкой на stage10 документацию.

## Изменённые файлы
- `docs/stage10-operations.md`
- `scripts/deploy/post_deploy_smoke.ps1`
- `docs/README.md`

## Тесты
- `scripts/deploy/post_deploy_smoke.ps1 -BaseUrl http://127.0.0.1:8000 -FrontendUrl http://127.0.0.1:5173` — PASS
- Повторная проверка `npm run build` — PASS

## Git
- Commit: pending
- Push: pending
