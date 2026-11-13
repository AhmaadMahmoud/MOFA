#!/bin/bash
# After uploading a new package: clears caches and runs new migrations.
# Run once from a cron job, log: mofa/storage/logs/update.log
DIR="$(cd "$(dirname "$0")" && pwd)"; cd "$DIR/.." || exit 1
. "$DIR/php.sh" || exit 1
{
  echo "===== $(date)"
  "$PHP" artisan optimize:clear &&
  "$PHP" artisan migrate --force &&
  "$PHP" artisan optimize &&
  "$PHP" artisan filament:optimize &&
  echo "UPDATE OK"
} >> storage/logs/update.log 2>&1
