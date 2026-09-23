<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\Access;

use Stringable;

final readonly class Role implements Stringable
{
    private function __construct(private string $name) {}

    public static function fromString(string $name): self
    {
        $normalized = mb_strtolower(trim($name));

        if (preg_match('/^[a-z][a-z0-9_]{1,63}$/', $normalized) !== 1) {
            throw UnknownRole::named($name);
        }

        return new self($normalized);
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function equals(self $other): bool
    {
        return $this->name === $other->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
