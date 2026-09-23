<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Hashing;

use Illuminate\Contracts\Hashing\Hasher;
use LaravelBoilerplate\Identity\Application\Port\PasswordHasher;
use LaravelBoilerplate\Identity\Domain\User\HashedPassword;
use LaravelBoilerplate\Identity\Domain\User\PlainPassword;

final readonly class LaravelPasswordHasher implements PasswordHasher
{
    public function __construct(private Hasher $hasher) {}

    public function hash(PlainPassword $password): HashedPassword
    {
        return HashedPassword::fromHash($this->hasher->make($password->reveal()));
    }

    public function verify(PlainPassword $password, HashedPassword $hash): bool
    {
        return $this->hasher->check($password->reveal(), $hash->toString());
    }

    public function needsRehash(HashedPassword $hash): bool
    {
        return $this->hasher->needsRehash($hash->toString());
    }
}
