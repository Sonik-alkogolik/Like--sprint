# Edits_20.04.2026_м_05

## Что сделано
- Реализован Этап 3 (финансовый контур): `wallets`, `ledger_entries`, `deposits`, `withdrawals`, `system_wallets`.
- Добавлен транзакционный `WalletService` с операциями `credit`, `hold`, `releaseHold`, `spendFromHold`.
- Добавлены API эндпоинты финансов:
  - `GET /api/finance/wallet`
  - `GET /api/finance/ledger`
  - `POST /api/finance/deposits/simulate`
  - `GET /api/finance/withdrawals`
  - `POST /api/finance/withdrawals`
- Добавлена страница фронтенда `Финансы` с тестовым пополнением, созданием заявки на вывод и просмотром проводок.
- Добавлен новый browser simulation сценарий: `tools/autotests/e2e_user_sim_finance.py`.
- Обновлён существующий auth simulation под текущий роутинг после регистрации/логина.

## Изменённые файлы
- `backend/database/migrations/2026_04_20_110000_create_finance_tables.php`
- `backend/app/Models/Wallet.php`
- `backend/app/Models/SystemWallet.php`
- `backend/app/Models/LedgerEntry.php`
- `backend/app/Models/Deposit.php`
- `backend/app/Models/Withdrawal.php`
- `backend/app/Models/User.php`
- `backend/app/Services/WalletService.php`
- `backend/app/Http/Controllers/Api/FinanceController.php`
- `backend/app/Http/Controllers/Api/AuthController.php`
- `backend/routes/api.php`
- `client/src/views/FinanceView.vue`
- `client/src/router/index.js`
- `client/src/App.vue`
- `tools/autotests/e2e_user_sim_finance.py`
- `tools/autotests/e2e_user_sim_auth.py`

## Тесты
- `npm run build` (client) — PASS
- `php artisan migrate --force` (через `C:\OSPanel\modules\PHP-8.3\PHP\php.exe`) — PASS
- Browser simulation regression (Playwright):
  - `python tools/autotests/e2e_user_sim_auth.py --headless --delay-ms 200` — PASS
  - `python tools/autotests/e2e_user_sim_performer.py --headless --delay-ms 200` — PASS
  - `python tools/autotests/e2e_user_sim_advertiser.py --headless --delay-ms 200` — PASS
  - `python tools/autotests/e2e_user_sim_finance.py --headless --delay-ms 200` — PASS

## Git
- Commit: pending
- Push: pending

## Примечания
- Этап 4 (задания: создание/модерация/запуск/остановка/резервирование) выполняется следующим шагом.