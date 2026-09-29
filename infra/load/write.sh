#!/bin/sh
set -eu

# Регистрация пользователей: транзакция, bcrypt, запись в outbox.
# oha не умеет менять тело между запросами, поэтому используем параллельный curl.

API="${API:-http://127.0.0.1:8080}"
TOTAL="${TOTAL:-200}"
PARALLEL="${PARALLEL:-10}"

start=$(date +%s)

seq 1 "$TOTAL" | xargs -P "$PARALLEL" -I{} sh -c "
  curl -s -o /dev/null -w '%{http_code}\n' -X POST $API/api/v1/users \
    -H 'Content-Type: application/json' \
    -d '{\"email\":\"load-{}-$(date +%s)@example.com\",\"password\":\"correct horse battery staple\"}'
" | sort | uniq -c

end=$(date +%s)
elapsed=$((end - start))

echo "Всего: $TOTAL за ${elapsed}с → $((TOTAL / (elapsed > 0 ? elapsed : 1))) rps"
