# Picks a PHP CLI >= 8.4 (the app's composer.lock needs it). Sourced by the other scripts.
PHP=""
for p in ${PHP_BIN:-} /opt/alt/php84/usr/bin/php /opt/alt/php85/usr/bin/php /usr/bin/php8.4 /usr/bin/php84 /usr/local/bin/php84 /usr/bin/php8.5 /usr/bin/php /usr/local/bin/php php; do
  command -v "$p" >/dev/null 2>&1 || continue
  if "$p" -r 'exit(PHP_VERSION_ID >= 80401 ? 0 : 1);' 2>/dev/null; then PHP="$p"; break; fi
done
if [ -z "$PHP" ]; then echo "$(date) ERROR: no PHP >= 8.4.1 found for the CLI" >&2; exit 1; fi
