# Edits_21.04.2026_м_01

## Что сделано
- Начат и реализован Этап 6 (уведомления):
  - добавлен внутренний центр уведомлений пользователя (`user_notifications`);
  - добавлена очередь событий уведомлений (`notification_events`) с состояниями `pending/sent/failed`;
  - реализован `NotificationService`:
    - постановка уведомлений в очередь (`enqueue`);
    - ручной диспетч очереди (`dispatchPending`);
    - статистика очереди (`stats`);
  - добавлен API-контур уведомлений:
    - `GET /api/notifications`
    - `GET /api/notifications/unread-count`
    - `POST /api/notifications/{notification}/read`
    - `POST /api/notifications/read-all`
    - `POST /api/admin/notifications/dispatch`
    - `GET /api/admin/notifications/stats`
- Уведомления встроены в ключевые события бизнес-логики:
  - `submission_submitted` (для рекламодателя);
  - `submission_rework_requested` (для исполнителя);
  - `submission_approved` (для исполнителя);
  - `submission_rejected` (для исполнителя);
  - `submission_dispute_created` (для рекламодателя);
  - `task_moderated` (для рекламодателя при approve/reject модерации).
- Добавлен frontend-экран `Уведомления`:
  - список уведомлений;
  - фильтр «только непрочитанные»;
  - отметить прочитанным / отметить все;
  - admin-блок для ручной отправки очереди и просмотра статистики.
- В верхнее меню добавлена ссылка на уведомления с счётчиком непрочитанных.
- Добавлен browser user simulation сценарий:
  - `tools/autotests/e2e_user_sim_stage6_notifications.py`.

## Изменённые файлы
- `backend/database/migrations/2026_04_21_090000_create_notifications_tables.php`
- `backend/app/Models/UserNotification.php`
- `backend/app/Models/NotificationEvent.php`
- `backend/app/Services/NotificationService.php`
- `backend/app/Http/Controllers/Api/NotificationController.php`
- `backend/routes/api.php`
- `backend/app/Models/User.php`
- `backend/app/Services/AssignmentService.php`
- `backend/app/Services/TaskService.php`
- `client/src/views/NotificationsView.vue`
- `client/src/router/index.js`
- `client/src/App.vue`
- `tools/autotests/e2e_user_sim_stage6_notifications.py`

## Тесты
- `npm run build` (client) — PASS
- `python -m py_compile tools/autotests/e2e_user_sim_stage6_notifications.py` — PASS
- `php -l`:
  - `backend/app/Services/NotificationService.php` — PASS
  - `backend/app/Http/Controllers/Api/NotificationController.php` — PASS
  - `backend/app/Services/AssignmentService.php` — PASS
  - `backend/app/Services/TaskService.php` — PASS
- `php artisan migrate --force` (через `C:\OSPanel\modules\PHP-8.3\PHP\php.exe`) — PASS
- `php artisan route:list` — PASS (новые notification routes присутствуют)
- `python tools/autotests/e2e_user_sim_stage6_notifications.py --headless --delay-ms 160` — FAIL в текущем окружении запуска:
  - `PermissionError: [WinError 5] Отказано в доступе` при старте Playwright subprocess.

## Git
- Commit: pending
- Push: pending

## Примечания
- Полный e2e прогон этапа 6 требует окружение, в котором Playwright может создавать subprocess/browser.
- После снятия ограничения по запуску browser subprocess нужно повторно прогнать stage6 e2e и полный regression chain.
