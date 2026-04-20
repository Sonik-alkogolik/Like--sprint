# Like-sprint

Browser-first microtask platform (MVP in progress).

## Repository structure

- `backend/` - Laravel API and business logic
- `client/` - Vue frontend
- `tools/` - Python autotests and Gradio runner UI
- `Edits/` - completed change logs
- `docs/` - supporting technical docs
- `plan.md` - primary development plan

## Local development

1. Backend (Laravel):
`C:\OSPanel\modules\PHP-8.2\PHP\php.exe artisan serve --host=127.0.0.1 --port=8000` (run from `backend/`)
2. Frontend (Vue):
`npm run dev` (run from `client/`)
3. Test UI (Gradio):
`python tools/dev_ui.py` (run from repo root)

## Environment templates

- `backend/.env.example`
- `client/.env.example`
- `tools/requirements-autotest.txt`

## Status

Stage 0 and baseline Stage 1 skeletons are initialized:
- Laravel backend scaffold in `backend/` with `/up` and `/api/health`
- Vue Vite scaffold in `client/`
- Python Gradio test UI scaffold in `tools/`
