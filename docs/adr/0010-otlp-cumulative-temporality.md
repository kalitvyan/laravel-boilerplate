# 0010. Cumulative temporality для метрик OTLP

- Status: Accepted
- Date: 2026-09-24

## Context

Метрики экспортируются по OTLP в коллектор, который пишет их в Prometheus
через эндпоинт `/api/v1/otlp`.

При первой настройке в Prometheus доходили только gauge. Гистограммы и счётчики
терялись полностью, при этом:

- `MeterProvider::forceFlush()` возвращал `true`;
- `ExportingReader::collect()` возвращал `true`;
- коллектор отвечал `HTTP 200` с телом `{"partialSuccess":{}}`;
- `otelcol_receiver_refused_metric_points_total` оставался нулевым;
- в логах коллектора не было ни одной записи об отказе.

Отладка через эхо-сервер показала, что экспортёр отправляет точки с
`"aggregationTemporality": 1` (delta). Prometheus на `/api/v1/otlp` принимает
только cumulative и отбрасывает delta молча. Gauge проходили, потому что у них
temporality отсутствует.

## Decision

`MetricExporter` создаётся с явной temporality:

```php
new MetricExporter($transport, Temporality::CUMULATIVE)
```

## Alternatives considered

- **Включить приём delta на стороне Prometheus.** Отклонено: cumulative — нативная
  модель Prometheus, а delta имеет смысл для приёмников вроде Datadog.
- **Оставить значение по умолчанию.** Отклонено: оно зависит от версии пакета
  и меняется молча.

## Consequences

- Метрики всех типов доходят до Prometheus.
- Появляется зависимость от выбранного приёмника: при переходе на приёмник с
  нативной delta temporality эту строку нужно будет менять.
- Тихий отказ такого рода не диагностируется по логам и кодам возврата.
  Проверка доставки метрик добавлена в чек-лист этапа наблюдаемости.
