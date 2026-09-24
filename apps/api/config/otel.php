<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('OTEL_ENABLED', false),

    'service' => [
        'name' => env('OTEL_SERVICE_NAME', 'laravel-boilerplate-api'),
        'namespace' => env('OTEL_SERVICE_NAMESPACE', 'laravel-boilerplate'),
        'version' => env('OTEL_SERVICE_VERSION', 'dev'),
    ],

    'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://otel-lgtm:4318'),

    // Доля корневых трейсов; для дочерних решение наследуется от родителя
    'sample_ratio' => (float) env('OTEL_TRACES_SAMPLER_ARG', 1.0),

    // Пути, которые не трассируются (пробы оркестратора)
    'ignore_paths' => ['health/*'],

    'metrics' => [
        'enabled' => (bool) env('OTEL_METRICS_ENABLED', env('OTEL_ENABLED', false)),
        // Интервал экспорта: чаще 15 секунд для API смысла не имеет
        'export_interval_ms' => (int) env('OTEL_METRIC_EXPORT_INTERVAL', 15000),
    ],

    'flush_interval_ms' => (int) env('OTEL_FLUSH_INTERVAL', 10000),
];
