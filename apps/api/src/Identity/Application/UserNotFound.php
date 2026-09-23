<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application;

use LaravelBoilerplate\Shared\Application\Exception\NotFound;

final class UserNotFound extends NotFound
{
    public static function withId(string $id): self
    {
        return new self(sprintf('User %s was not found', $id));
    }
}
