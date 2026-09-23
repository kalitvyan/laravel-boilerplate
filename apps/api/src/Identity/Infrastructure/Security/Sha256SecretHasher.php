<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Security;

use LaravelBoilerplate\Identity\Application\Port\SecretHasher;

final readonly class Sha256SecretHasher implements SecretHasher
{
    public function hash(string $secret): string
    {
        return hash('sha256', $secret);
    }
}
