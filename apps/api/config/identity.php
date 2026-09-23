<?php

declare(strict_types=1);

return [
    'tokens' => [
        // ISO 8601 durations
        'access_ttl' => env('IDENTITY_ACCESS_TTL', 'PT15M'),
        'refresh_ttl' => env('IDENTITY_REFRESH_TTL', 'P30D'),
    ],

    'rbac' => [
        'permissions' => [
            'identity.users.read',
            'identity.users.block',
            'identity.roles.assign',
        ],

        'roles' => [
            'user' => [],
            'support' => ['identity.users.read'],
            'admin' => [
                'identity.users.read',
                'identity.users.block',
                'identity.roles.assign',
            ],
        ],

        'default_role' => 'user',
    ],
];
