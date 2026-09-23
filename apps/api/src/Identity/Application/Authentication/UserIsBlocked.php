<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use LaravelBoilerplate\Shared\Application\Exception\AccessDenied;

final class UserIsBlocked extends AccessDenied
{
    public static function create(): self
    {
        return new self('Account is blocked');
    }
}
