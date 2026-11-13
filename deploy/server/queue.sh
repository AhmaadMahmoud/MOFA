#!/bin/bash
# Every-minute cron: sends queued order emails, then exits.
DIR="$(cd "$(dirname "$0")" && pwd)"; cd "$DIR/.." || exit 1
. "$DIR/php.sh" || exit 1
"$PHP" artisan queue:work --stop-when-empty --tries=3 --max-time=50 >> storage/logs/queue.log 2>&1
