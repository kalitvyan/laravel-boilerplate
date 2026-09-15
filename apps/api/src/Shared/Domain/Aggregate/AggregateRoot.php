<?php

declare(strict_types=1);

namespace LaravelBoilerplate\Shared\Domain\Aggregate;

use LaravelBoilerplate\Shared\Domain\Event\DomainEvent;

abstract class AggregateRoot
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    protected function recordThat(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
