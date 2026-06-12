#!/usr/bin/env bash
# Health check for raweditor inside nb-ncdev or local NC install.
set -euo pipefail

NC_ROOT="${1:-/var/www/nextcloud}"
APP_PATH="${NC_ROOT}/apps/raweditor"

echo "==> Checking app directory"
test -d "${APP_PATH}" || { echo "App not found at ${APP_PATH}"; exit 1; }

echo "==> Checking Python venv"
PYTHON="${APP_PATH}/.venv/bin/python3"
test -x "${PYTHON}" || { echo "Python venv missing — run deploy or occ repair"; exit 1; }

echo "==> Checking rawpy import"
"${PYTHON}" -c "import rawpy; import PIL; print('rawpy OK:', rawpy.__version__)"

echo "==> Checking JS bundle"
test -f "${APP_PATH}/js/raweditor-main.js" || { echo "JS bundle missing — run npm run build"; exit 1; }

echo "==> Checking app enabled"
cd "${NC_ROOT}"
sudo -u www-data php occ app:list | grep -E "raweditor.*enabled" || echo "Warning: app may not be enabled"

echo "All checks passed."
