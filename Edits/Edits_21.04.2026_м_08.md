# Edits_21.04.2026_м_08

## Что сделано
- Продолжен Этап 7 по плану: добавлен модуль `blacklists`.
- Реализованы blacklist записи (`email`, `ip`) с поддержкой:
  - `is_active`;
  - `expires_at`;
  - `created_by` / `deactivated_by`.
- Встроены проверки blacklist в auth flow:
  - `register` блокируется при совпадении email/ip;
  - `login` блокируется при совпадении email/ip.
- Добавлены admin API операции blacklist:
  - список активных записей;
  - добавление записи;
  - деактивация записи.
- Добавлено логирование:
  - `fraud_events` на блокировки auth и операции blacklist;
  - `audit_logs` на admin-действия blacklist.
- Обновлён admin UI:
  - новая секция управления blacklist в Control Center.
- Добавлен browser simulation тест:
  - `tools/autotests/e2e_user_sim_stage7_blacklist.py`.

## Изменённые файлы
- `backend/database/migrations/2026_04_21_140000_create_blacklist_entries_table.php`
- `backend/app/Models/BlacklistEntry.php`
- `backend/app/Services/BlacklistService.php`
- `backend/app/Http/Controllers/Api/AuthController.php`
- `backend/app/Http/Controllers/Api/AdminOpsController.php`
- `backend/routes/api.php`
- `client/src/views/AdminModerationView.vue`
- `tools/autotests/e2e_user_sim_stage7_blacklist.py`

## Тесты
- `php -l backend/app/Http/Controllers/Api/AuthController.php` — PASS
- `php -l backend/app/Http/Controllers/Api/AdminOpsController.php` — PASS
- `php -l backend/app/Services/BlacklistService.php` — PASS
- `python -m py_compile tools/autotests/e2e_user_sim_stage7_blacklist.py` — PASS
- `npm run build` — PASS
- `php artisan migrate --force` — PASS
- `php artisan route:list` — PASS
- `python tools/autotests/e2e_user_sim_stage7_blacklist.py --delay-ms 220` — PASS
- Полный Playwright regression chain (auth, performer, advertiser, finance, stage4, stage5, stage6, stage7 admin/antifraud, stage7 blacklist) — PASS

## Git
- Commit: pending
- Push: pending
