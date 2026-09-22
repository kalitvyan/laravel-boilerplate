<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Infrastructure\Event;

use LaravelBoilerplate\Shared\Application\Event\EventCollector;
use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

/**
 * Регистрируется как scoped: в Octane новый экземпляр на каждый запрос или джоб.
 */
final class InMemoryEventCollector implements EventCollector
{
    /** @var list<DomainEvent> */
    private array $events = [];

    public function collect(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->events[] = $event;
        }
    }

    public function release(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }
}
