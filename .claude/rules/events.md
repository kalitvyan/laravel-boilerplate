---
paths:
  - "apps/api/src/*/Domain/**/Event/**"
  - "apps/api/src/*/Application/Event/**"
  - "apps/api/src/*/Contract/Event/**"
  - "apps/api/src/Shared/Infrastructure/Event/**"
  - "apps/api/src/Shared/Infrastructure/Outbox/**"
---

# События

- Доменные события обрабатываются синхронно в транзакции команды. Падение
  листенера откатывает команду.
- Наружу событие уходит, только если зарегистрирован транслятор в
  `DomainEventTranslatorMap`. По умолчанию не уходит ничего.
- Интеграционное событие лежит в `Contract/Event`, имя вида
  `context.event_name`, класс с суффиксом версии (`UserRegisteredV1`). Поля
  только добавляются; несовместимое изменение — новая версия.
- Подписка идёт по имени и версии. Обработчик идемпотентен по эффектам в БД
  (inbox), внешние эффекты — at-least-once, учитывай это.
- Метаданные сериализуются с `JSON_FORCE_OBJECT`: `json_encode([])` даёт `[]`,
  и relay отправит такое сообщение в dead letter.
- Регистрация трансляторов, листенеров и подписчиков — только в `register()`.
