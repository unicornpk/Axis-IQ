#!/usr/bin/env bash
# Builds a deployable package for shared hosting with NO shell access and
# NO control over the document root — a real constraint on the unicornaxis.com
# host (confirmed with the owner). Produces two sibling folders:
#
#   axisiq/      — web-accessible (former public/ contents + install.php)
#   axisiq-app/  — NOT web-accessible (app, vendor, .env, etc.)
#
# Usage: ./build-deploy.sh /path/to/output-dir
# Then zip and upload both folders as siblings to the server, e.g. inside
# the same directory as the existing axisiq/ folder the owner created.
# See DEPLOY.md for the full walkthrough.

set -euo pipefail

OUT="${1:?Usage: ./build-deploy.sh /path/to/output-dir}"
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

rm -rf "${OUT:?}/app-src" "${OUT:?}/dist"
mkdir -p "$OUT/app-src" "$OUT/dist/axisiq" "$OUT/dist/axisiq-app"

echo "==> Copying source"
cp -r "$HERE"/{app,bootstrap,config,database,resources,routes,artisan,composer.json,composer.lock} "$OUT/app-src/"
cp -r "$HERE/public" "$OUT/app-src/public-orig"
mkdir -p "$OUT/app-src/storage/app/public" "$OUT/app-src/storage/app/private" \
  "$OUT/app-src/storage/framework/cache/data" "$OUT/app-src/storage/framework/sessions" \
  "$OUT/app-src/storage/framework/testing" "$OUT/app-src/storage/framework/views" \
  "$OUT/app-src/storage/logs" "$OUT/app-src/bootstrap/cache"
rm -f "$OUT/app-src/database/database.sqlite"
touch "$OUT/app-src/database/database.sqlite"

echo "==> Installing production dependencies (no-dev)"
(cd "$OUT/app-src" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction)

echo "==> Stripping .git dirs composer may have left in vendor/ (bloat only, not needed)"
find "$OUT/app-src/vendor" -maxdepth 3 -name ".git" -type d -exec rm -rf {} +

echo "==> Assembling axisiq/ (web root)"
cp -r "$OUT/app-src/public-orig/." "$OUT/dist/axisiq/"
# index.php must point at ../axisiq-app, not Laravel's default ../
sed -i \
  -e "s#require __DIR__.'/../vendor/autoload.php';#require __DIR__.'/../axisiq-app/vendor/autoload.php';#" \
  -e "s#__DIR__.'/../bootstrap/app.php'#__DIR__.'/../axisiq-app/bootstrap/app.php'#" \
  -e "s#__DIR__.'/../storage/framework/maintenance.php'#__DIR__.'/../axisiq-app/storage/framework/maintenance.php'#" \
  "$OUT/dist/axisiq/index.php"
cp "$HERE/deploy/install.php" "$OUT/dist/axisiq/install.php"

echo "==> Assembling axisiq-app/ (private)"
cp -r "$OUT/app-src"/{app,bootstrap,config,database,resources,routes,storage,vendor,artisan,composer.json,composer.lock} "$OUT/dist/axisiq-app/"
cp "$HERE/deploy/axisiq-app.htaccess" "$OUT/dist/axisiq-app/.htaccess"

for d in "$OUT/dist/axisiq-app/storage/app/public" "$OUT/dist/axisiq-app/storage/app/private" \
  "$OUT/dist/axisiq-app/storage/framework/cache/data" "$OUT/dist/axisiq-app/storage/framework/sessions" \
  "$OUT/dist/axisiq-app/storage/framework/testing" "$OUT/dist/axisiq-app/storage/framework/views" \
  "$OUT/dist/axisiq-app/storage/logs" "$OUT/dist/axisiq-app/bootstrap/cache"
do
  [ -z "$(ls -A "$d" 2>/dev/null)" ] && touch "$d/.gitkeep"
done

echo "==> Done. Deployable folders at: $OUT/dist/axisiq and $OUT/dist/axisiq-app"
echo "    Zip them and upload as SIBLING folders on the server. See DEPLOY.md."
