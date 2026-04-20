# Edits_20.04.2026_м_04

## Что сделано
- Реализованы RBAC-ограничения по ролям (`performer`, `advertiser`)
- Добавлен middleware `EnsureUserRole` и alias `role`
- Добавлены role-endpoints:
  - `GET /api/performer/home`
  - `GET /api/advertiser/home`
- Обновлён frontend router: role-protected маршруты
  - `/performer/home`
  - `/advertiser/home`
- Логика после логина/регистрации теперь направляет пользователя в кабинет по роли
- Добавлены отдельные frontend-экраны кабинетов ролей
- Добавлены отдельные Python browser user-simulation сценарии:
  - `tools/autotests/e2e_user_sim_performer.py`
  - `tools/autotests/e2e_user_sim_advertiser.py`
- Обновлён `plan.md` с фиксацией этих e2e сценариев в обязательных auth тестах

## Изменённые файлы
- backend/app/Http/Middleware/EnsureUserRole.php
- backend/app/Http/Controllers/Api/PerformerController.php
- backend/app/Http/Controllers/Api/AdvertiserController.php
- backend/bootstrap/app.php
- backend/routes/api.php
- client/src/router/index.js
- client/src/App.vue
- client/src/views/LoginView.vue
- client/src/views/RegisterView.vue
- client/src/views/PerformerHomeView.vue
- client/src/views/AdvertiserHomeView.vue
- tools/autotests/e2e_user_sim_performer.py
- tools/autotests/e2e_user_sim_advertiser.py
- plan.md

## Тесты
- Запущен: `php artisan route:list`
- Проверено: role-endpoints присутствуют
- Запущен: `npm run build`
- Результат: PASS
- Запущен: `python tools/autotests/e2e_user_sim_performer.py --headless --delay-ms 250`
- Результат: PASS
- Запущен: `python tools/autotests/e2e_user_sim_advertiser.py --headless --delay-ms 250`
- Результат: PASS

## Git
- Commit: выполнен отдельным шагом
- Push: выполнен отдельным шагом

## Примечания
- Следующий срез Этапа 2: расширить user simulation на негативные auth-кейсы (неверный пароль, блокировка, revoke session) и вынести общие e2e helper-функции.
