#!/usr/bin/env bash
# Fix common Nextcloud upload issues on nb-ncdev (PHP limits, mimetype DB, Apache timeout).
# Only touches container nb-ncdev.
set -euo pipefail

CONTAINER="nb-ncdev"
NC_ROOT="/var/www/nextcloud"

if ! lxc info "${CONTAINER}" >/dev/null 2>&1; then
	echo "Error: container ${CONTAINER} not found" >&2
	exit 1
fi

lxc exec "${CONTAINER}" -- bash -s <<'REMOTE'
set -euo pipefail
NC_ROOT="/var/www/nextcloud"

echo "==> PHP upload limits (Apache + CLI)"
cat > /etc/php/8.3/apache2/conf.d/99-nextcloud-uploads.ini <<'EOF'
; Nextcloud large file uploads (RAF/RAW)
upload_max_filesize = 1024M
post_max_size = 1100M
memory_limit = 512M
max_execution_time = 3600
max_input_time = 3600
EOF
cp /etc/php/8.3/apache2/conf.d/99-nextcloud-uploads.ini /etc/php/8.3/cli/conf.d/99-nextcloud-uploads.ini

echo "==> Apache timeout"
if ! grep -q "Nextcloud upload timeout" /etc/apache2/apache2.conf; then
	cat >> /etc/apache2/apache2.conf <<'EOF'

# Nextcloud upload timeout
Timeout 3600
EOF
fi

echo "==> Fix PostgreSQL mimetypes sequence"
sudo -u postgres psql -d nextcloud -c "INSERT INTO oc_mimetypes (mimetype) VALUES ('image/x-dcraw') ON CONFLICT (mimetype) DO NOTHING;"
sudo -u postgres psql -d nextcloud -c "SELECT setval('oc_mimetypes_id_seq', (SELECT MAX(id) FROM oc_mimetypes));"

echo "==> Update Nextcloud mimetype mappings"
cd "${NC_ROOT}"
sudo -u www-data php occ maintenance:mimetype:update-db
sudo -u www-data php occ maintenance:mimetype:update-js
sudo -u postgres psql -d nextcloud -c "SELECT setval('oc_mimetypes_id_seq', (SELECT MAX(id) FROM oc_mimetypes));"

echo "==> Nextcloud upload performance settings"
sudo -u www-data php occ config:system:set filesystem_check_changes --value=0 --type=integer
sudo -u www-data php occ config:system:set max_chunk_size --value=10485760 --type=integer

echo "==> Restart Apache"
systemctl restart apache2

echo "==> Done. PHP limits:"
grep upload_max /etc/php/8.3/apache2/conf.d/99-nextcloud-uploads.ini
REMOTE

echo "Upload fix applied on ${CONTAINER}."
