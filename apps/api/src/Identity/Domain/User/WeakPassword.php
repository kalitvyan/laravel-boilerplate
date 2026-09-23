<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\User;

use LaravelBoilerplate\Shared\Domain\Exception\DomainError;

final class WeakPassword extends DomainError
{
    public static function tooShort(int $min): self
    {
        return new self(sprintf('Password must be at least %d characters long', $min));
    }

    public static function tooLong(int $maxBytes): self
    {
        return new self(sprintf('Password must not exceed %d bytes', $maxBytes));
    }
}
