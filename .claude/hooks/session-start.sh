#!/bin/sh
# Проверки работают только в контейнерах: без поднятого стека make qa упадёт
cd "$CLAUDE_PROJECT_DIR" || exit 0

if ! docker compose -f infra/docker/compose/compose.yaml ps --status running --services 2>/dev/null | grep -qx api; then
  echo "Dev-стек не запущен. Перед проверками (make qa, make test) выполни make up."
fi

exit 0
