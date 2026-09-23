<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Port;

use LaravelBoilerplate\Identity\Domain\User\HashedPassword;
use LaravelBoilerplate\Identity\Domain\User\PlainPassword;

interface PasswordHasher
{
    public function hash(PlainPassword $password): HashedPassword;

    public function verify(PlainPassword $password, HashedPassword $hash): bool;

    public function needsRehash(HashedPassword $hash): bool;
}
