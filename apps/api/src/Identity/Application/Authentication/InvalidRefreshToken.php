<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use LaravelBoilerplate\Shared\Application\Exception\Unauthenticated;

final class InvalidRefreshToken extends Unauthenticated
{
    public static function create(): self
    {
        return new self('Refresh token is invalid or expired');
    }
}
