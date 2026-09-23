<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Identity\Domain\User\Event;

use DateTimeImmutable;
use LaravelBoilerplate\Identity\Domain\User\UserId;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

final readonly class PasswordChanged implements DomainEvent
{
    public function __construct(
        public UserId $userId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
