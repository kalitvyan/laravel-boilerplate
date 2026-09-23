<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\User;

use LaravelBoilerplate\Shared\Domain\Exception\DomainError;

final class EmailAlreadyRegistered extends DomainError
{
    public static function create(): self
    {
        return new self('Email is already registered');
    }
}
