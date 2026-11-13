#!/usr/bin/env bash
# Builds a ready-to-upload package of MOFA for Hostinger shared hosting (no SSH).
# Run it from the project root:   bash deploy/build-hostinger.sh
# It works on a clean copy of the last COMMIT (git archive), so your working
# folder, vendor/ and node_modules/ are not touched. Commit first.
# Output: ~/Desktop/mofa-deploy.zip  (contains mofa/ and public_html/)
set -euo pipefail

ROOT="$(git rev-parse --show-toplevel)"
PHP="${PHP:-php}"
OUT_ZIP="${OUT_ZIP:-$HOME/Desktop/mofa-deploy.zip}"
WORK="$(mktemp -d)"
APP="$WORK/mofa"
WEB="$WORK/public_html"

echo "==> Exporting last commit ($(git -C "$ROOT" rev-parse --short HEAD))"
if [ -n "$(git -C "$ROOT" status --porcelain)" ]; then
  echo "    note: you have uncommitted changes; they will NOT be in the package."
fi
mkdir -p "$APP"
git -C "$ROOT" archive HEAD | tar -x -C "$APP"

echo "==> composer install (no dev)"
( cd "$APP" && composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --quiet )
# if composer fell back to git clones, drop their history (saves hundreds of MB)
find "$APP/vendor" -name .git -type d -prune -exec rm -rf {} +

echo "==> npm ci + vite build"
( cd "$APP" && npm ci --no-audit --no-fund --loglevel=error && npm run build --silent )
rm -rf "$APP/node_modules" "$APP/public/hot"

echo "==> Moving public/ to public_html/"
mv "$APP/public" "$WEB"

# public_html/index.php: load the app from ../mofa
cat > "$WEB/index.php" <<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$base = __DIR__.'/../mofa';

if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $base.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $base.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
PHP

# bootstrap/app.php: public path = ../public_html (for web, cron and artisan)
perl -0pi -e 's/\}\)->create\(\);\s*$/})->create()->usePublicPath(dirname(__DIR__, 2).\x27\/public_html\x27);\n/' "$APP/bootstrap/app.php"
grep -q "usePublicPath" "$APP/bootstrap/app.php" || { echo "!! could not patch bootstrap/app.php"; exit 1; }

echo "==> Server scripts and .env"
mkdir -p "$APP/deploy"
cp "$ROOT/deploy/server/"*.sh "$APP/deploy/" 2>/dev/null || true
rm -f "$APP/deploy/build-hostinger.sh"
if [ -f "$ROOT/.env.production" ]; then
  cp "$ROOT/.env.production" "$APP/.env"
  if ! grep -q '^APP_KEY=base64:' "$APP/.env"; then
    KEY="$(cd "$APP" && "$PHP" artisan key:generate --show --no-ansi)"
    perl -pi -e "s|^APP_KEY=.*|APP_KEY=$KEY|" "$APP/.env"
    echo "    generated APP_KEY (also saved back to your .env.production)"
    perl -pi -e "s|^APP_KEY=.*|APP_KEY=$KEY|" "$ROOT/.env.production"
  fi
else
  echo "    note: no .env.production found; create mofa/.env on the server by hand."
fi

# writable dirs must exist
mkdir -p "$APP/storage/app/public" "$APP/storage/framework/"{cache/data,sessions,views} "$APP/storage/logs" "$APP/bootstrap/cache"
rm -f "$APP/storage/logs/"*.log "$APP/bootstrap/cache/"*.php

echo "==> Zipping"
rm -f "$OUT_ZIP"
( cd "$WORK" && zip -qry "$OUT_ZIP" mofa public_html -x '*.DS_Store' )
rm -rf "$WORK"
echo "Done: $OUT_ZIP ($(du -h "$OUT_ZIP" | cut -f1))"
