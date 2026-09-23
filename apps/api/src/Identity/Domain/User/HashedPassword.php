<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\User;

use InvalidArgumentException;

final readonly class HashedPassword
{
    private function __construct(private string $hash) {}

    public static function fromHash(string $hash): self
    {
        if ($hash === '') {
            throw new InvalidArgumentException('Password hash must not be empty');
        }

        return new self($hash);
    }

    public function toString(): string
    {
        return $this->hash;
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['hash' => '********'];
    }
}
