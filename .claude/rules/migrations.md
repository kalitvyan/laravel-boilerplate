---
paths:
  - "apps/api/database/migrations/**"
---

# Миграции

- Закоммиченные миграции не меняются — только новые.
- Временные колонки всегда с точностью 6: `timestampTz('x', 6)`,
  `timestampsTz(6)`. Без неё Postgres округляет до секунды. Проверяется
  `SchemaPrecisionTest`.
- Таблицы контекста живут в его схеме: `Schema::create('identity.users', ...)`.
  Схема добавляется в `0001_01_01_000000_create_context_schemas`, а сама схема —
  в `DB_SEARCH_PATH`, иначе `migrate:fresh` её не очистит.
- Объекты вне таблиц (функции, типы, расширения) создаются идемпотентно:
  `CREATE OR REPLACE`, `IF NOT EXISTS`. `migrate:fresh` их не удаляет.
- Внешних ключей между схемами разных контекстов нет.
- После изменения — `make fresh` дважды подряд.
