# Edits_20.04.2026_м_06

## Что сделано
- Реализован Этап 4 (контур заданий):
  - таблицы `tasks`, `task_requirements`, `task_links`;
  - создание/редактирование заданий рекламодателем;
  - отправка задания на модерацию;
  - очередь модерации для админа и решения approve/reject;
  - запуск/остановка задания;
  - резервирование бюджета при запуске через холд кошелька рекламодателя.
- Добавлен `TaskService` с бизнес-логикой жизненного цикла задания и резервов.
- Добавлен API для исполнителя: список активных доступных заданий.
- Расширен frontend:
  - кабинет рекламодателя для создания и управления заданиями;
  - админ-страница модерации;
  - страница исполнителя с доступными заданиями;
  - обновлена навигация и роутинг по ролям.
- Добавлен browser simulation сценарий этапа 4:
  - `tools/autotests/e2e_user_sim_stage4_tasks.py`
  - покрывает flow: advertiser create → submit moderation → admin approve → advertiser launch → performer sees task.

## Изменённые файлы
- `backend/database/migrations/2026_04_20_120000_create_tasks_tables.php`
- `backend/app/Models/Task.php`
- `backend/app/Models/TaskRequirement.php`
- `backend/app/Models/TaskLink.php`
- `backend/app/Models/User.php`
- `backend/app/Services/TaskService.php`
- `backend/app/Http/Controllers/Api/AdvertiserTaskController.php`
- `backend/app/Http/Controllers/Api/AdminTaskModerationController.php`
- `backend/app/Http/Controllers/Api/PerformerTaskController.php`
- `backend/app/Http/Controllers/Api/AuthController.php`
- `backend/routes/api.php`
- `client/src/views/AdvertiserTasksView.vue`
- `client/src/views/AdminModerationView.vue`
- `client/src/views/PerformerTasksView.vue`
- `client/src/views/LoginView.vue`
- `client/src/views/RegisterView.vue`
- `client/src/router/index.js`
- `client/src/App.vue`
- `tools/autotests/e2e_user_sim_stage4_tasks.py`

## Тесты
- `php artisan migrate --force` (через `C:\OSPanel\modules\PHP-8.3\PHP\php.exe`) — PASS
- `npm run build` (client) — PASS
- Stage 4 e2e:
  - `python tools/autotests/e2e_user_sim_stage4_tasks.py --headless --delay-ms 250` — PASS
- Полный browser regression:
  - `python tools/autotests/e2e_user_sim_auth.py --headless --delay-ms 180` — PASS
  - `python tools/autotests/e2e_user_sim_performer.py --headless --delay-ms 180` — PASS
  - `python tools/autotests/e2e_user_sim_advertiser.py --headless --delay-ms 180` — PASS
  - `python tools/autotests/e2e_user_sim_finance.py --headless --delay-ms 180` — PASS
  - `python tools/autotests/e2e_user_sim_stage4_tasks.py --headless --delay-ms 180` — PASS

## Git
- Commit: pending
- Push: pending

## Примечания
- Следующий шаг: Этап 5 (assignments/submissions, rework, approve/reject, массовая обработка, auto_accept, списки "на проверке").