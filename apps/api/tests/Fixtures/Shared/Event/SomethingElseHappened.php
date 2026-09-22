<?php

declare(strict_types=1);

namespace Tests\Fixtures\Shared\Event;

use DateTimeImmutable;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

final readonly class SomethingElseHappened implements DomainEvent
{
    public function __construct(
        public string $thingId,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable('2026-01-01 00:00:00'),
    ) {}

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
