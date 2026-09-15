<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Domain\Id;

use JsonSerializable;
use LaravelBoilerplate\Shared\Domain\Exception\InvalidIdentifier;
use Stringable;
use Symfony\Component\Uid\Uuid;

abstract readonly class AggregateId implements JsonSerializable, Stringable
{
    final private function __construct(private string $value) {}

    public static function generate(): static
    {
        return new static(Uuid::v7()->toRfc4122());
    }

    public static function fromString(string $value): static
    {
        if (! Uuid::isValid($value)) {
            throw InvalidIdentifier::for($value);
        }

        return new static(strtolower($value));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $other::class === static::class && $other->value === $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
