---
paths:
  - "apps/api/src/Shared/Infrastructure/Tracing/**"
  - "apps/api/src/Shared/Infrastructure/Metrics/**"
  - "apps/api/src/Shared/Infrastructure/Telemetry/**"
  - "infra/observability/**"
---

# Наблюдаемость

- Имена спанов и метрик — контракт: на них завязаны дашборд и алерты.
  Переименование требует правки `infra/observability/`.
- Атрибуты метрик — только низкая кардинальность: шаблон роута, имя команды,
  исход. Никаких идентификаторов и URL.
- Метрики экспортируются с cumulative temporality: Prometheus молча
  отбрасывает delta.
- Метрики уезжают только по `forceFlush()` — его вызывает terminable
  middleware и слушатели очереди. Короткоживущие команды вызывают
  `FlushTelemetry::force()`.
- Имена метрик — константы в `MetricName`.
