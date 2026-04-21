# Edits_21.04.2026_м_04

## Что сделано
- Реализован Этап 8 (подготовка к деплою):
  - добавлены production env templates:
    - `backend/.env.production.example`
    - `client/.env.production.example`
  - добавлена документация деплоя:
    - `docs/deployment.md`
    - `docs/nginx.like-sprint.conf`
    - `docs/server-checklist.md`
  - добавлен локальный deploy smoke script:
    - `scripts/deploy/local_deploy_smoke.ps1`
  - добавлен серверный deploy script:
    - `scripts/deploy/deploy_server.sh`.

## Изменённые файлы
- `backend/.env.production.example`
- `client/.env.production.example`
- `docs/deployment.md`
- `docs/nginx.like-sprint.conf`
- `docs/server-checklist.md`
- `scripts/deploy/deploy_server.sh`
- `scripts/deploy/local_deploy_smoke.ps1`

## Тесты
- `scripts/deploy/local_deploy_smoke.ps1 -DryRun` — PASS
- `scripts/deploy/local_deploy_smoke.ps1 -DryRun:$false` — PASS (через escalated run)
- `npm run build` — PASS

## Git
- Commit: pending
- Push: pending
