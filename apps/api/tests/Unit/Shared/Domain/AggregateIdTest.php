<?php

declare(strict_types=1);

use LaravelBoilerplate\Shared\Domain\Exception\InvalidIdentifier;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;
use Tests\Fixtures\Shared\CustomerId;
use Tests\Fixtures\Shared\OrderId;

it('generates UUIDv7', function (): void {
    expect(Uuid::fromString(OrderId::generate()->toString()))->toBeInstanceOf(UuidV7::class);
});

it('normalizes case and compares by value', function (): void {
    $upper = OrderId::fromString('0191F2A0-7C4B-7D2E-9A51-3C2B1E0F4A6D');
    $lower = OrderId::fromString('0191f2a0-7c4b-7d2e-9a51-3c2b1e0f4a6d');

    expect($upper->equals($lower))->toBeTrue()
        ->and(json_encode($upper))->toBe('"0191f2a0-7c4b-7d2e-9a51-3c2b1e0f4a6d"');
});

it('does not equal an id of another type with the same value', function (): void {
    $value = '0191f2a0-7c4b-7d2e-9a51-3c2b1e0f4a6d';

    expect(OrderId::fromString($value)->equals(CustomerId::fromString($value)))->toBeFalse();
});

it('rejects invalid values', function (): void {
    OrderId::fromString('not-a-uuid');
})->throws(InvalidIdentifier::class);
