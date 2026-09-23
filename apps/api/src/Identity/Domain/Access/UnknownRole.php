<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\Access;

use LaravelBoilerplate\Shared\Domain\Exception\DomainError;

final class UnknownRole extends DomainError
{
    public static function named(string $role): self
    {
        return new self(sprintf('Role "%s" does not exist', $role));
    }
}
