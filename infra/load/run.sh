#!/bin/sh
set -eu

# Нагрузочные прогоны против поднятого prod-стека.
# Результаты складываются в infra/load/results/ и сравниваются между запусками.

NETWORK="${NETWORK:-laravel-boilerplate-prod_default}"
API="${API:-http://api:8000}"
WEB="${WEB:-http://web:3000}"
DURATION="${DURATION:-30s}"
CONNECTIONS="${CONNECTIONS:-50}"
LABEL="${LABEL:-$(date +%Y%m%d-%H%M%S)}"

OUT="infra/load/results/$LABEL"
mkdir -p "$OUT"

oha() {
  docker run --rm --network "$NETWORK" ghcr.io/hatoo/oha:latest "$@"
}

run() {
  name="$1"
  shift

  echo "→ $name"
  # Прогрев: первые запросы прогревают OPcache, пулы соединений и кэши
  oha -z 5s -c 10 --no-tui "$@" > /dev/null
  oha -z "$DURATION" -c "$CONNECTIONS" --no-tui "$@" | tee "$OUT/$name.txt" | grep -E 'Requests/sec|Average|Slowest|in [0-9.]+ (ms|secs)' | head -8
  echo
}

echo "Нагрузка: $DURATION, соединений: $CONNECTIONS"
echo "Результаты: $OUT"
echo

run health "$API/health/live"

if [ -n "${TOKEN:-}" ]; then
  run read-api -H "Authorization: Bearer $TOKEN" "$API/api/v1/users/me"
fi

if [ -n "${COOKIE:-}" ]; then
  run read-bff -H "Cookie: $COOKIE" "$WEB/bff/users/me"
fi

echo "Готово: $OUT"
