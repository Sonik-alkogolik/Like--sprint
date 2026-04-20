# Edits_20.04.2026_м_07

## Что сделано
- Реализован Этап 5 (Assignments и Submissions):
  - взятие задания исполнителем (`take task`);
  - создание assignment с дедлайном;
  - поддержка `start_url` (после взятия задания UI открывает ссылку в новой вкладке);
  - отправка отчёта;
  - редактирование отчёта до финального решения (через повторную отправку);
  - отказ от выполнения до финального решения;
  - статусы `submitted / rework_requested / approved / rejected / cancelled / expired`;
  - список исполнителя `Ожидают проверки` с сортировкой и суммой;
  - экран рекламодателя с grid отчётов по заданию и действиями:
    - `Принять`
    - `Вернуть на доработку` (обязательный комментарий)
    - `Отклонить`
    - `Принять все` (массовое подтверждение);
  - `auto_accept` для задания: при отправке отчёт автоподтверждается;
  - вложения отчёта (`submission_attachments`);
  - создание спора исполнителем по отклонённому отчёту (`disputes`).
- Добавлен `AssignmentService` с транзакционной бизнес-логикой:
  - repeat-ограничения (`one_time`, `repeat_after_review`, `repeat_interval`);
  - лимит подтверждений;
  - финансовые проводки по approve/reject:
    - approve: списание из hold рекламодателя + выплата исполнителю + комиссия платформы;
    - reject: release hold рекламодателю.

## Изменённые файлы
- `backend/database/migrations/2026_04_20_130000_create_assignments_and_submissions_tables.php`
- `backend/app/Models/Assignment.php`
- `backend/app/Models/Submission.php`
- `backend/app/Models/SubmissionAttachment.php`
- `backend/app/Models/Dispute.php`
- `backend/app/Models/Task.php`
- `backend/app/Models/User.php`
- `backend/app/Services/AssignmentService.php`
- `backend/app/Http/Controllers/Api/PerformerSubmissionController.php`
- `backend/app/Http/Controllers/Api/AdvertiserSubmissionController.php`
- `backend/routes/api.php`
- `client/src/views/PerformerTasksView.vue`
- `client/src/views/AssignmentWorkView.vue`
- `client/src/views/PendingSubmissionsView.vue`
- `client/src/views/AdvertiserReportsView.vue`
- `client/src/views/AdvertiserTasksView.vue`
- `client/src/router/index.js`
- `client/src/App.vue`
- `tools/autotests/e2e_user_sim_stage5_assignments.py`

## Тесты
- `php artisan migrate --force` (через `C:\OSPanel\modules\PHP-8.3\PHP\php.exe`) — PASS
- `php artisan route:list` — PASS
- `npm run build` (client) — PASS
- Stage 5 e2e:
  - `python tools/autotests/e2e_user_sim_stage5_assignments.py --headless --delay-ms 220` — PASS
- Полный browser regression:
  - `python tools/autotests/e2e_user_sim_auth.py --headless --delay-ms 160` — PASS
  - `python tools/autotests/e2e_user_sim_performer.py --headless --delay-ms 160` — PASS
  - `python tools/autotests/e2e_user_sim_advertiser.py --headless --delay-ms 160` — PASS
  - `python tools/autotests/e2e_user_sim_finance.py --headless --delay-ms 160` — PASS
  - `python tools/autotests/e2e_user_sim_stage4_tasks.py --headless --delay-ms 160` — PASS
  - `python tools/autotests/e2e_user_sim_stage5_assignments.py --headless --delay-ms 160` — PASS

## Git
- Commit: pending
- Push: pending

## Примечания
- Логика жалоб по отклонённым отчётам заведена (создание `dispute`), полная админ-очередь разбирательств планируется на этапе админки/антифрода.