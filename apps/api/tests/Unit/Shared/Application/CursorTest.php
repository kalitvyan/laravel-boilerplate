<?php

declare(strict_types=1);

use LaravelBoilerplate\Shared\Application\Pagination\Cursor;
use LaravelBoilerplate\Shared\Application\Pagination\InvalidCursor;

$b64 = static fn (string $raw): string => rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

it('round-trips position as url-safe string', function (): void {
    $cursor = Cursor::fromPosition([
        'created_at' => '2026-01-01 00:00:00+00',
        'id' => '0191f2a0-7c4b-7d2e-9a51-3c2b1e0f4a6d',
    ]);

    expect($cursor->encode())->toMatch('/^[A-Za-z0-9_-]+$/')
        ->and(Cursor::decode($cursor->encode())->position)->toBe($cursor->position);
});

it('rejects malformed cursors', function (string $value): void {
    Cursor::decode($value);
})->with([
    'invalid alphabet' => ['***'],
    'not json' => [$b64('nope')],
    'empty object' => [$b64('{}')],
    'list' => [$b64('[1,2]')],
    'nested value' => [$b64('{"id":{"a":1}}')],
    'null value' => [$b64('{"id":null}')],
    'too long' => [str_repeat('a', 1025)],
])->throws(InvalidCursor::class);
