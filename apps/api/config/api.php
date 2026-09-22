<?php

declare(strict_types=1);

return [
    // Базовый URI для поля "type" (RFC 9457). Пусто → about:blank
    'problem_type_base_url' => env('API_PROBLEM_TYPE_BASE_URL'),

    // Прокси, которым разрешено передавать X-Forwarded-*: BFF, ingress, docker/k8s сети.
    // Никогда не '*', если API доступен напрямую из интернета
    'trusted_proxies' => env('TRUSTED_PROXIES', '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,127.0.0.1'),

    'rate_limit' => [
        'per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 120),
    ],
];
