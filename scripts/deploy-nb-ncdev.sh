#!/usr/bin/env bash
# Deploy raweditor app to an LXC Nextcloud container (default: nb-ncdev).
set -euo pipefail

CONTAINER="${1:-nb-ncdev}"
NC_ROOT="/var/www/nextcloud"
APP_ID="raweditor"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"

if ! lxc info "${CONTAINER}" >/dev/null 2>&1; then
	echo "Error: LXC container '${CONTAINER}' not found." >&2
	exit 1
fi

echo "==> Building frontend on host"
cd "${PROJECT_DIR}"
if command -v npm >/dev/null 2>&1; then
	npm run build
else
	echo "Warning: npm not found on host — ensure js/raweditor-main.js exists" >&2
	test -f "${PROJECT_DIR}/js/raweditor-main.js" || exit 1
fi

echo "==> Syncing app to ${CONTAINER}:${NC_ROOT}/apps/${APP_ID}"
lxc exec "${CONTAINER}" -- bash -c "mkdir -p ${NC_ROOT}/apps/${APP_ID}"
tar -C "${PROJECT_DIR}" \
	--exclude=node_modules \
	--exclude=.venv \
	--exclude=vendor \
	--exclude=.git \
	-cf - . | lxc exec "${CONTAINER}" -- tar -C "${NC_ROOT}/apps/${APP_ID}" -xf -

echo "==> Installing system packages (libraw, exiftool) if needed"
lxc exec "${CONTAINER}" -- bash -c "apt-get update -qq && apt-get install -y -qq libraw-dev python3-venv python3-dev libimage-exiftool-perl 2>/dev/null || true"

echo "==> Setting up Python venv"
lxc exec "${CONTAINER}" -- bash -c "cd ${NC_ROOT}/apps/${APP_ID} && python3 -m venv .venv && .venv/bin/pip install -q -r python/requirements.txt"

echo "==> Installing dnglab for RAF -> DNG conversion (may build from source)"
lxc exec "${CONTAINER}" -- bash -c "cd ${NC_ROOT}/apps/${APP_ID} && apt-get install -y -qq cargo rustc pkg-config libssl-dev 2>/dev/null || true && bash scripts/install-dnglab.sh"

echo "==> Fixing permissions"
lxc exec "${CONTAINER}" -- bash -c "chown -R www-data:www-data ${NC_ROOT}/apps/${APP_ID}"

echo "==> Enabling app"
lxc exec "${CONTAINER}" -- bash -c "cd ${NC_ROOT} && sudo -u www-data php occ app:enable ${APP_ID} 2>/dev/null || sudo -u www-data php occ app:enable ${APP_ID} --force"

echo "==> Running migrations"
lxc exec "${CONTAINER}" -- bash -c "cd ${NC_ROOT} && sudo -u www-data php occ migrations:execute ${APP_ID} 0 2>/dev/null || true"
lxc exec "${CONTAINER}" -- bash -c "cd ${NC_ROOT} && sudo -u www-data php occ migrations:execute ${APP_ID} 1 2>/dev/null || true"
lxc exec "${CONTAINER}" -- bash -c "cd ${NC_ROOT} && sudo -u www-data php occ migrations:execute ${APP_ID} 2 2>/dev/null || true"
lxc exec "${CONTAINER}" -- bash -c "cd ${NC_ROOT} && sudo -u www-data php occ migrations:execute ${APP_ID} 3 2>/dev/null || true"

echo "Deploy complete. App available at Nextcloud -> RAW Editor"
