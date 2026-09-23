<?php

declare(strict_types=1);

return [
    'tokens' => [
        // ISO 8601 durations
        'access_ttl' => env('IDENTITY_ACCESS_TTL', 'PT15M'),
        'refresh_ttl' => env('IDENTITY_REFRESH_TTL', 'P30D'),
    ],
];
