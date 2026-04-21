# Edits_21.04.2026_м_07

## Что сделано
- Выполнен следующий пункт после этапов 7-10: стабилизация e2e (anti-flake).
- Усилен `tools/autotests/e2e_user_sim_stage7_admin_antifraud.py`:
  - финальная проверка блокировки логина теперь подтверждается через API (`POST /api/auth/login`, ожидается `403`);
  - UI-проверка переведена в fallback режим (остаемся на `/login`, без ложного падения при редкой задержке отрисовки текста).

## Изменённые файлы
- `tools/autotests/e2e_user_sim_stage7_admin_antifraud.py`

## Тесты
- `python -m py_compile tools/autotests/e2e_user_sim_stage7_admin_antifraud.py` — PASS
- `python tools/autotests/e2e_user_sim_stage7_admin_antifraud.py --delay-ms 220` — PASS

## Git
- Commit: pending
- Push: pending
