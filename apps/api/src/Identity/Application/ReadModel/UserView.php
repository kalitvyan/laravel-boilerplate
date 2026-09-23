<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Application\ReadModel;

use DateTimeImmutable;

final readonly class UserView
{
    public function __construct(
        public string $id,
        public string $email,
        public string $status,
        public DateTimeImmutable $registeredAt,
    ) {}
}
