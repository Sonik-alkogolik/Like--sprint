# Edits_21.04.2026_м_03

## Что сделано
- Расширен Этап 7 (админка и антифрод):
  - добавлен полноценный `audit_logs`;
  - добавлена финансовая корректировка по спору в пользу исполнителя:
    - с дебетом рекламодателя;
    - с кредитом исполнителя;
    - с защитой от двойного применения компенсации;
  - добавлен `DisputeService` для транзакционного разрешения споров;
  - расширен `WalletService` методом `debit`.
- Обновлён admin API:
  - `GET /api/admin/audit-logs`;
  - `POST /api/admin/disputes/{dispute}/status` теперь поддерживает `apply_compensation`.
- Усилено журналирование:
  - админ-модерация задач пишет audit log;
  - массовое подтверждение отчётов рекламодателем пишет audit log;
  - действия блокировки/разблокировки и статусы споров пишутся в audit/fraud.
- Обновлён Admin UI:
  - фильтр/панель audit log;
  - чекбокс применения финкоррекции по спору;
  - отображение суммы компенсации по спору.
- Обновлён Stage 7 browser simulation:
  - проверка компенсации через API кошелька;
  - проверка наличия audit/fraud следов;
  - проверка блокировки логина исполнителя.

## Изменённые файлы
- `backend/database/migrations/2026_04_21_120000_create_audit_logs_and_extend_disputes_for_compensation.php`
- `backend/app/Models/AuditLog.php`
- `backend/app/Services/AuditLogService.php`
- `backend/app/Services/DisputeService.php`
- `backend/app/Services/WalletService.php`
- `backend/app/Models/Dispute.php`
- `backend/app/Http/Controllers/Api/AdminOpsController.php`
- `backend/app/Http/Controllers/Api/AdminTaskModerationController.php`
- `backend/app/Http/Controllers/Api/AdvertiserSubmissionController.php`
- `backend/routes/api.php`
- `client/src/views/AdminModerationView.vue`
- `tools/autotests/e2e_user_sim_stage7_admin_antifraud.py`

## Тесты
- `npm run build` — PASS
- `php -l`:
  - `backend/app/Services/DisputeService.php` — PASS
  - `backend/app/Http/Controllers/Api/AdminOpsController.php` — PASS
  - `backend/app/Http/Controllers/Api/AdvertiserSubmissionController.php` — PASS
  - `backend/app/Http/Controllers/Api/AdminTaskModerationController.php` — PASS
- `python -m py_compile tools/autotests/e2e_user_sim_stage7_admin_antifraud.py` — PASS
- `php artisan migrate --force` — PASS
- `php artisan route:list` — PASS
- Stage 7 e2e:
  - `python tools/autotests/e2e_user_sim_stage7_admin_antifraud.py --delay-ms 140` — PASS
- Полный browser regression chain:
  - `e2e_user_sim_auth.py` — PASS
  - `e2e_user_sim_performer.py` — PASS
  - `e2e_user_sim_advertiser.py` — PASS
  - `e2e_user_sim_finance.py` — PASS
  - `e2e_user_sim_stage4_tasks.py` — PASS
  - `e2e_user_sim_stage5_assignments.py` — PASS
  - `e2e_user_sim_stage6_notifications.py` — PASS
  - `e2e_user_sim_stage7_admin_antifraud.py` — PASS

## Git
- Commit: pending
- Push: pending

## Примечания
- Этап 7 теперь покрывает dispute queue + anti-fraud events + admin blocklist + audit trail + финансовую корректировку по спору.
