#!/bin/sh
set -eu

# storage монтируется как tmpfs/emptyDir и при старте пуст
for dir in \
  storage/framework/cache/data \
  storage/framework/views \
  storage/framework/sessions \
  storage/logs \
  bootstrap/cache
do
  mkdir -p "$dir" || { echo "entrypoint: cannot create $dir (check volume ownership)" >&2; exit 1; }
done

role="${1:-web}"
[ "$#" -gt 0 ] && shift

case "$role" in
  web)
    exec php artisan octane:frankenphp \
      --host=0.0.0.0 \
      --port="${PORT:-8000}" \
      --workers="${OCTANE_WORKERS:-4}" \
      --max-requests="${OCTANE_MAX_REQUESTS:-500}" \
      "$@"
    ;;
  horizon)
    exec php artisan horizon "$@"
    ;;
  scheduler)
    exec php artisan schedule:work "$@"
    ;;
  outbox-relay)
    exec php artisan outbox:relay "$@"
    ;;
  migrate)
    # --isolated: блокировка не даст двум подам мигрировать одновременно
    exec php artisan migrate --force --isolated "$@"
    ;;
  artisan)
    exec php artisan "$@"
    ;;
  *)
    exec "$role" "$@"
    ;;
esac
