# RAW Editor — Nextcloud App

View, edit, and export Fujifilm `.raf` RAW photos from selected Nextcloud folders.

## Features

- Gallery of `.raf` files from user-selected folders
- Fast thumbnails via embedded camera preview
- Full RAW preview and non-destructive adjustments
- High-quality JPEG export (quality 98) to the same folder as the source `.raf`
- Files app integration: "Open in RAW Editor"

## Requirements

- Nextcloud 30–34
- PHP 8.1+
- Python 3 with `rawpy`, `Pillow`, `numpy` (installed automatically in app `.venv`)
- System library: LibRaw (`libraw-dev` on Debian/Ubuntu)

## Development

```bash
npm install
npm run dev          # build frontend
composer install     # PHP autoload
```

## Deploy to nb-ncdev

```bash
chmod +x scripts/deploy-nb-ncdev.sh scripts/setup-check.sh
./scripts/deploy-nb-ncdev.sh
```

Health check (inside container):

```bash
lxc exec nb-ncdev -- bash /var/www/nextcloud/apps/raweditor/scripts/setup-check.sh
```

## Architecture

- **Frontend:** Vue 2.7 SPA with `@nextcloud/vue`
- **Backend:** PHP (Nextcloud App Framework)
- **RAW processing:** Python scripts using `rawpy` (LibRaw)
