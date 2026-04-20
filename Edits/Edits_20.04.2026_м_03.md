# Edits_20.04.2026_м_03

## Что сделано
- Инициализирован Git-репозиторий (`git init`) и выставлена ветка `main`
- Реализован базовый контур Этапа 2 (auth/users/profiles/roles/sessions/device logging)
- Добавлены backend модели: `Profile`, `UserSession`, `DeviceLog`
- Добавлены миграции:
  - роль и блокировка пользователя в `users`
  - `profiles`
  - `user_sessions`
  - `device_logs`
- Реализованы API контроллеры:
  - `POST /api/auth/register`
  - `POST /api/auth/login`
  - `GET /api/auth/me`
  - `POST /api/auth/logout`
  - `GET /api/profile`
  - `PUT /api/profile`
  - `GET /api/sessions`
  - `POST /api/sessions/{session}/revoke`
- Добавлен middleware токен-аутентификации `auth.token`
- Добавлен сервис device logging (`DeviceLogService`)
- Обновлён frontend:
  - подключены `vue-router`, `pinia`, `axios`
  - маршруты `/login`, `/register`, `/profile`, `/sessions`
  - экраны auth/profile/sessions
  - выход пользователя
  - Vite proxy для `/api`
- Добавлен Python E2E user-simulation тест с задержками:
  - `tools/autotests/e2e_user_sim_auth.py`
- Обновлены требования автотестов (`playwright`) и `plan.md` под обязательный формат тестирования через browser user simulation

## Изменённые файлы
- backend/app/Models/User.php
- backend/app/Models/Profile.php
- backend/app/Models/UserSession.php
- backend/app/Models/DeviceLog.php
- backend/app/Http/Middleware/AuthenticateApiToken.php
- backend/app/Http/Controllers/Api/AuthController.php
- backend/app/Http/Controllers/Api/ProfileController.php
- backend/app/Http/Controllers/Api/SessionController.php
- backend/app/Services/DeviceLogService.php
- backend/bootstrap/app.php
- backend/routes/api.php
- backend/database/migrations/2026_04_20_100000_add_role_and_blocked_to_users_table.php
- backend/database/migrations/2026_04_20_100100_create_profiles_table.php
- backend/database/migrations/2026_04_20_100200_create_user_sessions_table.php
- backend/database/migrations/2026_04_20_100300_create_device_logs_table.php
- client/src/main.js
- client/src/App.vue
- client/src/style.css
- client/src/api.js
- client/src/stores/auth.js
- client/src/router/index.js
- client/src/views/LoginView.vue
- client/src/views/RegisterView.vue
- client/src/views/ProfileView.vue
- client/src/views/SessionsView.vue
- client/vite.config.js
- tools/requirements-autotest.txt
- tools/autotests/e2e_user_sim_auth.py
- plan.md

## Тесты
- Запущен: `php artisan migrate --force` (новые миграции)
- Запущен: API smoke через `Invoke-RestMethod` (`/api/health`, register, `/api/auth/me`)
- Результат: PASS (`API_SMOKE: PASS`)
- Запущен: `npm run build` (frontend)
- Результат: PASS
- Запущен: `python tools/autotests/e2e_user_sim_auth.py --headless --delay-ms 300`
- Результат: PASS (`E2E user simulation: PASS`)

## Git
- Commit: не выполнен
- Push: не выполнен

## Примечания
- Для `artisan serve` в текущем окружении нужен `sys_temp_dir` на локальную writable-папку проекта.
- Следующий шаг: расширить Этап 2 (RBAC-ограничения по ролям + страницы пользователя/админа) и добавить дополнительные user-simulation e2e сценарии.
