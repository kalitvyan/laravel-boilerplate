<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Domain\Event;

use DateTimeImmutable;

interface DomainEvent
{
    public function occurredAt(): DateTimeImmutable;
}
