#!/bin/bash
# One-time setup, run from an hPanel cron job (no SSH on this plan).
# Log: mofa/storage/logs/setup.log   — runs once, then skips (storage/setup.done).
DIR="$(cd "$(dirname "$0")" && pwd)"; cd "$DIR/.." || exit 1
LOG=storage/logs/setup.log
[ -f storage/setup.done ] && { echo "$(date) already done" >> "$LOG"; exit 0; }

. "$DIR/php.sh" 2>>"$LOG" || exit 1

{
  echo "===== $(date)"
  echo "php: $PHP"; "$PHP" -v | head -1
  "$PHP" artisan migrate --force &&
  "$PHP" artisan db:seed --force &&
  { "$PHP" artisan storage:link || true; } &&
  "$PHP" artisan optimize &&
  "$PHP" artisan filament:optimize &&
  touch storage/setup.done && echo "SETUP OK"
} >> "$LOG" 2>&1
