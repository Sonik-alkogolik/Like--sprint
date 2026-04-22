# Edits_22.04.2026_м_01

## Что сделано
- Продолжен server rollout контур после этапов 8-10.
- Усилен `scripts/deploy/server_rollout_dryrun.ps1`:
  - добавлен режим `-Strict`;
  - добавлена проверка ключевых шагов в `scripts/deploy/deploy_server.sh`;
  - добавлена проверка ссылки на deploy-скрипт в `docs/stage9-server-rollout.md`.
- Обновлён runbook `docs/stage9-server-rollout.md`:
  - добавлен обязательный preflight перед выкладкой;
  - основной путь выкладки унифицирован на `bash scripts/deploy/deploy_server.sh`.

## Изменённые файлы
- `scripts/deploy/server_rollout_dryrun.ps1`
- `docs/stage9-server-rollout.md`

## Тесты
- `pwsh scripts/deploy/server_rollout_dryrun.ps1 -Strict` — PASS

## Git
- Commit: pending
- Push: pending
