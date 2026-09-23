<?php

declare(strict_types=1);

use LaravelBoilerplate\Identity\Domain\User\Email;
use LaravelBoilerplate\Identity\Domain\User\InvalidEmail;

it('normalizes case and whitespace', function (): void {
    expect(Email::fromString('  John.Doe@Example.COM ')->toString())->toBe('john.doe@example.com');
});

it('rejects invalid emails', function (string $email): void {
    Email::fromString($email);
})->with([
    'empty' => [''],
    'no at' => ['not-an-email'],
    'no domain' => ['user@'],
    'too long' => [str_repeat('a', 250).'@x.io'],
])->throws(InvalidEmail::class);
