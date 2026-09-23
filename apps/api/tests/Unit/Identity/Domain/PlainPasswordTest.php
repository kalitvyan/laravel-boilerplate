<?php

declare(strict_types=1);

use LaravelBoilerplate\Identity\Domain\User\PlainPassword;
use LaravelBoilerplate\Identity\Domain\User\WeakPassword;

it('accepts passwords within policy', function (): void {
    expect(PlainPassword::fromString('correct horse battery')->reveal())->toBe('correct horse battery');
});

it('rejects short passwords', function (): void {
    PlainPassword::fromString('short');
})->throws(WeakPassword::class, 'at least 12');

it('rejects passwords longer than 72 bytes', function (): void {
    // 37 кириллических символов = 74 байта: по символам в норме, но bcrypt обрезал бы хвост
    PlainPassword::fromString(str_repeat('я', 37));
})->throws(WeakPassword::class, '72 bytes');

it('does not leak into dumps', function (): void {
    $password = PlainPassword::fromString('correct horse battery');

    // var_export() игнорирует __debugInfo() by design и здесь не проверяется
    expect(print_r($password, true))->not->toContain('correct horse');
});

it('refuses serialization', function (): void {
    serialize(PlainPassword::fromString('correct horse battery'));
})->throws(LogicException::class);
