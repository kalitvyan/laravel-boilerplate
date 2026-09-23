<?php

declare(strict_types=1);

use LaravelBoilerplate\Shared\Infrastructure\Persistence\Row;

$row = static fn (array $data): Row => Row::from((object) $data, 'test.rows');

it('reads typed values', function () use ($row): void {
    $data = $row([
        'id' => 'x',
        'count' => 7,
        'at' => '2026-01-01 10:00:00.123456+00',
        'nothing' => null,
        'payload' => '{"a":1}',
    ]);

    expect($data->string('id'))->toBe('x')
        ->and($data->int('count'))->toBe(7)
        ->and($data->timestamp('at')->format('u'))->toBe('123456')
        ->and($data->nullableString('nothing'))->toBeNull()
        ->and($data->nullableTimestamp('nothing'))->toBeNull()
        ->and($data->jsonObject('payload'))->toBe(['a' => 1]);
});

it('fails loudly with the column name', function () use ($row): void {
    $row(['id' => 42])->string('id');
})->throws(LogicException::class, 'test.rows.id');

it('rejects json that is not an object', function () use ($row): void {
    $row(['payload' => '[1,2]'])->jsonObject('payload');
})->throws(LogicException::class);
