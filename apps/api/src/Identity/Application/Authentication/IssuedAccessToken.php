<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\Authentication;

use DateTimeImmutable;
use SensitiveParameter;

final readonly class IssuedAccessToken
{
    public function __construct(
        public string $id,
        #[SensitiveParameter]
        public string $value,
        public DateTimeImmutable $expiresAt,
    ) {}
}
