#!/bin/sh
# Закоммиченные миграции не меняются — только новые
input=$(cat)
file=$(printf '%s' "$input" | sed -n 's/.*"file_path" *: *"\([^"]*\)".*/\1/p' | head -1)

case "$file" in
  */apps/api/database/migrations/*.php) ;;
  *) exit 0 ;;
esac

cd "$CLAUDE_PROJECT_DIR" || exit 0
rel=${file#"$CLAUDE_PROJECT_DIR"/}

if git cat-file -e "HEAD:$rel" 2>/dev/null; then
  echo "Миграция $rel уже закоммичена и не меняется. Создай новую миграцию." >&2
  exit 2
fi

exit 0
