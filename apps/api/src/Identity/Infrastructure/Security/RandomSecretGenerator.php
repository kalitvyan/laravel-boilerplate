<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Infrastructure\Security;

use LaravelBoilerplate\Identity\Application\Port\SecretGenerator;

final readonly class RandomSecretGenerator implements SecretGenerator
{
    public function generate(): string
    {
        // 256 бит энтропии, base64url без паддинга: безопасно в заголовках и URL
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
