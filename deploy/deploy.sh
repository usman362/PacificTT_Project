#!/usr/bin/env bash
#
# Update the live site.
#
#   bash ~/private/deploy/deploy.sh
#
# Layout on the host:
#   ~/private       the application core (this repository) — not web-reachable
#   ~/public_html   only the contents of public/, copied here on every deploy
#
# PUBLIC_HTML and COMPOSER can be overridden, e.g.
#   PUBLIC_HTML=~/www COMPOSER="php ~/composer.phar" bash deploy/deploy.sh
set -euo pipefail

CORE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PUBLIC_HTML="${PUBLIC_HTML:-$(dirname "$CORE")/public_html}"
COMPOSER="${COMPOSER:-composer}"

if [ ! -f "$CORE/.env" ]; then
  echo "No .env in $CORE — copy .env.example to .env and fill it in first." >&2
  exit 1
fi
if [ ! -d "$PUBLIC_HTML" ]; then
  echo "Public folder $PUBLIC_HTML does not exist. Set PUBLIC_HTML=/path/to/public_html." >&2
  exit 1
fi

cd "$CORE"

echo "→ Pulling latest code"
git pull --ff-only

echo "→ Installing dependencies"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

echo "→ Maintenance mode on"
php artisan down --retry=15 || true

echo "→ Database"
php artisan migrate --force

echo "→ Publishing public files to $PUBLIC_HTML"
# Copy, never delete: public_html may hold files that are not ours
# (e.g. .well-known for SSL). The storage link is handled separately below.
if command -v rsync >/dev/null 2>&1; then
  rsync -a --exclude storage --exclude .DS_Store "$CORE/public/" "$PUBLIC_HTML/"
else
  (cd "$CORE/public" && find . -path ./storage -prune -o -type f ! -name .DS_Store -print \
    | while read -r f; do mkdir -p "$PUBLIC_HTML/$(dirname "$f")"; cp -p "$f" "$PUBLIC_HTML/$f"; done)
fi

# Uploaded certificate photos and waiver signatures live in the core's storage
# and are served through this one link.
if [ ! -e "$PUBLIC_HTML/storage" ]; then
  ln -s "$CORE/storage/app/public" "$PUBLIC_HTML/storage"
fi

echo "→ Rebuilding caches"
php artisan optimize:clear
php artisan optimize

echo "→ Maintenance mode off"
php artisan up

echo "✓ Deployed $(git log -1 --format='%h %s')"
