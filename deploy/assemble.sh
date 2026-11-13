#!/usr/bin/env bash
# Lays out an already-built MOFA checkout for Hostinger shared hosting:
#   OUT/mofa         the app (outside the web root)
#   OUT/public_html  what the web server serves
# Usage: bash deploy/assemble.sh OUT_DIR   (run from the project root, after composer install + npm run build)
# DEPLOY_KEY_HASH (sha256 of the deploy key) is baked into public_html/_deploy.php when set.
set -euo pipefail
OUT="${1:?usage: assemble.sh OUT_DIR}"
ROOT="$(pwd)"
APP="$OUT/mofa"
WEB="$OUT/public_html"
rm -rf "$OUT"; mkdir -p "$APP"

# The app, minus things that never go to the server
tar -C "$ROOT" \
    --exclude=./.git --exclude=./.github --exclude=./node_modules --exclude=./public \
    --exclude=./.env --exclude='./.env.*' --exclude=./storage --exclude=./bootstrap/cache \
    --exclude=./tests --exclude=./.idea --exclude=./.vscode --exclude='*.DS_Store' \
    -cf - . | tar -xf - -C "$APP"
mkdir -p "$APP/bootstrap/cache"

# Web root = public/ without the local storage link or Vite hot file
mkdir -p "$WEB"
tar -C "$ROOT/public" --exclude=./storage --exclude=./hot -cf - . | tar -xf - -C "$WEB"

# public_html/index.php loads the app from ../mofa
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

# bootstrap/app.php: public path is ../public_html
perl -0pi -e 's/\}\)->create\(\);\s*$/})->create()->usePublicPath(dirname(__DIR__, 2).\x27\/public_html\x27);\n/' "$APP/bootstrap/app.php"
grep -q usePublicPath "$APP/bootstrap/app.php" || { echo "could not patch bootstrap/app.php" >&2; exit 1; }

# Post-deploy hook (migrate + rebuild caches), guarded by the deploy key
if [ -n "${DEPLOY_KEY_HASH:-}" ]; then
    sed "s/__KEY_HASH__/${DEPLOY_KEY_HASH}/" "$ROOT/deploy/hook.php" > "$WEB/_deploy.php"
fi
echo "assembled: $(find "$APP" | wc -l) app entries, $(find "$WEB" | wc -l) web entries"
