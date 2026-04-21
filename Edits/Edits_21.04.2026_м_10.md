# Edits_21.04.2026_м_10

## Что сделано
- Продолжен следующий подпункт разработки: operational `admin overview`.
- Backend:
  - добавлен endpoint `GET /api/admin/overview` с агрегатами:
    - users total / blocked;
    - tasks total / active / pending moderation;
    - disputes open;
    - high severity fraud events (24h);
    - audit logs (24h);
    - active blacklist entries;
    - pending notification queue.
- Frontend:
  - добавлен верхний блок `Overview` в `Admin Control Center` с метриками.
- Вёрстка:
  - контент переведён на полную ширину экрана с боковыми отступами ~25px:
    - `.content` теперь `width: calc(100% - 50px)` без `max-width`;
    - верхняя панель с горизонтальными отступами `25px` на desktop;
    - адаптация для mobile сохранена.
- Тесты:
  - добавлен e2e сценарий `stage7_admin_overview`.

## Изменённые файлы
- `backend/app/Http/Controllers/Api/AdminOpsController.php`
- `backend/routes/api.php`
- `client/src/views/AdminModerationView.vue`
- `client/src/style.css`
- `tools/autotests/e2e_user_sim_stage7_admin_overview.py`

## Тесты
- `php -l backend/app/Http/Controllers/Api/AdminOpsController.php` — PASS
- `python -m py_compile tools/autotests/e2e_user_sim_stage7_admin_overview.py` — PASS
- `npm run build` — PASS
- `python tools/autotests/e2e_user_sim_stage7_admin_overview.py --delay-ms 180` — PASS
- Полный Playwright regression chain (auth, performer, advertiser, finance, stage4, stage5, stage6, stage7 antifraud, stage7 blacklist, stage7 overview) — PASS

## Git
- Commit: pending
- Push: pending
