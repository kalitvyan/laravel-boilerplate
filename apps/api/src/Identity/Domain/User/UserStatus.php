<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\User;

enum UserStatus: string
{
    case Active = 'active';
    case Blocked = 'blocked';
}
