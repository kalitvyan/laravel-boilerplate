#!/bin/sh
# Стиль не обсуждается: Pint приводит отредактированный файл к норме сразу
input=$(cat)
file=$(printf '%s' "$input" | sed -n 's/.*"file_path" *: *"\([^"]*\)".*/\1/p' | head -1)

case "$file" in
  */apps/api/*.php) ;;
  *) exit 0 ;;
esac

cd "$CLAUDE_PROJECT_DIR" || exit 0

# Стек не поднят — пропускаем: make qa всё равно проверит стиль
docker compose -f infra/docker/compose/compose.yaml ps --status running --services 2>/dev/null | grep -qx api || exit 0

rel=${file#"$CLAUDE_PROJECT_DIR"/apps/api/}
docker compose -f infra/docker/compose/compose.yaml exec -T api ./vendor/bin/pint --quiet "$rel" >/dev/null 2>&1

exit 0
