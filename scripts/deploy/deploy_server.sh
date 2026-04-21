#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/Like-sprint"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"

echo "[deploy] app dir: ${APP_DIR}"
cd "${APP_DIR}"

echo "[deploy] git pull"
git pull origin main

echo "[deploy] backend deps"
cd "${APP_DIR}/backend"
${COMPOSER_BIN} install --no-dev --optimize-autoloader

echo "[deploy] migrate"
${PHP_BIN} artisan migrate --force

echo "[deploy] clear caches"
${PHP_BIN} artisan optimize:clear

echo "[deploy] frontend build"
cd "${APP_DIR}/client"
${NPM_BIN} ci
${NPM_BIN} run build

echo "[deploy] publish frontend to public"
cd "${APP_DIR}"
rm -f public/index.html
rm -rf public/assets
cp client/dist/index.html public/
cp -r client/dist/assets public/

echo "[deploy] restart queue"
cd "${APP_DIR}/backend"
${PHP_BIN} artisan queue:restart

echo "[deploy] health check"
curl -fsS "http://127.0.0.1/api/health" >/dev/null || {
  echo "[deploy] health check failed"
  exit 1
}

echo "[deploy] done"
