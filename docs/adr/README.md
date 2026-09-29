# Решения (ADR)

Архитектурные решения с контекстом, в котором они принимались. Записи
неизменяемы: устаревшее решение не правится, а помечается статусом
«Заменено» со ссылкой на новое.

| # | Решение | Статус |
|---|---|---|
| [0001](0001-frankenphp-octane.md) | FrankenPHP + Octane как runtime | Принято |
| [0002](0002-modular-monolith.md) | Модульный монолит, структура по контекстам | Принято |
| [0003](0003-cqrs-lite-persistence.md) | CQRS-lite: мапперы на запись, Query Builder на чтение | Принято |
| [0004](0004-transactional-outbox.md) | Transactional outbox поверх Laravel Queue | Принято |
| [0005](0005-identity-context.md) | Identity как отдельный контекст, авторизация вне Laravel auth | Принято |
| [0006](0006-nextjs-bff.md) | Next.js как BFF, токены не попадают в браузер | Принято |
| [0007](0007-problem-details.md) | RFC 9457 для ошибок API | Принято |
| [0008](0008-spec-first-openapi.md) | Spec-first OpenAPI с проверкой контракта в тестах | Принято |
| [0009](0009-local-s3.md) | RustFS вместо MinIO для локального S3 | Принято |
| [0010](0010-otlp-cumulative-temporality.md) | Cumulative temporality для метрик OTLP | Принято |
| [0011](0011-container-registration.md) | Расширение карт только в `register()` | Принято |
| [0012](0012-refresh-grace-window.md) | Окно грейса при ротации refresh-токена | Принято |
