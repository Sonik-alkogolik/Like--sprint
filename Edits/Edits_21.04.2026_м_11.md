# Edits_21.04.2026_м_11

## Что сделано
- Переход к следующему шагу плана: завершён контур regression launcher + test UI.
- Добавлен универсальный раннер регресса:
  - `tools/autotests/regression_launcher.py`
  - поддержка suites:
    - `full_browser`
    - `post_deploy`
  - формирование markdown-отчётов в `tools/autotest-reports/`.
- Обновлён `tools/dev_ui.py`:
  - запуск одиночного e2e;
  - запуск полного регресса;
  - запуск post-deploy API smoke;
  - открытие последнего regression report.
- Обновлена документация `tools/autotests/README.md` с командами launcher.
- Добавлен `tools/autotest-reports/` в `.gitignore`, чтобы не коммитить артефакты прогонов.

## Изменённые файлы
- `.gitignore`
- `tools/autotests/regression_launcher.py`
- `tools/dev_ui.py`
- `tools/autotests/README.md`

## Тесты
- `python -m py_compile tools/autotests/regression_launcher.py tools/dev_ui.py` — PASS
- `python tools/autotests/regression_launcher.py --list` — PASS
- `python tools/autotests/regression_launcher.py --suite post_deploy` — PASS
- `python tools/autotests/regression_launcher.py --suite full_browser --delay-ms 90` — PASS
- `npm run build` — PASS

## Git
- Commit: pending
- Push: pending
